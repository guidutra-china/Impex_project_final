<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\ProformaInvoiceResource\Pages;
use App\Models\ProformaInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProformaInvoiceResource extends Resource
{
    protected static ?string $model = ProformaInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('finance');
    }

    public static function getEloquentQuery(): Builder
    {
        // Filter by client_id through order
        return parent::getEloquentQuery()
            ->whereHas('order', function ($query) {
                $query->where('client_id', auth()->user()->client_id);
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Proforma Invoice Information')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->disabled(),
                        Forms\Components\DatePicker::make('invoice_date')
                            ->label('Invoice Date')
                            ->disabled(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency ?? 'USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProformaInvoices::route('/'),
            'view' => Pages\ViewProformaInvoice::route('/{record}'),
        ];
    }
}
