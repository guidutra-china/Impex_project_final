<?php

namespace App\Filament\Resources\CompanySettingsResource\Pages;

use App\Filament\Resources\CompanySettings\Schemas\CompanySettingsForm;
use App\Filament\Resources\CompanySettingsResource\CompanySettingsResource;
use App\Models\CompanySetting;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;

class ManageCompanySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CompanySettingsResource::class;

    protected string $view = 'filament.resources.company-settings.pages.manage-company-settings';

    // Define all form fields as public properties for Livewire
    public ?string $company_name = null;
    public mixed $logo_path = null;
    public ?string $address = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $zip_code = null;
    public ?string $country = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $website = null;
    public ?string $tax_id = null;
    public ?string $registration_number = null;
    public ?string $bank_name = null;
    public ?string $bank_account_number = null;
    public ?string $bank_routing_number = null;
    public ?string $bank_swift_code = null;
    public ?string $footer_text = null;
    public ?string $invoice_prefix = null;
    public ?string $quote_prefix = null;
    public ?string $po_prefix = null;

    public function mount(): void
    {
        $settings = CompanySetting::first();
        
        if (!$settings) {
            $settings = CompanySetting::create([
                'company_name' => 'Your Company Name',
                'invoice_prefix' => 'INV',
                'quote_prefix' => 'QT',
                'po_prefix' => 'PO',
            ]);
        }

        // Fill the form with settings data
        $this->form->fill($settings->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(CompanySettingsForm::getSchema());
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Handle logo file upload manually
        if (isset($data['logo_path']) && is_string($data['logo_path'])) {
            $tempPath = $data['logo_path'];
            
            // Check if file exists in livewire-tmp
            $livewireTempPath = storage_path('app/livewire-tmp/' . $tempPath);
            
            if (file_exists($livewireTempPath)) {
                // Generate new filename
                $filename = basename($tempPath);
                $destinationPath = 'company/' . $filename;
                
                // Ensure company directory exists
                $companyDir = storage_path('app/public/company');
                if (!is_dir($companyDir)) {
                    mkdir($companyDir, 0755, true);
                }
                
                // Copy file to public storage
                $destination = storage_path('app/public/' . $destinationPath);
                copy($livewireTempPath, $destination);
                
                // Update data with final path
                $data['logo_path'] = $destinationPath;
                
                \Log::info('Logo moved from temp to: ' . $destination);
            } else {
                \Log::warning('Livewire temp file not found: ' . $livewireTempPath);
            }
        }

        // Debug: Log what's being saved
        \Log::info('Company Settings Save Data:', $data);

        $settings = CompanySetting::first();
        
        if ($settings) {
            $settings->update($data);
        } else {
            CompanySetting::create($data);
        }

        // Debug: Log what was actually saved
        \Log::info('Company Settings After Save:', $settings->fresh()->toArray());

        Notification::make()
            ->success()
            ->title('Settings Saved')
            ->body('Company settings have been updated successfully.')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('common.save'))
                ->action('save'),
        ];
    }
}
