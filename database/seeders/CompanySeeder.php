<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Branches
        $branches = [
            ['code' => 'JKT', 'name' => 'Kantor Pusat Jakarta', 'created_at' => now()],
            ['code' => 'SBY', 'name' => 'Pool Trucking Surabaya', 'created_at' => now()],
            ['code' => 'SMG', 'name' => 'Pool Semarang', 'created_at' => now()],
        ];
        DB::table('branch')->insert($branches);

        // 2. Departments
        $departments = [
            ['code' => 'OPS', 'name' => 'Operasional', 'created_at' => now()],
            ['code' => 'MTC', 'name' => 'Maintenance & Mekanik', 'created_at' => now()],
            ['code' => 'FIN', 'name' => 'Finance & Accounting', 'created_at' => now()],
            ['code' => 'HRD', 'name' => 'Human Resources', 'created_at' => now()],
        ];
        DB::table('departments')->insert($departments);

        // 3. Positions
        $positions = [
            ['name' => 'Supervisor', 'created_at' => now()],
            ['name' => 'Staff / Admin', 'created_at' => now()],
            ['name' => 'Supir Truck (Driver)', 'created_at' => now()],
            ['name' => 'Kernet (Helper)', 'created_at' => now()],
            ['name' => 'Suku Cadang / Mekanik', 'created_at' => now()],
        ];
        DB::table('positions')->insert($positions);
    }
}
