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
        Schema::create('payroll_template_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_template_id')->constrained();
            $table->foreignId('payroll_component_id')->constrained();
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_template_id', 'payroll_component_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_template_components');
    }
};
