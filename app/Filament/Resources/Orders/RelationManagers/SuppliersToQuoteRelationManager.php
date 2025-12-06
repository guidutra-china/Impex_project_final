<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\SupplierQuote;
use App\Models\RFQSupplierStatus;
use App\Repositories\OrderRepository;
use App\Repositories\SupplierRepository;
use App\Services\RFQExcelService;
use App\Services\RFQMatchingService;
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

    protected OrderRepository $orderRepository;
    protected SupplierRepository $supplierRepository;

    public function mount(): void {
        parent::mount();
        $this->orderRepository = app(OrderRepository::class);
        $this->supplierRepository = app(SupplierRepository::class);
    }

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

                // Get tags from Order (Tags for Suppliers field)
                $orderTagIds = $owner->tags()->pluck('tags.id')->toArray();
                
                if (empty($orderTagIds)) {
                    // No tags selected in Order, return empty query
                    return Supplier::query()->whereRaw('1 = 0');
                }

                // Find suppliers that have at least one matching tag
                return Supplier::query()
                    ->whereHas('tags', function ($q) use ($orderTagIds) {
                        $q->whereIn('tags.id', $orderTagIds);
                    })
                    ->with('tags');
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

                TextColumn::make('matched_products')
                    ->label('Matched Products')
                    ->state(function (Supplier $record) {
                        $owner = $this->getOwnerRecord();
                        
                        // Get tags from this specific Supplier
                        $supplierTagIds = $record->tags()->pluck('tags.id')->toArray();
                        
                        if (empty($supplierTagIds)) {
                            return 'None';
                        }
                        
                        // Get products from Order items that have tags matching this supplier's tags
                        $productIds = $owner->items()->pluck('product_id')->toArray();
                        
                        if (empty($productIds)) {
                            return 'None';
                        }
                        
                        $matchedProducts = \App\Models\Product::whereIn('id', $productIds)
                            ->whereHas('tags', function ($q) use ($supplierTagIds) {
                                $q->whereIn('tags.id', $supplierTagIds);
                            })
                            ->get();
                        
                        if ($matchedProducts->isEmpty()) {
                            return 'None';
                        }
                        
                        return $matchedProducts->pluck('name')->join(', ');
                    })
                    ->wrap()
                    ->searchable(false)
                    ->sortable(false)
                    ->badge()
                    ->color('success'),

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
                        
                        // Filament already stored the file, $data['file'] is the path
                        $filePath = storage_path('app/public/' . $data['file']);
                        
                        // Try alternative paths if file doesn't exist
                        if (!file_exists($filePath)) {
                            $filePath = storage_path('app/' . $data['file']);
                        }
                        
                        \Log::info('Import file path', [
                            'data_file' => $data['file'],
                            'full_path' => $filePath,
                            'exists' => file_exists($filePath),
                        ]);
                        
                        if (!file_exists($filePath)) {
                            throw new \Exception('File does not exist at: ' . $filePath);
                        }
                        
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
                                ->label('Send to')
                                ->options($options)
                                ->required()
                                ->default(array_keys($options))
                        ];
                    })
                    ->action(function (array $data, Supplier $record) {
                        /** @var Order $owner */
                        $owner = $this->getOwnerRecord();
                        $rfqService = app(RFQExcelService::class);
                        
                        try {
                            // Get selected contacts
                            $contactIds = $data['contact_ids'] ?? [];
                            $contacts = $record->suppliercontacts()
                                ->whereIn('id', $contactIds)
                                ->get();

                            if ($contacts->isEmpty()) {
                                throw new \Exception('No contacts selected');
                            }

                            // Generate and send Excel file (personalized for this supplier)
                            $filePath = $rfqService->generateRFQ($owner, $record);
                            
                            foreach ($contacts as $contact) {
                                if ($contact->email) {
                                    try {
                                        Mail::send('emails.rfq-request', [
                                            'supplier' => $record,
                                            'contact' => $contact,
                                            'order' => $owner,
                                        ], function ($message) use ($contact, $filePath, $record) {
                                            $message->to($contact->email)
                                                ->subject("RFQ Request from {" . ($owner->company ? $owner->company->name : 'Our Company') . "}")
                                                ->attach($filePath);
                                        });

                                        \Log::info("RFQ sent to {$contact->email}");
                                    } catch (\Exception $e) {
                                        \Log::error("Failed to send to {$contact->email}: " . $e->getMessage());
                                    }
                                }
                            }

                            // Mark as sent
                            RFQSupplierStatus::updateOrCreate(
                                [
                                    'order_id' => $owner->id,
                                    'supplier_id' => $record->id,
                                ],
                                [
                                    'sent' => true,
                                    'sent_at' => now(),
                                ]
                            );

                            Notification::make()
                                ->success()
                                ->title('Quotation Sent')
                                ->body("RFQ sent to {$contacts->count()} contact(s) at {$record->name}")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to Send')
                                ->body($e->getMessage())
                                ->send();

                            \Log::error('Error sending RFQ', [
                                'supplier_id' => $record->id,
                                'order_id' => $owner->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Send Quotation Request')
                    ->modalSubmitActionLabel('Send'),

                Action::make('send_all')
                    ->label('Send All')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Quotation Requests to All Suppliers')
                    ->modalDescription('This will send RFQ requests to all suppliers with matching tags that have not been sent yet.')
                    ->modalSubmitActionLabel('Send All')
                    ->action(function () {
                        /** @var Order $owner */
                        $owner = $this->getOwnerRecord();
                        $rfqService = app(RFQExcelService::class);
                        $count = 0;

                        // Get all suppliers with matching tags that haven't been sent
                        $tagIds = $owner->tags()->pluck('tags.id');
                        $suppliers = Supplier::query()
                            ->whereHas('tags', function ($q) use ($tagIds) {
                                $q->whereIn('tags.id', $tagIds);
                            })
                            ->whereDoesntHave('rfqStatuses', function ($q) use ($owner) {
                                $q->where('order_id', $owner->id)
                                    ->where('sent', true);
                            })
                            ->get();

                        foreach ($suppliers as $supplier) {
                            $contacts = $supplier->suppliercontacts()
                                ->whereNotNull('email')
                                ->get();

                            if ($contacts->isEmpty()) {
                                continue;
                            }

                            try {
                                $filePath = $rfqService->generateRFQExcel($owner, $supplier);
                                
                                foreach ($contacts as $contact) {
                                    if ($contact->email) {
                                        try {
                                            Mail::send('emails.rfq-request', [
                                                'supplier' => $supplier,
                                                'contact' => $contact,
                                                'order' => $owner,
                                            ], function ($message) use ($contact, $filePath, $supplier, $owner) {
                                                $message->to($contact->email)
                                                    ->subject("RFQ Request from {" . ($owner->company ? $owner->company->name : 'Our Company') . "}")
                                                    ->attach($filePath);
                                            });

                                            \Log::info("RFQ sent to {$contact->email}");
                                        } catch (\Exception $e) {
                                            \Log::error("Failed to send to {$contact->email}: " . $e->getMessage());
                                        }
                                    }
                                }

                                // Mark as sent
                                RFQSupplierStatus::updateOrCreate(
                                    [
                                        'order_id' => $owner->id,
                                        'supplier_id' => $supplier->id,
                                    ],
                                    [
                                        'sent' => true,
                                        'sent_at' => now(),
                                    ]
                                );

                                $count++;
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
