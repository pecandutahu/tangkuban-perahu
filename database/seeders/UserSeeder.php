<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // HR Admin
        $hrAdmin = User::create([
            'name' => 'HR Administrator',
            'email' => 'admin@yoursite.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
        $hrAdmin->roles()->attach(Role::where('name', 'HR Admin')->first()->id);

        // Payroll Officer
        $payroll = User::create([
            'name' => 'Payroll Officer',
            'email' => 'payroll@yoursite.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
        $payroll->roles()->attach(Role::where('name', 'Payroll Officer')->first()->id);

        // Approver (Finance/Manager)
        $approver = User::create([
            'name' => 'Finance Approver',
            'email' => 'finance@yoursite.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
        $approver->roles()->attach(Role::where('name', 'Approver')->first()->id);

        // Viewer (Auditor)
        $viewer = User::create([
            'name' => 'Auditor',
            'email' => 'auditor@yoursite.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
        $viewer->roles()->attach(Role::where('name', 'Viewer')->first()->id);
    }
}
