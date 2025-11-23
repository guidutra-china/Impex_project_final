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
            ]);
        }
    }
}
