<?php

namespace App\Console\Commands;

use App\Models\PayrollItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPayrollItemLocationsCommand extends Command
{
    protected $signature   = 'payroll:backfill-locations
                                {--period= : Hanya update item dari period ID tertentu}
                                {--dry-run : Tampilkan berapa baris yang akan diupdate tanpa eksekusi}';

    protected $description = 'Isi kolom branch_name dan department_name pada payroll_items berdasarkan data employee saat ini.';

    public function handle(): int
    {
        $this->info('📦 Backfill branch_name & department_name pada payroll_items...');

        $query = DB::table('payroll_items as pi')
            ->join('employees as e', 'e.id', '=', 'pi.employee_id')
            ->leftJoin('departments as d', 'd.id', '=', 'e.department_id')
            ->leftJoin('branch as b', 'b.id', '=', 'e.branch_id')
            ->whereNull('pi.branch_name')
            ->orWhereNull('pi.department_name');

        if ($this->option('period')) {
            $query->where('pi.payroll_period_id', $this->option('period'));
        }

        $count = $query->count();
        $this->info("  → Ditemukan {$count} baris yang perlu diupdate.");

        if ($this->option('dry-run')) {
            $this->warn('  → [dry-run] Tidak ada yang diubah.');
            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info('  ✅ Semua data sudah terisi. Tidak ada yang perlu diupdate.');
            return self::SUCCESS;
        }

        // Update semua payroll_items yang null menggunakan JOIN
        // Note: menggunakan raw query karena Laravel Query Builder UpdateFrom (join-update)
        $affected = DB::statement("
            UPDATE payroll_items pi
            SET
                branch_name     = b.name,
                department_name = d.name,
                updated_at      = NOW()
            FROM employees e
            LEFT JOIN branch b ON b.id = e.branch_id
            LEFT JOIN departments d ON d.id = e.department_id
            WHERE pi.employee_id = e.id
              AND (pi.branch_name IS NULL OR pi.department_name IS NULL)
            " . ($this->option('period') ? "AND pi.payroll_period_id = " . (int)$this->option('period') : "")
        );

        $this->info("  ✅ Selesai. Baris diperbarui.");
        $this->line('');
        $this->info('💡 Tips: Jalankan tanpa --period untuk update seluruh data.');

        return self::SUCCESS;
    }
}
