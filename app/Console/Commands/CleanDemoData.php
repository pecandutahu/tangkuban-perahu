<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class CleanDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan data karyawan, user, dan riwayat payroll dummy agar sistem siap digunakan di tahap Production/Live.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('=========== PERINGATAN REFRESH DATA ===========');
        $this->warn('Perintah ini akan MENGHAPUS PERMANEN tipe data berikut:');
        $this->line('1. Seluruh Data Karyawan');
        $this->line('2. Seluruh Riwayat Payroll Period, Detail Item, & Slip Gaji');
        $this->line('3. Custom Override data BPJS per karyawan');
        $this->line('');
        $this->info('Akun User / Login, Data Master (Cabang, Departemen, Jabatan, Role, Template) AKAN DIPERTAHANKAN.');
        $this->line('');
        
        if (!$this->confirm('YAKIN ingin mereset/menghapus 500+ data demo sekarang?')) {
            $this->info('Proses dibatalkan. Tidak ada file yang diubah.');
            return;
        }

        try {
            DB::transaction(function () {
                $this->info('--> Mematikan proteksi Foreign Key sementara...');
                Schema::disableForeignKeyConstraints();

                // 1. Bersihkan transaksi payroll
                $this->info('--> Menghapus histori dan beban Payroll...');
                if (Schema::hasTable('payroll_item_components')) {
                    DB::table('payroll_item_components')->truncate();
                }
                if (Schema::hasTable('payroll_audit_logs')) {
                    DB::table('payroll_audit_logs')->truncate();
                }
                if (Schema::hasTable('payroll_items')) {
                    DB::table('payroll_items')->truncate();
                }
                if (Schema::hasTable('payroll_periods')) {
                    DB::table('payroll_periods')->truncate();
                }
                
                // 2. Bersihkan personalia
                $this->info('--> Menghapus Data Karyawan & Komponen Spesifik...');
                if (Schema::hasTable('employee_specific_components')) {
                    DB::table('employee_specific_components')->truncate();
                }
                if (Schema::hasTable('employee_components')) {
                    DB::table('employee_components')->truncate();
                }
                if (Schema::hasTable('employees')) {
                    DB::table('employees')->truncate();
                }
                
                // 3. (Penyesuaian) Users dipertahankan seusai permintaan
                $this->info('--> Data User Account (Login) dipertahankan sesuai permintaan.');

                $this->info('--> Menghidupkan kembali proteksi Foreign Key...');
                Schema::enableForeignKeyConstraints();
            });

            $this->info("\n✅ BERHASIL! Database telah bersih. Sistem siap digunakan untuk diisi data karyawan yang sebenarnya.");
        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints();
            $this->error('Gagal saat menghapus data: ' . $e->getMessage());
        }
    }
}
