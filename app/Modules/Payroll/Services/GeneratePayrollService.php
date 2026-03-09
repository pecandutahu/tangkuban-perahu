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
                // 2. Anti Duplicate: Cek apakah periode dengan tanggal yang beririsan/sama persis sudah pernah ada
                $existingPeriod = PayrollPeriod::where('start_date', $data['start_date'])
                    ->where('end_date', $data['end_date'])
                    ->where('period_type', $data['period_type'])
                    ->first();

                if ($existingPeriod) {
                    // Re-Generate Safe: Tolak jika sudah ada, minta user hapus draft lama dulu
                    throw new \Exception("Periode penggajian ({$data['period_type']}) untuk tanggal {$data['start_date']} s/d {$data['end_date']} sudah pernah dibuat (Status: {$existingPeriod->status}). Silakan hapus draft lama jika ingin menggenerate ulang.");
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

                foreach ($employees as $employee) {
                    // Gunakan Resolver untuk cari template
                    try {
                        $template = PayrollTemplateResolver::resolve($employee);
                    } catch (\DomainException $e) {
                        continue; 
                    }

                    // 5. Buat PayrollItem per karyawan
                    $item = PayrollItem::create([
                        'payroll_period_id' => $period->id,
                        'employee_id'       => $employee->id,
                        'employee_name'     => $employee->name,
                        'department_name'   => $employee->department?->name,
                        'branch_name'       => $employee->branch?->name,
                        'status'            => 'draft',
                        'total_bruto'       => 0,
                        'total_deduction'   => 0,
                        'total_netto'       => 0,
                    ]);

                    $totalBruto = 0;
                    $totalDeduction = 0;

                    // Buat map/kamus dari komponen spesifik untuk kemudahan override
                    $specificComponentsMap = [];
                    foreach ($employee->specificComponents as $specComp) {
                        if ($specComp->is_active && $specComp->component) {
                            $specificComponentsMap[$specComp->component->id] = $specComp;
                        }
                    }

                    $processedComponentIds = [];

                    // 6. Salin semua template komponen ke item
                    foreach ($template->components as $templateComponent) {
                        $component = $templateComponent->component;
                        
                        if (!$component || !$component->is_active) {
                            continue;
                        }

                        $processedComponentIds[] = $component->id;

                        // Gunakan nilai milik Karyawan Khusus jika ada (Override), kalau tidak pakai default_amount Jabatan
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

                        if ($component->component_type === 'earning') {
                            $totalBruto += $amount;
                        } elseif ($component->component_type === 'deduction') {
                            $totalDeduction += $amount;
                        }
                    }

                    // 7. Salin komponen spesifik yang belum tertampung oleh template jabatan (Append)
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

                            if ($component->component_type === 'earning') {
                                $totalBruto += $specComp->amount;
                            } elseif ($component->component_type === 'deduction') {
                                $totalDeduction += $specComp->amount;
                            }
                        }
                    }

                    // Hitung ulang summary item
                    $item->update([
                        'total_bruto' => $totalBruto,
                        'total_deduction' => $totalDeduction,
                        'total_netto' => $totalBruto - $totalDeduction,
                    ]);
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
            
            foreach ($items as $item) {
                // Kita bisa melakukan pemanggilan langsung fungsi regenerateItem 
                // Karena kita sudah ada di dalam db transaction.
                // Parameter kedua adalah itemId. $this->regenerateItem($periodId, $item->id)
                // Wait: regenerateItem also has its own DB::transaction, which is safe in Laravel (nested transaction),
                // tapi alangkah baiknya kita hindari recursive transaction wrap jika perlu, atau panggil saja langsung.
                $this->regenerateItem($periodId, $item->id);
            }

            return $period;
        });
    }
}
