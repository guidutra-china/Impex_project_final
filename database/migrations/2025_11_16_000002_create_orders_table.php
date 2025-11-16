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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->enum('status', ['pending', 'processing', 'quoted', 'completed', 'cancelled'])->default('pending');
            $table->decimal('commission_percent', 4, 2)->default(5.00); // max 99.99%
            $table->enum('commission_type', ['embedded', 'separate'])->default('embedded');
            $table->text('customer_notes')->nullable(); // original customer request
            $table->text('notes')->nullable(); // internal notes
            $table->integer('total_amount')->unsigned()->default(0); // best quote total (cents)
            $table->unsignedBigInteger('selected_quote_id')->nullable(); // FK added later to avoid circular dependency
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
            $table->index('order_number');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
