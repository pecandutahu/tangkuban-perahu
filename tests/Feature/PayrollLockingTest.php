<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Modules\Payroll\Services\PayrollStatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollLockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_edit_item_when_period_is_approved()
    {
        $employee = Employee::create([
            'nik_internal' => 'EMP-X',
            'name' => 'Mr. X',
            'employment_type' => 'permanent',
            'join_date' => '2025-01-01',
            'payment_method' => 'bank',
        ]);

        $period = PayrollPeriod::create([
            'code' => 'PRL-X',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
            'pay_date' => '2026-02-25',
            'period_type' => 'monthly',
            'created_by' => 1,
            'status' => 'draft',
        ]);

        $item = PayrollItem::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'status' => 'draft',
        ]);

        // Transisi ke Reviewed -> Approved
        $service = new PayrollStatusTransitionService();
        $service->markAsReviewed($period->id);
        $service->markAsApproved($period->id, 99); // 99 is approver ID

        // Cek DB Status berubah
        $this->assertDatabaseHas('payroll_periods', [
            'id' => $period->id,
            'status' => 'approved',
        ]);

        // Ekspektasi Exception
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Immutable Guard");

        // Gagal mengupdate item karena status sudah approved
        $item->update([
            'total_bruto' => 999999
        ]);
    }
}
