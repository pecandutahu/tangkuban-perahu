<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('payroll_period_id')->constrained();
        $table->foreignId('employee_id');

        // SNAPSHOT
        $table->string('employee_name', 150);
        $table->string('department_name', 100)->nullable();
        $table->string('branch_name', 100)->nullable();

        $table->decimal('total_bruto', 15, 2)->default(0);
        $table->decimal('total_deduction', 15, 2)->default(0);
        $table->decimal('total_netto', 15, 2)->default(0);

        $table->string('status', 30)->default('draft');
        $table->timestamps();
    });


    DB::statement("
        CREATE INDEX idx_payroll_items_period
        ON payroll_items(payroll_period_id)
    ");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
