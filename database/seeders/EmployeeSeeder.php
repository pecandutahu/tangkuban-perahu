<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $branches = DB::table('branch')->pluck('id')->toArray();
        $deps = DB::table('departments')->pluck('id')->toArray();
        $positions = DB::table('positions')->pluck('id', 'name')->toArray(); // ['Supervisor' => 1, ...]

        $employeeTypes = [
            ['pos' => 'Supir Truck (Driver)', 'pos_prefix' => 'DRV', 'dep' => 'OPS', 'type' => 'permanent', 'count' => 10],
            ['pos' => 'Kernet (Helper)', 'pos_prefix' => 'HLP', 'dep' => 'OPS', 'type' => 'contract', 'count' => 10],
            ['pos' => 'Suku Cadang / Mekanik', 'pos_prefix' => 'MCH', 'dep' => 'MTC', 'type' => 'permanent', 'count' => 5],
            ['pos' => 'Staff / Admin', 'pos_prefix' => 'ADM', 'dep' => 'FIN', 'type' => 'permanent', 'count' => 3],
            ['pos' => 'Supervisor', 'pos_prefix' => 'SPV', 'dep' => 'OPS', 'type' => 'permanent', 'count' => 2],
        ];

        $getDepId = function($code) {
             return DB::table('departments')->where('code', $code)->first()->id ?? null;
        };

        foreach ($employeeTypes as $grup) {
            $depId = $getDepId($grup['dep']);
            $posId = $positions[$grup['pos']] ?? null;

            for ($i = 0; $i < $grup['count']; $i++) {
                Employee::create([
                    'nik_internal' => $grup['pos_prefix'] . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'name' => $faker->name,
                    'is_active' => true,
                    'join_date' => $faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
                    'payment_method' => $faker->randomElement(['transfer', 'cash']),
                    'bank_name' => 'BCA',
                    'bank_account' => $faker->numerify('##########'),
                    'employment_type' => $grup['type'],
                    'branch_id' => $faker->randomElement($branches),
                    'department_id' => $depId,
                    'position_id' => $posId,
                ]);
            }
        }
    }
}
