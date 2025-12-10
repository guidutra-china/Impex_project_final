<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Repositories\ProductRepository;
use App\Services\ProductImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected ?ProductRepository $productRepository = null;

    public function mount(): void
    {
        parent::mount();
        $this->productRepository = app(ProductRepository::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            
            Action::make('import_from_excel')
                ->label(__('common.import'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel File')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel'
                        ])
                        ->maxSize(10240) // 10MB
                        ->required()
                        ->helperText('Upload Excel file with product data. Supports photos via URLs or embedded images.'),
                ])
                ->action(function (array $data) {
                    $importService = app(ProductImportService::class);
                    
                    // Get the actual file path from Livewire temporary upload
                    $filePath = storage_path('app/' . $data['file']);
                    
                    try {
                        $result = $importService->importFromExcel($filePath);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->success()
                                ->title('Import Successful')
                                ->body($result['message'])
                                ->send();
                        } else {
                            $errorMessage = $result['message'];
                            if (!empty($result['errors'])) {
                                $errorMessage .= "\n\nErrors:\n" . implode("\n", array_slice($result['errors'], 0, 5));
                                if (count($result['errors']) > 5) {
                                    $errorMessage .= "\n... and " . (count($result['errors']) - 5) . " more";
                                }
                            }
                            
                            Notification::make()
                                ->warning()
                                ->title('Import Completed with Errors')
                                ->body($errorMessage)
                                ->send();
                        }
                        
                        // Show warnings if any
                        if (!empty($result['warnings'])) {
                            $warningMessage = "Warnings:\n" . implode("\n", array_slice($result['warnings'], 0, 5));
                            if (count($result['warnings']) > 5) {
                                $warningMessage .= "\n... and " . (count($result['warnings']) - 5) . " more";
                            }
                            
                            Notification::make()
                                ->info()
                                ->title('Import Warnings')
                                ->body($warningMessage)
                                ->send();
                        }
                        
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->send();
                    } finally {
                        // Clean up uploaded file
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                    }
                }),
        ];
    }

    /**
     * Override getEloquentQuery to use the repository for filtering and searching.
     * This allows the repository to handle the query logic while Filament handles the UI.
     */
    protected function getEloquentQuery(): Builder
    {
        // Get the base query from the model
        $query = parent::getEloquentQuery();
        
        // Apply any repository-specific filters if needed
        // For now, we're maintaining the default behavior
        return $query;
    }
}
