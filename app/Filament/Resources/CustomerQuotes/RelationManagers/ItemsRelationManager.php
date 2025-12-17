<?php

namespace App\Filament\Resources\CustomerQuotes\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Quote Options & Products';

    protected static ?string $recordTitleAttribute = 'display_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('display_name')
                    ->label('Display Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('delivery_time')
                    ->label('Delivery Time')
                    ->maxLength(255),

                TextInput::make('moq')
                    ->label('MOQ')
                    ->numeric(),

                Textarea::make('highlights')
                    ->label('Highlights')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_visible_to_customer')
                    ->label('Visible to Customer')
                    ->helperText('If disabled, this option will not be shown to the customer')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Option')
                    ->badge()
                    ->color('primary')
                    ->size('lg')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('supplierQuote.supplier.name')
                    ->label('Supplier (Internal Only)')
                    ->toggleable()
                    ->description('Hidden from customer')
                    ->color('gray'),

                TextColumn::make('price_after_commission')
                    ->label('Total Price')
                    ->money(fn ($record) => $record->customerQuote->order->currency->code ?? 'USD', divideBy: 100)
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->color('success'),

                TextColumn::make('delivery_time')
                    ->label('Delivery Time')
                    ->placeholder('Not specified')
                    ->icon('heroicon-o-clock')
                    ->sortable(),

                TextColumn::make('moq')
                    ->label('MOQ')
                    ->numeric()
                    ->placeholder('N/A')
                    ->toggleable()
                    ->sortable(),

                IconColumn::make('is_visible_to_customer')
                    ->label('Visible to Customer')
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('is_selected_by_customer')
                    ->label('Customer Selection')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? '✓ Selected' : 'Not selected')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('display_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                TernaryFilter::make('is_visible_to_customer')
                    ->label('Visible to Customer')
                    ->placeholder('All')
                    ->trueLabel('Visible only')
                    ->falseLabel('Hidden only'),
            ])
            ->headerActions([
                Action::make('show_all')
                    ->label('Show All to Customer')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->action(function () {
                        $this->getOwnerRecord()->items()->update(['is_visible_to_customer' => true]);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('All options are now visible to customer')
                            ->send();
                    }),

                Action::make('hide_all')
                    ->label('Hide All from Customer')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->action(function () {
                        $this->getOwnerRecord()->items()->update(['is_visible_to_customer' => false]);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('All options are now hidden from customer')
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make(),
                
                Action::make('toggle_visibility')
                    ->label(fn ($record) => $record->is_visible_to_customer ? 'Hide' : 'Show')
                    ->icon(fn ($record) => $record->is_visible_to_customer ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->is_visible_to_customer ? 'warning' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_visible_to_customer' => !$record->is_visible_to_customer]);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title($record->is_visible_to_customer ? 'Option is now visible' : 'Option is now hidden')
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('show_to_customer')
                        ->label('Show to Customer')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_visible_to_customer' => true]);
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Selected options are now visible')
                                ->send();
                        }),

                    BulkAction::make('hide_from_customer')
                        ->label('Hide from Customer')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each->update(['is_visible_to_customer' => false]);
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Selected options are now hidden')
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order', 'asc')
            ->reorderable('display_order')
            ->emptyStateHeading('No quote options')
            ->emptyStateDescription('Generate a customer quote from supplier quotes to see options here.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
