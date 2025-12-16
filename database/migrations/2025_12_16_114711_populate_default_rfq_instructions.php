<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultInstructions = "Please provide your best quotation including:\n\n• Unit price and total price for each item\n• Lead time / delivery time\n• Minimum Order Quantity (MOQ) if applicable\n• Payment terms and conditions\n• Validity period of your quotation\n• Any additional costs (tooling, setup, shipping, etc.)\n\nPlease submit your quotation by the specified deadline.";

        // Update existing records that have null or empty rfq_default_instructions
        DB::table('company_settings')
            ->whereNull('rfq_default_instructions')
            ->orWhere('rfq_default_instructions', '')
            ->update(['rfq_default_instructions' => $defaultInstructions]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - we don't want to remove user's instructions
    }
};
