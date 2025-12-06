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
        Schema::table('commercial_invoice_items', function (Blueprint $table) {
            // Description field
            if (!Schema::hasColumn('commercial_invoice_items', 'description')) {
                $table->text('description')->after('product_id');
            }
            
            // HS Code (Harmonized System code for customs)
            if (!Schema::hasColumn('commercial_invoice_items', 'hs_code')) {
                $table->string('hs_code')->nullable()->after('total');
            }
            
            // Country of origin
            if (!Schema::hasColumn('commercial_invoice_items', 'country_of_origin')) {
                $table->string('country_of_origin', 100)->nullable()->after('hs_code');
            }
            
            // Weight and volume for shipping
            if (!Schema::hasColumn('commercial_invoice_items', 'weight')) {
                $table->decimal('weight', 10, 2)->nullable()->after('country_of_origin')->comment('Weight in kg');
            }
            
            if (!Schema::hasColumn('commercial_invoice_items', 'volume')) {
                $table->decimal('volume', 10, 2)->nullable()->after('weight')->comment('Volume in m³');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commercial_invoice_items', function (Blueprint $table) {
            $columns = [
                'description',
                'hs_code',
                'country_of_origin',
                'weight',
                'volume',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('commercial_invoice_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
