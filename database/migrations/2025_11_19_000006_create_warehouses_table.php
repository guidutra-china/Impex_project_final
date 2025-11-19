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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            
            // === IDENTIFICAÇÃO ===
            $table->string('code', 50)->unique()->comment('Ex: WH-001');
            $table->string('name');
            
            // === TIPO ===
            $table->enum('type', ['owned', 'rented', 'third_party'])->default('owned');
            
            // === ENDEREÇO ===
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            
            // === CONTATO ===
            $table->string('contact_person')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email')->nullable();
            
            // === CAPACIDADE ===
            $table->decimal('total_capacity', 10, 2)->nullable()->comment('Total capacity (m³)');
            $table->decimal('used_capacity', 10, 2)->default(0)->comment('Used capacity (m³)');
            
            // === STATUS ===
            $table->boolean('is_active')->default(true);
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
