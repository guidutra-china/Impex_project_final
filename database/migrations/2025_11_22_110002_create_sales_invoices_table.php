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
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->integer('revision_number')->default(1);
            
            // Relationships
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->constrained();
            $table->foreignId('base_currency_id')->constrained('currencies');
            
            // Revision control
            $table->foreignId('original_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('superseded_by_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->text('revision_reason')->nullable();
            
            // Dates
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            
            // Financial (stored in cents)
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->bigInteger('subtotal')->default(0)->comment('In cents');
            $table->bigInteger('commission')->default(0)->comment('In cents');
            $table->bigInteger('tax')->default(0)->comment('In cents');
            $table->bigInteger('total')->default(0)->comment('In cents');
            $table->bigInteger('total_base_currency')->default(0)->comment('In cents');
            
            // Status and payment
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled', 'superseded'])->default('draft');
            $table->string('payment_method')->nullable();
            $table->text('payment_reference')->nullable();
            
            // Additional info
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            
            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('status');
            $table->index('client_id');
            $table->index('quote_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
