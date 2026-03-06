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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable();
            $table->string('action', 30);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');

            $table->jsonb('before_data')->nullable();
            $table->jsonb('after_data')->nullable();

            $table->timestamps();
        });

        DB::statement("
            CREATE INDEX idx_audit_entity
            ON audit_logs(entity_type, entity_id)
        ");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
