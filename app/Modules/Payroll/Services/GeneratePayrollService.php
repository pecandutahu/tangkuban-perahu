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
                    'created_by' => auth()->id() ?? 'SYSTEM',
                    'status' => 'draft',
                ]);

                // 4. Ambil semua Karyawan aktif
                $employees = Employee::where('is_active', true)->get();

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
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->name,
                        'department_name' => null,
                        'branch_name' => null,
                        'status' => 'draft',
                        'total_bruto' => 0,
                        'total_deduction' => 0,
                        'total_netto' => 0,
                    ]);

                    $totalBruto = 0;
                    $totalDeduction = 0;

                    // 6. Salin semua template komponen ke item
                    foreach ($template->components as $templateComponent) {
                        $component = $templateComponent->component;
                        
                        if (!$component || !$component->is_active) {
                            continue;
                        }

                        $amount = $component->default_amount;

                        PayrollItemComponent::create([
                            'payroll_item_id' => $item->id,
                            'payroll_component_id' => $component->id,
                            'component_code' => $component->code,
                            'component_name' => $component->name,
                            'component_type' => $component->component_type,
                            'amount' => $amount,
                            'source' => 'SYSTEM',
                        ]);

                        if ($component->component_type === 'earning') {
                            $totalBruto += $amount;
                        } elseif ($component->component_type === 'deduction') {
                            $totalDeduction += $amount;
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
}
