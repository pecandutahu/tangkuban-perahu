<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollTemplate;
use App\Models\PayrollTemplateComponent;
use App\Models\User;
use App\Modules\Payroll\Services\GeneratePayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_draft_payroll_for_active_employees()
    {
        // Setup Karyawan
        $employee1 = Employee::create([
            'nik_internal' => 'EMP-001',
            'name' => 'John Doe',
            'employment_type' => 'permanent',
            'join_date' => '2025-01-01',
            'payment_method' => 'bank',
            'is_active' => true,
        ]);

        $employee2 = Employee::create([
            'nik_internal' => 'EMP-002',
            'name' => 'Jane Doe',
            'employment_type' => 'permanent',
            'join_date' => '2025-01-01',
            'payment_method' => 'bank',
            'is_active' => false, // Inactive!
        ]);

        // Setup Komponen
        $gajiPokok = PayrollComponent::create([
            'code' => 'GP',
            'name' => 'Gaji Pokok',
            'component_type' => 'earning',
            'default_amount' => 5000000,
        ]);

        // Setup Template
        $template = PayrollTemplate::create([
            'name' => 'Template Tetap',
            'employment_type' => 'permanent',
        ]);

        PayrollTemplateComponent::create([
            'payroll_template_id' => $template->id,
            'payroll_component_id' => $gajiPokok->id,
        ]);

        // Execution
        $service = new GeneratePayrollService();
        $period = $service->generate([
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'pay_date' => '2026-01-25',
            'period_type' => 'monthly',
        ]);

        // Assertion
        $this->assertDatabaseHas('payroll_periods', [
            'id' => $period->id,
            'status' => 'draft',
        ]);

        // Hanya employee 1 yang di-generate karena employee 2 tidak aktif
        $this->assertDatabaseCount('payroll_items', 1);
        
        $this->assertDatabaseHas('payroll_items', [
            'payroll_period_id' => $period->id,
            'employee_id' => $employee1->id,
            'total_bruto' => 5000000,
            'total_netto' => 5000000,
            'status' => 'draft',
        ]);
    }
}
