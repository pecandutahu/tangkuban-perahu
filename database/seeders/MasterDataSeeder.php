<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class, // Admin awal
            CompanySeeder::class,
            PtkpStatusSeeder::class,
            PayrollComponentSeeder::class,
            PayrollTemplateSeeder::class,
            Pph21SettingSeeder::class,
            BpjsSettingSeeder::class,
        ]);
        
        $this->command->info('Data master inti berhasil di-seed (Tanpa Karyawan Dummy).');
    }
}
