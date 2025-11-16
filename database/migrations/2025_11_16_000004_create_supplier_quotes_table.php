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
        Schema::create('supplier_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('quote_number')->unique()->nullable();
            $table->integer('revision_number')->default(1);
            $table->boolean('is_latest')->default(true);
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected'])->default('draft');
            
            // Prices in cents (integers)
            $table->integer('total_price_before_commission')->unsigned()->default(0);
            $table->integer('total_price_after_commission')->unsigned()->default(0);
            $table->integer('commission_amount')->unsigned()->default(0);
            
            // Exchange rate locking
            $table->decimal('locked_exchange_rate', 18, 8)->nullable();
            $table->date('locked_exchange_rate_date')->nullable();
            
            $table->enum('commission_type', ['embedded', 'separate'])->nullable(); // override order commission type
            $table->date('valid_until')->nullable();
            $table->integer('validity_days')->default(30);
            
            $table->text('supplier_notes')->nullable(); // from supplier
            $table->text('notes')->nullable(); // internal notes
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['order_id', 'supplier_id']);
            $table->index('status');
            $table->index('quote_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_quotes');
    }
};
