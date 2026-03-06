<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeComponent;
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

        $earningComponents = DB::table('payroll_components')->where('component_type', 'earning')
            ->where('code', '!=', 'TJ_FUNC')->pluck('id')->toArray();
        $tjFuncComponentId = DB::table('payroll_components')->where('code', 'TJ_FUNC')->first()->id ?? null;

        $employeeTypes = [
            ['pos' => 'Supir Truck (Driver)', 'pos_prefix' => 'DRV', 'dep' => 'OPS', 'type' => 'permanent', 'count' => 100],
            ['pos' => 'Kernet (Helper)', 'pos_prefix' => 'HLP', 'dep' => 'OPS', 'type' => 'contract', 'count' => 100],
            ['pos' => 'Suku Cadang / Mekanik', 'pos_prefix' => 'MCH', 'dep' => 'MTC', 'type' => 'permanent', 'count' => 50],
            ['pos' => 'Staff / Admin', 'pos_prefix' => 'ADM', 'dep' => 'FIN', 'type' => 'permanent', 'count' => 30],
            ['pos' => 'Supervisor', 'pos_prefix' => 'SPV', 'dep' => 'OPS', 'type' => 'permanent', 'count' => 20],
        ];

        $getDepId = function($code) {
             return DB::table('departments')->where('code', $code)->first()->id ?? null;
        };

        foreach ($employeeTypes as $grup) {
            $depId = $getDepId($grup['dep']);
            $posId = $positions[$grup['pos']] ?? null;

            for ($i = 0; $i < $grup['count']; $i++) {
                $employee = Employee::create([
                    'nik_internal' => $grup['pos_prefix'] . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT) . rand(10, 99),
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

                if (!empty($earningComponents)) {
                    // Random komponen earning lain
                    EmployeeComponent::create([
                        'employee_id' => $employee->id,
                        'payroll_component_id' => $faker->randomElement($earningComponents),
                        'amount' => $faker->numberBetween(10, 100) * 10000, // 100.000 s/d 1.000.000
                        'is_active' => true,
                    ]);
                }

                if ($tjFuncComponentId) {
                    // Pasti dapat Tunjangan Fungsional dengan nilai acak
                    EmployeeComponent::create([
                        'employee_id' => $employee->id,
                        'payroll_component_id' => $tjFuncComponentId,
                        'amount' => $faker->numberBetween(25, 200) * 10000, // 250.000 s/d 2.000.000
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
