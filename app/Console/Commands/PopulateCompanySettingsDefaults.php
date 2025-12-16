<?php

namespace App\Console\Commands;

use App\Models\CompanySetting;
use Illuminate\Console\Command;

class PopulateCompanySettingsDefaults extends Command
{
    protected $signature = 'company:populate-defaults';
    protected $description = 'Populate default values for company settings fields';

    public function handle()
    {
        $this->info('Populating company settings defaults...');

        $defaultInstructions = "Please provide your best quotation including:\n\n• Unit price and total price for each item\n• Lead time / delivery time\n• Minimum Order Quantity (MOQ) if applicable\n• Payment terms and conditions\n• Validity period of your quotation\n• Any additional costs (tooling, setup, shipping, etc.)";

        $defaultPoTerms = "Standard Purchase Order Terms:\n\n1. Payment terms as agreed\n2. Delivery as per schedule\n3. Quality inspection upon receipt\n4. Warranty as specified\n5. Compliance with all applicable regulations";

        $settings = CompanySetting::first();

        if (!$settings) {
            $this->error('No company settings found!');
            return 1;
        }

        $updated = false;

        // Check and update rfq_default_instructions
        if (empty($settings->rfq_default_instructions)) {
            $settings->rfq_default_instructions = $defaultInstructions;
            $this->info('✓ Updated rfq_default_instructions');
            $updated = true;
        } else {
            $this->comment('- rfq_default_instructions already has content (length: ' . strlen($settings->rfq_default_instructions) . ')');
        }

        // Check and update po_terms
        if (empty($settings->po_terms)) {
            $settings->po_terms = $defaultPoTerms;
            $this->info('✓ Updated po_terms');
            $updated = true;
        } else {
            $this->comment('- po_terms already has content');
        }

        // Check and update packing_list_prefix
        if (empty($settings->packing_list_prefix)) {
            $settings->packing_list_prefix = 'PL';
            $this->info('✓ Updated packing_list_prefix');
            $updated = true;
        } else {
            $this->comment('- packing_list_prefix already set: ' . $settings->packing_list_prefix);
        }

        // Check and update commercial_invoice_prefix
        if (empty($settings->commercial_invoice_prefix)) {
            $settings->commercial_invoice_prefix = 'CI';
            $this->info('✓ Updated commercial_invoice_prefix');
            $updated = true;
        } else {
            $this->comment('- commercial_invoice_prefix already set: ' . $settings->commercial_invoice_prefix);
        }

        if ($updated) {
            $settings->save();
            $this->info("\n✅ Company settings updated successfully!");
        } else {
            $this->info("\n✅ All fields already have values - nothing to update");
        }

        return 0;
    }
}
