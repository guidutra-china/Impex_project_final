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

        // Handle logo file upload using Livewire temporary uploads
        if (isset($data['logo_path'])) {
            $logoValue = $data['logo_path'];
            
            \Log::info('Logo value type: ' . gettype($logoValue));
            \Log::info('Logo value: ' . json_encode($logoValue));
            
            // If it's a TemporaryUploadedFile object
            if (is_object($logoValue) && method_exists($logoValue, 'store')) {
                // Store the file permanently
                $path = $logoValue->store('company', 'public');
                $data['logo_path'] = $path;
                \Log::info('Logo stored to: ' . $path);
            }
            // If it's already a string path, keep it
            elseif (is_string($logoValue)) {
                \Log::info('Logo is already a string path: ' . $logoValue);
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
