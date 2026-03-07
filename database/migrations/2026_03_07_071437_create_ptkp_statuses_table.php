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
        Schema::create('ptkp_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Contoh: TK/0, K/1');
            $table->decimal('amount', 15, 2)->comment('Nominal PTKP Terkait per Tahun');
            $table->string('description')->nullable()->comment('Keterangan status PTKP');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ptkp_statuses');
    }
};
