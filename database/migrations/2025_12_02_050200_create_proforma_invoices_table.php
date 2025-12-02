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
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();
            
            // Basic info
            $table->string('proforma_number')->unique();
            $table->integer('revision_number')->default(1);
            
            // Relationships
            $table->foreignId('customer_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained()->nullOnDelete();
            
            // Amounts (in cents)
            $table->bigInteger('subtotal')->default(0); // Sum of all items (with commission)
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('total')->default(0);
            $table->decimal('exchange_rate', 10, 6)->default(1);
            
            // Dates
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->date('due_date')->nullable();
            
            // Status
            $table->enum('status', [
                'draft',
                'sent',
                'approved',
                'rejected',
                'expired',
                'cancelled'
            ])->default('draft');
            
            // Approval tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            
            // Deposit/Payment
            $table->boolean('deposit_required')->default(false);
            $table->bigInteger('deposit_amount')->nullable(); // in cents
            $table->decimal('deposit_percent', 5, 2)->nullable();
            $table->boolean('deposit_received')->default(false);
            $table->timestamp('deposit_received_at')->nullable();
            $table->string('deposit_payment_method')->nullable();
            $table->string('deposit_payment_reference')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('customer_notes')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('customer_id');
            $table->index('status');
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};
