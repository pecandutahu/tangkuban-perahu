<?php

namespace App\Modules\Payroll\Services;

use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollItemComponent;
use Illuminate\Support\Facades\DB;

class GeneratePayrollService
{
    /**
     * Generate Draft Payroll for a specified period
     */
    public function generate(array $data)
    {
        // 1. Concurrency Safe: Gunakan Cache Lock berdasarkan rentang tanggal agar tidak ada eksekusi ganda di detik yang sama
        $lockKey = 'generate_payroll_' . $data['start_date'] . '_' . $data['end_date'];
        
        // Coba dapatkan lock selama 60 detik
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 60);

        if (!$lock->get()) {
            throw new \Exception('Proses generate untuk periode ini sedang berjalan. Silakan tunggu beberapa saat.');
        }

        try {
            return DB::transaction(function () use ($data) {
                // 2. Anti Duplicate & Overlap: Cek apakah periode dengan tanggal yang beririsan sudah pernah ada
                $existingPeriod = PayrollPeriod::where('start_date', '<=', $data['end_date'])
                    ->where('end_date', '>=', $data['start_date'])
                    ->first();

                if ($existingPeriod) {
                    throw new \Exception("Gagal: Periode yang Anda buat beririsan dengan Periode {$existingPeriod->code} ({$existingPeriod->start_date->format('d/m/Y')} s/d {$existingPeriod->end_date->format('d/m/Y')} - Status: {$existingPeriod->status}). Silakan hapus draft lama atau sesuaikan tanggal.");
                }

                // 3. Buat Periode Payroll (Draft)
                $period = PayrollPeriod::create([
                    'code' => $data['code'] ?? 'PRL-' . now()->format('YmHis'),
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'pay_date' => $data['pay_date'],
                    'period_type' => $data['period_type'],
                    'created_by' => \Illuminate\Support\Facades\Auth::id() ?? 'SYSTEM',
                    'status' => 'draft',
                ]);

                // 4. Ambil semua Karyawan aktif dengan komponen khususnya
                $employees = Employee::with([
                    'specificComponents.component',
                    'department:id,name',
                    'branch:id,name',
                ])->where('is_active', true)->get();

                // 4.1. Pra-muat seluruh Template Gaji ke Memory Cache untuk menghindari N+1 query
                $cachedTemplates = \App\Models\PayrollTemplate::with('components.component')->get();

                // Konfigurasi Pengecualian Komponen BPJS
                $excludedComponentIds = [];
                $exSetting = \App\Models\Setting::where('key', 'pph21_excluded_components')->first();
                if ($exSetting && !empty($exSetting->value)) {
                    $excludedComponentIds = json_decode($exSetting->value, true) ?? [];
                }
                
                $taxStrategy = \App\Modules\Payroll\Calculators\Pph21CalculatorFactory::make();
                $taxComponent = null;
                if ($taxStrategy) {
                    $taxComponent = \App\Models\PayrollComponent::firstOrCreate(
                        ['code' => 'TAX_PPH21'],
                        [
                            'name' => 'Potongan PPh 21',
                            'component_type' => 'deduction',
                            'is_taxable' => false,
                            'default_amount' => 0,
                            'is_active' => true,
                        ]
                    );
                }

                $now = now();
                $itemsToInsert = [];
                $componentsDataCache = []; 

                // 5. Kalkulasi di Memory dan Siapkan Data Bulk
                foreach ($employees as $employee) {
                    try {
                        $template = PayrollTemplateResolver::resolve($employee, $cachedTemplates);
                    } catch (\DomainException $e) {
                        continue; 
                    }

                    $totalBruto = 0;
                    $totalDeduction = 0;
                    $taxableBruto = 0;
                    $employeeComponentsToInsert = [];

                    $specificComponentsMap = [];
                    foreach ($employee->specificComponents as $specComp) {
                        if ($specComp->is_active && $specComp->component) {
                            $specificComponentsMap[$specComp->component->id] = $specComp;
                        }
                    }

                    $processedComponentIds = [];

                    // Proses komponen dari template
                    foreach ($template->components as $templateComponent) {
                        $component = $templateComponent->component;
                        if (!$component || !$component->is_active) continue;

                        $processedComponentIds[] = $component->id;

                        $amount = isset($specificComponentsMap[$component->id]) 
                            ? $specificComponentsMap[$component->id]->amount 
                            : $component->default_amount;

                        $employeeComponentsToInsert[] = [
                            'payroll_component_id' => $component->id,
                            'component_code' => $component->code,
                            'component_name' => $component->name,
                            'component_type' => $component->component_type,
                            'amount' => $amount,
                            'source' => isset($specificComponentsMap[$component->id]) ? 'OVR_EMP' : 'SYSTEM',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        if ($component->component_type === 'earning') {
                            $totalBruto += $amount;
                            $taxableBruto += $amount;
                        } elseif ($component->component_type === 'deduction') {
                            $totalDeduction += $amount;
                            if (in_array($component->id, $excludedComponentIds)) {
                                $taxableBruto -= $amount;
                            }
                        }
                    }

                    // Proses sisa komponen spesifik
                    foreach ($specificComponentsMap as $compId => $specComp) {
                        if (!in_array($compId, $processedComponentIds)) {
                            $component = $specComp->component;

                            $employeeComponentsToInsert[] = [
                                'payroll_component_id' => $component->id,
                                'component_code' => $component->code,
                                'component_name' => $component->name,
                                'component_type' => $component->component_type,
                                'amount' => $specComp->amount,
                                'source' => 'OVR_ADD',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];

                            if ($component->component_type === 'earning') {
                                $totalBruto += $specComp->amount;
                                $taxableBruto += $specComp->amount;
                            } elseif ($component->component_type === 'deduction') {
                                $totalDeduction += $specComp->amount;
                                if (in_array($component->id, $excludedComponentIds)) {
                                    $taxableBruto -= $specComp->amount;
                                }
                            }
                        }
                    }

                    // Hitung Pajak PPh21 menggunakan Nilai Bruto yang telah dipotong Iuran (Jika ada)
                    $pph21Amount = $taxStrategy ? $taxStrategy->calculate($employee, $taxableBruto) : 0;

                    if ($pph21Amount > 0 && $taxComponent) {
                        $employeeComponentsToInsert[] = [
                            'payroll_component_id' => $taxComponent->id,
                            'component_code' => $taxComponent->code,
                            'component_name' => $taxComponent->name,
                            'component_type' => 'deduction',
                            'amount' => $pph21Amount,
                            'source' => 'SYSTEM_TAX',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $totalDeduction += $pph21Amount;
                    }

                    // Susun Data Item (Sudah terhitung Nettonya)
                    $itemsToInsert[] = [
                        'payroll_period_id' => $period->id,
                        'employee_id'       => $employee->id,
                        'employee_name'     => $employee->name,
                        'department_name'   => $employee->department?->name,
                        'branch_name'       => $employee->branch?->name,
                        'status'            => 'draft',
                        'total_bruto'       => $totalBruto,
                        'total_deduction'   => $totalDeduction,
                        'total_netto'       => $totalBruto - $totalDeduction,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];

                    // Simpan sementara referensi komponen employee_id
                    $componentsDataCache[$employee->id] = $employeeComponentsToInsert;
                }

                // 6. Eksekusi Bulk Insert Payroll Items
                foreach (array_chunk($itemsToInsert, 500) as $chunk) {
                    PayrollItem::insert($chunk);
                }

                // 7. Ambil kembali Items yang barusan disimpan untuk mendapatkan auto-increment `id`
                $insertedItems = PayrollItem::where('payroll_period_id', $period->id)->get(['id', 'employee_id']);

                $finalComponentsToInsert = [];
                foreach ($insertedItems as $insertedItem) {
                    $empId = $insertedItem->employee_id;
                    if (isset($componentsDataCache[$empId])) {
                        foreach ($componentsDataCache[$empId] as $compRow) {
                            $compRow['payroll_item_id'] = $insertedItem->id; // Assign Parent ID
                            $finalComponentsToInsert[] = $compRow;
                        }
                    }
                }

                // 8. Eksekusi Bulk Insert Payroll Item Components
                foreach (array_chunk($finalComponentsToInsert, 1000) as $chunk) {
                    PayrollItemComponent::insert($chunk);
                }

                return $period;
            });
        } finally {
            // Selalu lepas lock apapun yang terjadi (berhasil atau error)
            $lock->release();
        }
    }

    /**
     * Regenerate spesifik 1 karyawan pada Payroll Period tertentu.
     * Hanya me-refresh komponen bawaan SYSTEM dan OVERRIDE.
     */
    public function regenerateItem($periodId, $itemId)
    {
        return DB::transaction(function () use ($periodId, $itemId) {
            $period = PayrollPeriod::findOrFail($periodId);
            if ($period->status !== 'draft') {
                throw new \Exception("Hanya draft yang dapat di-regenerate (diperbarui).");
            }

            $item = PayrollItem::where('id', $itemId)->where('payroll_period_id', $periodId)->firstOrFail();
            $employee = Employee::with('specificComponents.component')->find($item->employee_id);

            if (!$employee || !$employee->is_active) {
                throw new \Exception("Karyawan tidak ditemukan atau sudah tidak aktif bekerja.");
            }

            // Hapus list komponen bawaan jabatan dan pajak yang lama
            PayrollItemComponent::where('payroll_item_id', $item->id)
                ->whereIn('source', ['SYSTEM', 'OVR_EMP', 'OVR_ADD', 'SYSTEM_TAX'])
                ->delete();

            // Hitung ulang komponen dari template terbaru
            try {
                $template = PayrollTemplateResolver::resolve($employee);
            } catch (\DomainException $e) {
                $template = null;
            }

            $specificComponentsMap = [];
            foreach ($employee->specificComponents as $specComp) {
                if ($specComp->is_active && $specComp->component) {
                    $specificComponentsMap[$specComp->component->id] = $specComp;
                }
            }

            $processedComponentIds = [];

            if ($template) {
                foreach ($template->components as $templateComponent) {
                    $component = $templateComponent->component;
                    
                    if (!$component || !$component->is_active) {
                        continue;
                    }

                    $processedComponentIds[] = $component->id;

                    $amount = isset($specificComponentsMap[$component->id]) 
                        ? $specificComponentsMap[$component->id]->amount 
                        : $component->default_amount;

                    PayrollItemComponent::create([
                        'payroll_item_id' => $item->id,
                        'payroll_component_id' => $component->id,
                        'component_code' => $component->code,
                        'component_name' => $component->name,
                        'component_type' => $component->component_type,
                        'amount' => $amount,
                        'source' => isset($specificComponentsMap[$component->id]) ? 'OVR_EMP' : 'SYSTEM',
                    ]);
                }
            }

            foreach ($specificComponentsMap as $compId => $specComp) {
                if (!in_array($compId, $processedComponentIds)) {
                    $component = $specComp->component;
                    PayrollItemComponent::create([
                        'payroll_item_id' => $item->id,
                        'payroll_component_id' => $component->id,
                        'component_code' => $component->code,
                        'component_name' => $component->name,
                        'component_type' => $component->component_type,
                        'amount' => $specComp->amount,
                        'source' => 'OVR_ADD',
                    ]);
                }
            }

            // Recalculate gabungan dengan IMPORT/MANUAL
            $totalBruto = 0;
            $totalDeduction = 0;
            $allComponents = PayrollItemComponent::where('payroll_item_id', $item->id)->get();
            
            // Konfigurasi Pengecualian Komponen BPJS
            $excludedComponentIds = [];
            $exSetting = \App\Models\Setting::where('key', 'pph21_excluded_components')->first();
            if ($exSetting && !empty($exSetting->value)) {
                $excludedComponentIds = json_decode($exSetting->value, true) ?? [];
            }
            
            // Variabel khusus pajak
            $taxableBruto = 0;

            foreach ($allComponents as $comp) {
                if ($comp->component_type === 'earning') {
                    $totalBruto += $comp->amount;
                    $taxableBruto += $comp->amount; // Tambah ke base awal pajak
                } elseif ($comp->component_type === 'deduction') {
                    $totalDeduction += $comp->amount;
                    
                    // Bila komponen ini dipatok sebagai pengurang bruto wajib
                    if (in_array($comp->payroll_component_id, $excludedComponentIds)) {
                        $taxableBruto -= $comp->amount;
                    }
                }
            }

            // Hitung Pajak PPh21 menggunakan Nilai Bruto yang telah dipotong Iuran (Jika ada)
            $taxStrategy = \App\Modules\Payroll\Calculators\Pph21CalculatorFactory::make();
            $pph21Amount = $taxStrategy ? $taxStrategy->calculate($employee, $taxableBruto) : 0;

            if ($pph21Amount > 0) {
                // Pastikan ada Master Komponen khusus untuk PPh 21
                $taxComponent = \App\Models\PayrollComponent::firstOrCreate(
                    ['code' => 'TAX_PPH21'],
                    [
                        'name' => 'Potongan PPh 21',
                        'component_type' => 'deduction',
                        'is_taxable' => false,
                        'default_amount' => 0,
                        'is_active' => true,
                    ]
                );

                \App\Models\PayrollItemComponent::create([
                    'payroll_item_id' => $item->id,
                    'payroll_component_id' => $taxComponent->id,
                    'component_code' => $taxComponent->code,
                    'component_name' => $taxComponent->name,
                    'component_type' => 'deduction',
                    'amount' => $pph21Amount,
                    'source' => 'SYSTEM_TAX',
                ]);

                // Tambahkan pajak ke total potongan
                $totalDeduction += $pph21Amount;
            }

            $item->update([
                'total_bruto' => $totalBruto,
                'total_deduction' => $totalDeduction,
                'total_netto' => $totalBruto - $totalDeduction,
            ]);

            return $item;
        });
    }

    /**
     * Regenerate SELURUH karyawan pada satu Payroll Period.
     * Hanya me-refresh komponen bawaan SYSTEM dan OVERRIDE. Data IMPORT (CSV) tetap utuh.
     */
    public function regeneratePeriod($periodId)
    {
        return DB::transaction(function () use ($periodId) {
            $period = PayrollPeriod::findOrFail($periodId);
            if ($period->status !== 'draft') {
                throw new \Exception("Hanya draft yang dapat di-regenerate (diperbarui).");
            }

            $items = PayrollItem::where('payroll_period_id', $periodId)->get();
            if ($items->isEmpty()) return $period;

            $itemIds = $items->pluck('id')->toArray();
            $employeeIds = $items->pluck('employee_id')->toArray();

            // 1. Bulk Delete komponen sistem/override yang lama
            PayrollItemComponent::whereIn('payroll_item_id', $itemIds)
                ->whereIn('source', ['SYSTEM', 'OVR_EMP', 'OVR_ADD', 'SYSTEM_TAX'])
                ->delete();

            // 2. Ambil karyawan terkait beserta komponen khususnya
            $employees = Employee::with('specificComponents.component')
                ->whereIn('id', $employeeIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            // 3. Ambil sisa komponen (seperti IMPORT / MANUAL) yang harus dipertahankan
            $remainingComponents = PayrollItemComponent::whereIn('payroll_item_id', $itemIds)
                ->get()
                ->groupBy('payroll_item_id');

            // 4. Pra-muat seluruh Template
            $cachedTemplates = \App\Models\PayrollTemplate::with('components.component')->get();

            // 5. Konfigurasi Setting Pajak dll
            $excludedComponentIds = [];
            $exSetting = \App\Models\Setting::where('key', 'pph21_excluded_components')->first();
            if ($exSetting && !empty($exSetting->value)) {
                $excludedComponentIds = json_decode($exSetting->value, true) ?? [];
            }
            
            $taxStrategy = \App\Modules\Payroll\Calculators\Pph21CalculatorFactory::make();
            $taxComponent = null;
            if ($taxStrategy) {
                $taxComponent = \App\Models\PayrollComponent::firstOrCreate(
                    ['code' => 'TAX_PPH21'],
                    [
                        'name' => 'Potongan PPh 21',
                        'component_type' => 'deduction',
                        'is_taxable' => false,
                        'default_amount' => 0,
                        'is_active' => true,
                    ]
                );
            }

            $now = now();
            $componentsToInsert = [];
            $itemsToUpdate = [];

            // 6. Kalkulasi Memory Berjalan
            foreach ($items as $item) {
                $employee = $employees->get($item->employee_id);
                
                // Variabel perhitungan total (netto) untuk satu item payroll
                $totalBruto = 0;
                $totalDeduction = 0;
                $taxableBruto = 0;

                // Hitung kembali komponen impor/manual yang tidak terhapus
                $remains = $remainingComponents->get($item->id);
                if ($remains) {
                    foreach ($remains as $comp) {
                        if ($comp->component_type === 'earning') {
                            $totalBruto += $comp->amount;
                            $taxableBruto += $comp->amount;
                        } elseif ($comp->component_type === 'deduction') {
                            $totalDeduction += $comp->amount;
                            if (in_array($comp->payroll_component_id, $excludedComponentIds)) {
                                $taxableBruto -= $comp->amount;
                            }
                        }
                    }
                }

                // Jika karyawan masih aktif dan ditemukan
                if ($employee) {
                    try {
                        $template = PayrollTemplateResolver::resolve($employee, $cachedTemplates);
                    } catch (\DomainException $e) {
                        $template = null;
                    }

                    $specificComponentsMap = [];
                    foreach ($employee->specificComponents as $specComp) {
                        if ($specComp->is_active && $specComp->component) {
                            $specificComponentsMap[$specComp->component->id] = $specComp;
                        }
                    }

                    $processedComponentIds = [];

                    if ($template) {
                        foreach ($template->components as $templateComponent) {
                            $component = $templateComponent->component;
                            if (!$component || !$component->is_active) continue;

                            $processedComponentIds[] = $component->id;

                            $amount = isset($specificComponentsMap[$component->id]) 
                                ? $specificComponentsMap[$component->id]->amount 
                                : $component->default_amount;

                            $componentsToInsert[] = [
                                'payroll_item_id' => $item->id,
                                'payroll_component_id' => $component->id,
                                'component_code' => $component->code,
                                'component_name' => $component->name,
                                'component_type' => $component->component_type,
                                'amount' => $amount,
                                'source' => isset($specificComponentsMap[$component->id]) ? 'OVR_EMP' : 'SYSTEM',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];

                            if ($component->component_type === 'earning') {
                                $totalBruto += $amount;
                                $taxableBruto += $amount;
                            } elseif ($component->component_type === 'deduction') {
                                $totalDeduction += $amount;
                                if (in_array($component->id, $excludedComponentIds)) {
                                    $taxableBruto -= $amount;
                                }
                            }
                        }
                    }

                    // Sisa dari specific component (komponen ganda/tambahan)
                    foreach ($specificComponentsMap as $compId => $specComp) {
                        if (!in_array($compId, $processedComponentIds)) {
                            $component = $specComp->component;
                            $componentsToInsert[] = [
                                'payroll_item_id' => $item->id,
                                'payroll_component_id' => $component->id,
                                'component_code' => $component->code,
                                'component_name' => $component->name,
                                'component_type' => $component->component_type,
                                'amount' => $specComp->amount,
                                'source' => 'OVR_ADD',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];

                            if ($component->component_type === 'earning') {
                                $totalBruto += $specComp->amount;
                                $taxableBruto += $specComp->amount;
                            } elseif ($component->component_type === 'deduction') {
                                $totalDeduction += $specComp->amount;
                                if (in_array($component->id, $excludedComponentIds)) {
                                    $taxableBruto -= $specComp->amount;
                                }
                            }
                        }
                    }

                    // Hitung PPh21 untuk employee
                    $pph21Amount = $taxStrategy ? $taxStrategy->calculate($employee, $taxableBruto) : 0;

                    if ($pph21Amount > 0 && $taxComponent) {
                        $componentsToInsert[] = [
                            'payroll_item_id' => $item->id,
                            'payroll_component_id' => $taxComponent->id,
                            'component_code' => $taxComponent->code,
                            'component_name' => $taxComponent->name,
                            'component_type' => 'deduction',
                            'amount' => $pph21Amount,
                            'source' => 'SYSTEM_TAX',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $totalDeduction += $pph21Amount;
                    }
                }

                // 7. Simpan total akhir
                $itemsToUpdate[] = [
                    'id'              => $item->id,
                    'total_bruto'     => $totalBruto,
                    'total_deduction' => $totalDeduction,
                    'total_netto'     => $totalBruto - $totalDeduction,
                ];
            }

            // 8. Bulk Insert Komponen Baru
            foreach (array_chunk($componentsToInsert, 1000) as $chunk) {
                PayrollItemComponent::insert($chunk);
            }

            // 9. Bulk Update kalkulasi Parent Item 
            // Query satu per satu sangat ringan (<50ms per batch 500) karena berada di dalam 1 buah DB transaction
            foreach ($itemsToUpdate as $data) {
                PayrollItem::where('id', $data['id'])->update([
                    'total_bruto'     => $data['total_bruto'],
                    'total_deduction' => $data['total_deduction'],
                    'total_netto'     => $data['total_netto'],
                ]);
            }

            return $period;
        });
    }
}
