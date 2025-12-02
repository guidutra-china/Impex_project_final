<?php

namespace App\Filament\Resources\SalesInvoices\Tables;

use App\Services\Export\ExcelExportService;
use App\Services\Export\PdfExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SalesInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('revision_number')
                    ->label('Rev')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('paymentTerm.name')
                    ->label('Payment Terms')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('quote.quote_number')
                    ->label('Quote #')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('approval_status')
                    ->label('Approval')
                    ->badge()
                    ->colors([
                        'warning' => 'pending_approval',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_approval' => 'Pending',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('deposit_status')
                    ->label('Deposit')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->deposit_required) return 'Not Required';
                        return $record->deposit_received ? 'Received' : 'Pending';
                    })
                    ->colors([
                        'gray' => 'Not Required',
                        'warning' => 'Pending',
                        'success' => 'Received',
                    ])
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'sent',
                        'success' => 'paid',
                        'danger' => ['overdue', 'cancelled'],
                        'gray' => 'superseded',
                    ])
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('approval_deadline')
                    ->label('Approval Deadline')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isApprovalOverdue() ? 'danger' : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),

                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency->code ?? 'USD')
                    ->sortable(),

                TextColumn::make('commission')
                    ->label('Commission')
                    ->money(fn ($record) => $record->currency->code ?? 'USD')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('currency.code')
                    ->label('Currency')
                    ->toggleable(),

                TextColumn::make('payment_date')
                    ->label('Paid On')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->label('Approval Status')
                    ->options([
                        'pending_approval' => 'Pending Approval',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('deposit_pending')
                    ->query(fn ($query) => $query->where('deposit_required', true)
                        ->where('deposit_received', false))
                    ->label('Deposit Pending'),

                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                        'superseded' => 'Superseded',
                    ])
                    ->multiple(),

                SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('currency')
                    ->relationship('currency', 'code'),

                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->where('status', '!=', 'paid')
                        ->where('status', '!=', 'cancelled')
                        ->where('status', '!=', 'superseded')
                        ->where('due_date', '<', now()))
                    ->label('Overdue Only'),

                Tables\Filters\Filter::make('unpaid')
                    ->query(fn ($query) => $query->where('status', '!=', 'paid')
                        ->where('status', '!=', 'cancelled')
                        ->where('status', '!=', 'superseded'))
                    ->label('Unpaid Only'),
            ])
            ->actions([
                EditAction::make(),
                
                Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function ($record) {
                        $pdfService = app(PdfExportService::class);
                        $document = $pdfService->generate(
                            $record,
                            'commercial_invoice',
                            'pdf.commercial-invoice.template'
                        );
                        
                        Notification::make()
                            ->success()
                            ->title('Commercial Invoice PDF generated successfully')
                            ->send();
                        
                        return Storage::download(
                            $document->file_path,
                            $document->filename
                        );
                    }),
                
                Action::make('export_excel')
                    ->label('Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function ($record) {
                        $excelService = app(ExcelExportService::class);
                        $document = $excelService->generateCommercialInvoice($record);
                        
                        Notification::make()
                            ->success()
                            ->title('Commercial Invoice Excel generated successfully')
                            ->send();
                        
                        return Storage::download(
                            $document->file_path,
                            $document->filename
                        );
                    }),

                Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['sent', 'overdue']))
                    ->form([
                        DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->default(now())
                            ->required(),
                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'bank_transfer' => 'Bank Transfer',
                                'credit_card' => 'Credit Card',
                                'cash' => 'Cash',
                                'check' => 'Check',
                                'paypal' => 'PayPal',
                                'other' => 'Other',
                            ])
                            ->required(),
                        TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->helperText('Transaction ID, check number, etc.'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'payment_reference' => $data['payment_reference'] ?? null,
                        ]);
                    })
                    ->successNotification(
                        fn ($record) => \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invoice Paid')
                            ->body("Invoice {$record->invoice_number} has been marked as paid.")
                    ),

                Action::make('mark_as_accepted')
                    ->label('Mark as Accepted')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->approval_status === 'pending_approval')
                    ->form([
                        TextInput::make('approved_by')
                            ->label('Approved By')
                            ->helperText('Name or email of person who approved'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'approval_status' => 'accepted',
                            'approved_at' => now(),
                            'approved_by' => $data['approved_by'] ?? 'Manual',
                        ]);
                    })
                    ->successNotification(
                        fn ($record) => \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invoice Accepted')
                            ->body("Invoice {$record->invoice_number} has been accepted by client.")
                    ),

                Action::make('mark_deposit_received')
                    ->label('Mark Deposit Received')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->deposit_required && !$record->deposit_received)
                    ->form([
                        DatePicker::make('deposit_received_at')
                            ->label('Deposit Received Date')
                            ->default(now())
                            ->required(),
                        Select::make('deposit_payment_method')
                            ->label('Payment Method')
                            ->options([
                                'bank_transfer' => 'Bank Transfer',
                                'credit_card' => 'Credit Card',
                                'cash' => 'Cash',
                                'check' => 'Check',
                                'paypal' => 'PayPal',
                                'other' => 'Other',
                            ])
                            ->required(),
                        TextInput::make('deposit_payment_reference')
                            ->label('Payment Reference')
                            ->helperText('Transaction ID, check number, etc.'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'deposit_received' => true,
                            'deposit_received_at' => $data['deposit_received_at'],
                            'deposit_payment_method' => $data['deposit_payment_method'],
                            'deposit_payment_reference' => $data['deposit_payment_reference'] ?? null,
                        ]);
                    })
                    ->successNotification(
                        fn ($record) => \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Deposit Received')
                            ->body("Deposit for invoice {$record->invoice_number} has been confirmed.")
                    ),

                Action::make('mark_as_sent')
                    ->label('Mark as Sent')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invoice to Client')
                    ->modalDescription(fn ($record) => "Are you sure you want to mark invoice {$record->invoice_number} as sent? This will change the status from Draft to Sent.")
                    ->modalSubmitActionLabel('Yes, Mark as Sent')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                    })
                    ->successNotification(
                        fn ($record) => \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invoice Sent')
                            ->body("Invoice {$record->invoice_number} has been marked as sent to client.")
                    ),

                Action::make('cancel')
                    ->label('Cancel Invoice')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => !in_array($record->status, ['paid', 'cancelled', 'superseded']))
                    ->modalHeading('Cancel Invoice')
                    ->modalDescription(fn ($record) => "Are you sure you want to cancel invoice {$record->invoice_number}? This action cannot be undone.")
                    ->form([
                        Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->rows(3)
                            ->helperText('Please provide a reason for cancelling this invoice.'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'cancellation_reason' => $data['cancellation_reason'],
                        ]);
                    })
                    ->successNotification(
                        fn ($record) => \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Invoice Cancelled')
                            ->body("Invoice {$record->invoice_number} has been cancelled.")
                    ),

                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function ($record) {
                        $pdf = \PDF::loadView('pdf.invoices.sales-invoice', ['invoice' => $record]);
                        $filename = str_replace('/', '-', $record->invoice_number) . '-Rev' . $record->revision_number . '.pdf';
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, $filename);
                    }),

                Action::make('create_revision')
                    ->label('Create Revision')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->visible(fn ($record) => !in_array($record->status, ['draft', 'cancelled']) && !$record->isSuperseded())
                    ->modalHeading('Create New Revision')
                    ->modalDescription(fn ($record) => "This will create a new revision of invoice {$record->invoice_number}. The current invoice will be marked as superseded.")
                    ->form([
                        Textarea::make('revision_reason')
                            ->label('Reason for Revision')
                            ->required()
                            ->rows(3)
                            ->helperText('Please explain why this revision is needed.'),
                    ])
                    ->action(function ($record, array $data) {
                        \DB::transaction(function () use ($record, $data) {
                            // Create new revision
                            $newRevision = $record->replicate();
                            $newRevision->revision_number = $record->revision_number + 1;
                            $newRevision->status = 'draft';
                            $newRevision->revision_reason = $data['revision_reason'];
                            $newRevision->supersedes_id = $record->id;
                            $newRevision->superseded_by_id = null;
                            $newRevision->sent_at = null;
                            $newRevision->paid_at = null;
                            $newRevision->cancelled_at = null;
                            $newRevision->payment_date = null;
                            $newRevision->save();

                            // Copy items
                            foreach ($record->items as $item) {
                                $newItem = $item->replicate();
                                $newItem->sales_invoice_id = $newRevision->id;
                                $newItem->save();
                            }

                            // Copy purchase orders relationship
                            $purchaseOrderIds = $record->purchaseOrders()->pluck('purchase_orders.id')->toArray();
                            $newRevision->purchaseOrders()->sync($purchaseOrderIds);

                            // Mark current invoice as superseded
                            $record->update([
                                'status' => 'superseded',
                                'superseded_by_id' => $newRevision->id,
                            ]);
                        });
                    })
                    ->successNotification(
                        fn ($record) => \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Revision Created')
                            ->body("New revision {$record->invoice_number}-R" . ($record->revision_number + 1) . " has been created.")
                    )
                    ->after(function () {
                        // Redirect to the new revision (optional, can be implemented later)
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
