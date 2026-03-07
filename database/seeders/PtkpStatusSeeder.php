<?php

namespace Database\Seeders;

use App\Models\PtkpStatus;
use Illuminate\Database\Seeder;

class PtkpStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['code' => 'TK/0', 'amount' => 54000000, 'description' => 'Tidak Kawin, Tanpa Tanggungan'],
            ['code' => 'TK/1', 'amount' => 58500000, 'description' => 'Tidak Kawin, Tanggungan 1'],
            ['code' => 'TK/2', 'amount' => 63000000, 'description' => 'Tidak Kawin, Tanggungan 2'],
            ['code' => 'TK/3', 'amount' => 67500000, 'description' => 'Tidak Kawin, Tanggungan 3'],
            
            ['code' => 'K/0', 'amount' => 58500000, 'description' => 'Kawin, Tanpa Tanggungan'],
            ['code' => 'K/1', 'amount' => 63000000, 'description' => 'Kawin, Tanggungan 1'],
            ['code' => 'K/2', 'amount' => 67500000, 'description' => 'Kawin, Tanggungan 2'],
            ['code' => 'K/3', 'amount' => 72000000, 'description' => 'Kawin, Tanggungan 3'],
        ];

        foreach ($statuses as $status) {
            PtkpStatus::firstOrCreate(
                ['code' => $status['code']],
                ['amount' => $status['amount'], 'description' => $status['description']]
            );
        }
    }
}
