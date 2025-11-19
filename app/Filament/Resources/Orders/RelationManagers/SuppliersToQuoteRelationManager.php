<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\SupplierQuote;
use App\Models\RFQSupplierStatus;
use App\Services\RFQExcelService;
use App\Services\SupplierQuoteImportService;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class SuppliersToQuoteRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierStatuses';

    protected static ?string $title = 'Suppliers to Quote';


    /**
     * Get the table query for suppliers matching RFQ tags
     */
    public function table(Table $table): Table
    {
        return $table
            ->heading('Suppliers Matching RFQ Tags')
            ->description('Send quotation requests to suppliers with matching tags')
            ->query(function () {
                /** @var Order $owner */
                $owner = $this->getOwnerRecord();

                // Get tag IDs from the RFQ
                $tagIds = $owner->tags()->pluck('tags.id');

                if ($tagIds->isEmpty()) {
                    // No tags, return empty query
                    return Supplier::query()->whereRaw('1 = 0');
                }

                // Find suppliers with matching tags
                return Supplier::query()
                    ->whereHas('tags', function ($q) use ($tagIds) {
                        $q->whereIn('tags.id', $tagIds);
                    })
                    ->distinct();
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Supplier Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->color('info'),

                TextColumn::make('rfq_status')
                    ->label('Send Status')
                    ->badge()
                    ->state(function (Supplier $record): string {
                        $status = $this->getSupplierStatus($record);
                        return $status ? $status->getStatusLabel() : 'Not Sent';
                    })
                    ->color(function (Supplier $record): string {
                        $status = $this->getSupplierStatus($record);
                        return $status && $status->isSent() ? 'success' : 'gray';
                    }),

                TextColumn::make('sent_at')
                    ->label('Sent Date')
                    ->state(function (Supplier $record): ?string {
                        $status = $this->getSupplierStatus($record);
                        return $status && $status->sent_at
                            ? $status->sent_at->format('M d, Y H:i')
                            : null;
                    })
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('sent')
                    ->label('Sent')
                    ->query(function (Builder $query) {
                        /** @var Order $owner */
                        $owner = $this->getOwnerRecord();

                        return $query->whereHas('rfqStatuses', function ($q) use ($owner) {
                            $q->where('order_id', $owner->id)
                                ->where('sent', true);
                        });
                    }),

                Filter::make('not_sent')
                    ->label('Not Sent')
                    ->query(function (Builder $query) {
                        /** @var Order $owner */
                        $owner = $this->getOwnerRecord();

                        return $query->whereDoesntHave('rfqStatuses', function ($q) use ($owner) {
                            $q->where('order_id', $owner->id)
                                ->where('sent', true);
                        });
                    }),
            ])
            ->actions([
                Action::make('import_quote')
                    ->label('Import')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->form([
                        FileUpload::make('file')
                            ->label('Excel File')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->maxSize(5120)
                            ->required()
                            ->helperText('Upload the Excel file returned by this supplier with filled prices.'),
                    ])
                    ->action(function (array $data, Supplier $record) {
                        /** @var Order $owner */
                        $owner = $this->getOwnerRecord();
                        $importService = app(SupplierQuoteImportService::class);
                        $filePath = storage_path('app/' . $data['file']);
                        
                        try {
                            // Create Supplier Quote if it doesn't exist
                            $supplierQuote = SupplierQuote::firstOrCreate(
                                [
                                    'order_id' => $owner->id,
                                    'supplier_id' => $record->id,
                                ],
                                [
                                    'currency_id' => $owner->currency_id,
                                    'status' => 'draft',
                                    'created_by' => auth()->id(),
                                    'updated_by' => auth()->id(),
                                ]
                            );
                            
                            // Import Excel data
                            $result = $importService->importFromExcel($supplierQuote, $filePath);
                            
                            if ($result['success']) {
                                Notification::make()
                                    ->success()
                                    ->title('Import Successful')
                                    ->body($result['message'] . " Supplier Quote created/updated for {$record->name}.")
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Import Completed with Warnings')
                                    ->body($result['message'])
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
                                try {
                                    @unlink($filePath);
                                } catch (\Throwable $e) {
                                    // Ignore cleanup errors
                                }
                            }
                        }
                    })
                    ->modalHeading('Import Supplier Quote from Excel')
                    ->modalSubmitActionLabel('Import'),
                    
                Action::make('send_quotation')
                    ->label(function (Supplier $record): string {
                        $status = $this->getSupplierStatus($record);
                        return $status && $status->isSent()
                            ? 'Sent on ' . $status->sent_at->format('M d')
                            : 'Send Quotation';
                    })
                    ->icon(function (Supplier $record): string {
                        $status = $this->getSupplierStatus($record);
                        return $status && $status->isSent()
                            ? 'heroicon-o-check-circle'
                            : 'heroicon-o-paper-airplane';
                    })
                    ->color(function (Supplier $record): string {
                        $status = $this->getSupplierStatus($record);
                        return $status && $status->isSent() ? 'success' : 'primary';
                    })
                    ->disabled(function (Supplier $record): bool {
                        $status = $this->getSupplierStatus($record);
                        return $status && $status->isSent();
                    })
                    ->form(function (Supplier $record) {
                        $contacts = $record->suppliercontacts()
                            ->whereNotNull('email')
                            ->get();

                        if ($contacts->isEmpty()) {
                            return [
                                Forms\Components\Placeholder::make('no_contacts')
                                    ->content('This supplier has no contacts with email addresses. Please add contacts before sending.')
                            ];
                        }

                        $options = $contacts->mapWithKeys(function ($contact) {
                            return [
                                $contact->id => $contact->name . ' (' . $contact->email . ')' . 
                                    ($contact->function ? ' - ' . $contact->function->value : '')
                            ];
                        })->toArray();

                        return [
                            CheckboxList::make('contact_ids')
                                ->label('Select Contacts to Send RFQ')
                                ->options($options)
                                ->required()
                                ->columns(1)
                                ->helperText('Select one or more contacts to receive the RFQ via email.')
                        ];
                    })
                    ->modalHeading('Send Quotation Request')
                    ->modalDescription(fn (Supplier $record) => "Select contacts from {$record->name} to receive the RFQ")
                    ->modalSubmitActionLabel('Send RFQ')
                    ->action(function (Supplier $record, array $data) {
                        /** @var Order $owner */
                        $owner = $this->getOwnerRecord();

                        // Check if supplier has contacts
                        $contacts = $record->suppliercontacts()
                            ->whereNotNull('email')
                            ->get();

                        if ($contacts->isEmpty()) {
                            Notification::make()
                                ->title('No Contacts')
                                ->body('This supplier has no contacts with email addresses.')
                                ->warning()
                                ->send();
                            return;
                        }

                        try {
                            // Get selected contacts
                            $selectedContactIds = $data['contact_ids'] ?? [];
                            $selectedContacts = $contacts->whereIn('id', $selectedContactIds);

                            if ($selectedContacts->isEmpty()) {
                                Notification::make()
                                    ->title('No Contacts Selected')
                                    ->body('Please select at least one contact.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Generate RFQ Excel
                            $excelService = new RFQExcelService();
                            $filePath = $excelService->generateRFQ($owner);

                            // Send email to each selected contact
                            foreach ($selectedContacts as $contact) {
                                // TODO: Implement actual email sending
                                // Mail::to($contact->email)->send(new RFQMail($owner, $filePath));
                            }

                            // Mark as sent
                            $owner->markSentToSupplier($record->id, 'email');

                            // Clean up temp file
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }

                            Notification::make()
                                ->title('Quotation Sent')
                                ->body("RFQ sent to " . $selectedContacts->count() . " contact(s) from {$record->name}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Failed to send quotation: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('view_supplier')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Supplier $record): string => route('filament.admin.resources.suppliers.edit', ['record' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkAction::make('send_to_all')
                    ->label('Send to Selected')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Send Quotation to Selected Suppliers')
                    ->modalDescription('Send quotation request to all selected suppliers?')
                    ->action(function ($records) {
                        /** @var Order $owner */
                        $owner = $this->getOwnerRecord();
                        $count = 0;

                        foreach ($records as $supplier) {
                            try {
                                // Skip if already sent
                                if (!$owner->isSentToSupplier($supplier->id)) {
                                    $owner->markSentToSupplier($supplier->id, 'email');
                                    $count++;
                                }
                            } catch (\Exception $e) {
                                \Log::error("Failed to send to supplier {$supplier->id}: " . $e->getMessage());
                            }
                        }

                        Notification::make()
                            ->title('Quotations Sent')
                            ->body("Sent quotation requests to {$count} supplier(s)")
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No Matching Suppliers')
            ->emptyStateDescription('Add categories to this RFQ to find matching suppliers')
            ->emptyStateIcon('heroicon-o-tag')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    /**
     * Get the RFQ supplier status for a supplier
     */
    protected function getSupplierStatus(Supplier $supplier): ?RFQSupplierStatus
    {
        /** @var Order $owner */
        $owner = $this->getOwnerRecord();

        return RFQSupplierStatus::where('order_id', $owner->id)
            ->where('supplier_id', $supplier->id)
            ->first();
    }

    /**
     * Disable form as this is read-only
     */
    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    /**
     * Disable create/edit/delete as this is read-only
     */
    public function canCreate(): bool
    {
        return false;
    }

    public function canEdit(Model $record): bool
    {
        return false;
    }

    public function canDelete(Model $record): bool
    {
        return false;
    }

    public function canDeleteAny(): bool
    {
        return false;
    }
}
