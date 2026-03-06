<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\PayrollItem;
use App\Modules\Payroll\Services\VariableImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VariableImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_import_csv_and_recalculate()
    {
        $employee = Employee::create([
            'nik_internal' => 'DRV-001',
            'name' => 'Driver 1',
            'employment_type' => 'contract',
            'join_date' => '2025-01-01',
            'payment_method' => 'cash',
        ]);

        $period = PayrollPeriod::create([
            'code' => 'PRL-TEST',
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
            'total_bruto' => 0,
            'total_deduction' => 0,
            'total_netto' => 0,
        ]);

        PayrollComponent::create([
            'code' => 'LMBR',
            'name' => 'Lembur',
            'component_type' => 'earning',
        ]);

        PayrollComponent::create([
            'code' => 'KSBN',
            'name' => 'Kasbon',
            'component_type' => 'deduction',
        ]);

        // Buat mock CSV file
        $csvContent = "NIK,Komponen,Nominal\nDRV-001,LMBR,500000\nDRV-001,KSBN,100000";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $service = new VariableImportService();
        $result = $service->import($period->id, $file);

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('payroll_item_components', [
            'payroll_item_id' => $item->id,
            'component_code' => 'LMBR',
            'amount' => 500000,
        ]);

        // Cek kalkulasi bruto dan netto
        $this->assertDatabaseHas('payroll_items', [
            'id' => $item->id,
            'total_bruto' => 500000,
            'total_deduction' => 100000,
            'total_netto' => 400000,
        ]);
    }
}
