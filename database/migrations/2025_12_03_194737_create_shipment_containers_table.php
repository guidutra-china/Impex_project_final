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
        Schema::create('shipment_containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('container_number')->unique();
            $table->enum('container_type', ['20ft', '40ft', '40hc', 'pallet', 'box'])->default('40ft');
            $table->decimal('max_weight', 10, 2);
            $table->decimal('max_volume', 12, 4);
            $table->decimal('current_weight', 10, 2)->default(0);
            $table->decimal('current_volume', 12, 4)->default(0);
            $table->enum('status', ['draft', 'packed', 'sealed', 'in_transit', 'delivered'])->default('draft');
            $table->string('seal_number')->nullable();
            $table->timestamp('sealed_at')->nullable();
            $table->foreignId('sealed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shipment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_containers');
    }
};
