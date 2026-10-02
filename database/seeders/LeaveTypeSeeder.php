<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        LeaveType::updateOrCreate(
            ['code' => 'ANNUAL'],
            [
                'name' => 'Cuti Tahunan',
                'category' => 'leave',
                'deduct_balance' => true,
                'is_active' => true,
            ]
        );

        LeaveType::updateOrCreate(
            ['code' => 'PERMIT'],
            [
                'name' => 'Izin',
                'category' => 'permit',
                'deduct_balance' => false,
                'is_active' => true,
            ]
        );
    }
}
