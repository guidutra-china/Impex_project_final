<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\ProformaInvoiceResource\Pages;
use App\Models\ProformaInvoice;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class ProformaInvoiceResource extends Resource
{
    protected static ?string $model = ProformaInvoice::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        // Allow all authenticated users in Portal (filtering is done by ClientOwnershipScope)
        return auth()->check();
    }

    // Multi-tenancy filtering is handled automatically by ClientOwnershipScope global scope
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['order' => function ($query) {
            // Load order without its own scope to avoid conflicts
            $query->withoutGlobalScopes();
        }]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
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
                Tables\Columns\TextColumn::make('proforma_number')
                    ->label('PI Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('revision_number')
                    ->label('Rev')
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
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Issue Date')
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
                Action::make('view_pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (ProformaInvoice $record) => route('public.proforma-invoice.show', ['token' => $record->public_token]))
                    ->openUrlInNewTab()
                    ->color('primary'),
                ViewAction::make(),
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
