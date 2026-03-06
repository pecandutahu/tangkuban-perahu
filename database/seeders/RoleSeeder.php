<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create permissions
        $permissions = [
            'view-master-data',
            'create-master-data',
            'edit-master-data',
            'delete-master-data',
            // Payroll Permissions
            'view-payroll',
            'generate-payroll',
            'edit-payroll',
            'delete-payroll',
            'approve-payroll',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 2. Create Roles and assign existing permissions
        $hrAdmin = Role::firstOrCreate(['name' => 'HR Admin']);
        // HR Admin can do everything
        $hrAdmin->syncPermissions($permissions);

        $payrollOfficer = Role::firstOrCreate(['name' => 'Payroll Officer']);
        // Payroll Officer can manage master data and do basic payroll operations but CANNOT approve
        $payrollOfficer->syncPermissions([
            'view-master-data',
            'view-payroll',
            'generate-payroll',
            'edit-payroll',
            'delete-payroll'
        ]);

        $approver = Role::firstOrCreate(['name' => 'Approver']);
        // Approver can only view results and approve them
        $approver->syncPermissions([
            'view-master-data',
            'view-payroll',
            'approve-payroll'
        ]);

        Role::firstOrCreate(['name' => 'Viewer']);
    }
}
