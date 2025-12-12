<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Jobs\GenerateImportPreviewJob;
use App\Services\AI\FieldMappingService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ConfigureMapping extends Page
{
    use \Filament\Resources\Pages\Concerns\InteractsWithRecord;
    
    protected static string $resource = DocumentImportResource::class;
    
    protected string $view = 'filament.resources.document-imports.pages.configure-mapping';

    public ?array $data = [];
    
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        // Load existing mapping or use AI-suggested mapping
        $mapping = $this->record->column_mapping ?? [];
        
        if (empty($mapping)) {
            // Generate default mapping from AI analysis
            $mapping = $this->generateDefaultMapping();
        }
        
        $this->form->fill(['mapping' => $mapping]);
    }

    public function form(Form $form): Form
    {
        $headers = $this->getHeaders();
        $fieldOptions = FieldMappingService::getFieldOptions();
        
        $mappingFields = [];
        
        foreach ($headers as $column => $label) {
            $mappingFields[] = Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\TextInput::make("mapping.{$column}.label")
                        ->label('Column')
                        ->default($label)
                        ->disabled()
                        ->columnSpan(1),
                    
                    Forms\Components\Select::make("mapping.{$column}.field")
                        ->label('Maps To')
                        ->options($fieldOptions)
                        ->searchable()
                        ->placeholder('Select field...')
                        ->columnSpan(1)
                        ->live(),
                    
                    Forms\Components\TextInput::make("mapping.{$column}.confidence")
                        ->label('AI Confidence')
                        ->suffix('%')
                        ->disabled()
                        ->columnSpan(1),
                ]);
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Column Mapping Configuration')
                    ->description('Map Excel columns to product fields. The AI has suggested mappings based on column headers.')
                    ->schema($mappingFields)
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generatePreview')
                ->label('Generate Preview')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate Import Preview')
                ->modalDescription('This will generate a preview of all products based on the current mapping. You can review and edit them before final import.')
                ->action(function () {
                    // Save mapping
                    $mapping = $this->form->getState()['mapping'] ?? [];
                    
                    $this->record->update([
                        'column_mapping' => $mapping,
                    ]);
                    
                    // Dispatch preview generation job
                    GenerateImportPreviewJob::dispatch($this->record);
                    
                    Notification::make()
                        ->title('Preview Generation Started')
                        ->body('The preview is being generated. This may take a few moments...')
                        ->success()
                        ->send();
                    
                    // Redirect to view page
                    return redirect()->to(
                        DocumentImportResource::getUrl('view', ['record' => $this->record])
                    );
                }),
            
            Action::make('resetMapping')
                ->label('Reset to AI Suggestions')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $mapping = $this->generateDefaultMapping();
                    $this->form->fill(['mapping' => $mapping]);
                    
                    Notification::make()
                        ->title('Mapping Reset')
                        ->body('Mapping has been reset to AI suggestions.')
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Get headers from AI analysis
     */
    protected function getHeaders(): array
    {
        $analysis = $this->record->ai_analysis;
        $headers = $analysis['headers'] ?? [];
        
        // Remove internal fields
        unset($headers['_detected_row']);
        
        return $headers;
    }

    /**
     * Generate default mapping from AI analysis
     */
    protected function generateDefaultMapping(): array
    {
        $aiMapping = $this->record->column_mapping ?? [];
        
        if (!empty($aiMapping)) {
            return $aiMapping;
        }
        
        // If no AI mapping, create empty mapping for all columns
        $headers = $this->getHeaders();
        $mapping = [];
        
        foreach ($headers as $column => $label) {
            $mapping[$column] = [
                'label' => $label,
                'field' => null,
                'confidence' => null,
            ];
        }
        
        return $mapping;
    }

    public function save(): void
    {
        $mapping = $this->form->getState()['mapping'] ?? [];
        
        $this->record->update([
            'column_mapping' => $mapping,
        ]);
        
        Notification::make()
            ->title('Mapping Saved')
            ->body('Column mapping has been saved successfully.')
            ->success()
            ->send();
    }
}
