<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Modules\Payroll\Services\BpjsCalculatorService;

class SyncBpjsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:sync-bpjs {--force : Jalankan sinkronisasi tanpa konfirmasi konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menjalankan sinkronisasi massal komponen BPJS untuk seluruh data karyawan aktif.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Memulai sinkronisasi massal (Hybrid) komponen BPJS karyawan...");

        $employees = Employee::where('is_active', true)->get();
        $total = $employees->count();

        if ($total === 0) {
            $this->warn("Tidak ada data karyawan aktif untuk disinkronisasi.");
            return;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Ditemukan {$total} karyawan aktif. Lanjutkan perhitungan massal?")) {
                $this->info("Sinkronisasi dibatalkan.");
                return;
            }
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($employees as $employee) {
            // Ambil array override saat ini agar tak tertimpa hilang
            $existingOverrides = [];
            foreach ($employee->specificComponents()->get() as $sc) {
                // Kita kecualikan BPJS lama karena akan di-generate ulang
                $code = $sc->component->code ?? '';
                if (!in_array($code, ['BPJS_KES', 'BPJS_TK', 'BPJ'])) {
                    $existingOverrides[] = [
                        'payroll_component_id' => $sc->payroll_component_id,
                        'amount' => $sc->amount,
                    ];
                }
            }

            // Minta Service Hitung Angka BPJS yang Benar
            $finalComponents = BpjsCalculatorService::calculateForAdmin($employee, $existingOverrides);

            // Tembak balikan ke Master Profil
            $employee->specificComponents()->delete();
            if (!empty($finalComponents)) {
                foreach ($finalComponents as $comp) {
                    $employee->specificComponents()->create([
                        'payroll_component_id' => $comp['payroll_component_id'],
                        'amount' => $comp['amount'],
                        'is_active' => true,
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n✅ Berhasil mensinkronisasi komponen BPJS untuk {$total} karyawan.");
    }
}
