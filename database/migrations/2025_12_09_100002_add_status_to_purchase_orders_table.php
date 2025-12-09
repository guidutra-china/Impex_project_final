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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('purchase_orders', 'status')) {
                $table->enum('status', ['draft', 'sent', 'confirmed', 'received', 'paid', 'cancelled'])
                    ->default('draft')
                    ->after('actual_delivery_date');
            }
            
            // Add incoterm column if it doesn't exist
            if (!Schema::hasColumn('purchase_orders', 'incoterm')) {
                $table->enum('incoterm', ['EXW', 'FCA', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP', 'FAS', 'FOB', 'CFR', 'CIF'])
                    ->nullable()
                    ->after('total_base_currency');
            }
            
            // Modify columns to have proper defaults
            if (Schema::hasColumn('purchase_orders', 'revision_number')) {
                $table->integer('revision_number')->default(1)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'exchange_rate')) {
                $table->decimal('exchange_rate', 10, 2)->nullable()->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'subtotal')) {
                $table->bigInteger('subtotal')->default(0)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'shipping_cost')) {
                $table->bigInteger('shipping_cost')->default(0)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'insurance_cost')) {
                $table->bigInteger('insurance_cost')->default(0)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'other_costs')) {
                $table->bigInteger('other_costs')->default(0)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'discount')) {
                $table->bigInteger('discount')->default(0)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'tax')) {
                $table->bigInteger('tax')->default(0)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'total')) {
                $table->bigInteger('total')->default(0)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'total_base_currency')) {
                $table->bigInteger('total_base_currency')->default(0)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'shipping_included_in_price')) {
                $table->boolean('shipping_included_in_price')->default(false)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'insurance_included_in_price')) {
                $table->boolean('insurance_included_in_price')->default(false)->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'payment_terms_text')) {
                $table->text('payment_terms_text')->nullable()->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'po_date')) {
                $table->date('po_date')->nullable()->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'notes')) {
                $table->text('notes')->nullable()->change();
            }
            
            if (Schema::hasColumn('purchase_orders', 'terms_and_conditions')) {
                $table->text('terms_and_conditions')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'status')) {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('purchase_orders', 'incoterm')) {
                $table->dropColumn('incoterm');
            }
        });
    }
};
