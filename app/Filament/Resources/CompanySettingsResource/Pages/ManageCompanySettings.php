<?php

namespace App\Filament\Resources\CompanySettingsResource\Pages;

use App\Filament\Resources\CompanySettings\Schemas\CompanySettingsForm;
use App\Filament\Resources\CompanySettingsResource;
use App\Models\CompanySetting;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;

class ManageCompanySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CompanySettingsResource::class;

    // Remove static from $view
    protected string $view = 'filament.resources.company-settings.pages.manage-company-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = CompanySetting::first();
        
        if (!$settings) {
            $settings = CompanySetting::create([
                'company_name' => 'Your Company Name',
            ]);
        }

        $this->form->fill($settings->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(CompanySettingsForm::getSchema());
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = CompanySetting::first();
        
        if ($settings) {
            $settings->update($data);
        } else {
            CompanySetting::create($data);
        }

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
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
}
