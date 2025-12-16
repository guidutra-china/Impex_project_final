<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only create if no settings exist
        if (CompanySetting::count() === 0) {
            CompanySetting::create([
                'company_name' => 'Your Company Name',
                'address' => '123 Business Street',
                'city' => 'City',
                'state' => 'State',
                'zip_code' => '12345',
                'country' => 'Country',
                'phone' => '(123) 456-7890',
                'email' => 'info@yourcompany.com',
                'website' => 'https://www.yourcompany.com',
                'tax_id' => 'TAX123456',
                'registration_number' => 'REG123456',
                'bank_name' => 'Your Bank Name',
                'bank_account_number' => '1234567890',
                'bank_routing_number' => '123456789',
                'bank_swift_code' => 'SWIFT123',
                'footer_text' => 'Thank you for your business!',
                'invoice_prefix' => 'INV',
                'quote_prefix' => 'QT',
                'po_prefix' => 'PO',
                'rfq_default_instructions' => "Please provide your best quotation including:\n\n• Unit price and total price for each item\n• Lead time / delivery time\n• Minimum Order Quantity (MOQ) if applicable\n• Payment terms and conditions\n• Validity period of your quotation\n• Any additional costs (tooling, setup, shipping, etc.)\n\nPlease submit your quotation by the specified deadline.",
                'po_terms' => "Standard Purchase Order Terms:\n\n1. Payment terms as agreed\n2. Delivery as per schedule\n3. Quality inspection upon receipt\n4. Warranty as specified\n5. Compliance with all applicable regulations",
                'packing_list_prefix' => 'PL',
                'commercial_invoice_prefix' => 'CI',
            ]);
        }
    }
}
