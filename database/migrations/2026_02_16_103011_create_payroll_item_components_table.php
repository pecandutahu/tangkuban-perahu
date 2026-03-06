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
        Schema::create('payroll_item_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_item_id')->constrained();
            $table->foreignId('payroll_component_id');

            // SNAPSHOT
            $table->string('component_code', 50);
            $table->string('component_name', 100);
            $table->string('component_type', 30);

            $table->decimal('amount', 15, 2);
            $table->string('source', 10); // SYSTEM | MANUAL | IMPORT

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_item_components');
    }
};
