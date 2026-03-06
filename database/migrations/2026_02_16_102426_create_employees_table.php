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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->string('nik_internal', 50)->unique();
            $table->string('name', 150);

            $table->text('ktp_number')->nullable();
            $table->text('npwp_number')->nullable();
            $table->string('ptkp_status', 10)->nullable();

            $table->foreignId('department_id')->nullable();
            $table->foreignId('position_id')->nullable();
            $table->foreignId('branch_id')->nullable();

            $table->string('employment_type', 50);
            $table->date('join_date');
            $table->date('resign_date')->nullable();

            $table->boolean('is_active')->default(true);

            $table->string('payment_method', 10);
            $table->string('bank_name', 50)->nullable();
            $table->text('bank_account')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
