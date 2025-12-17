<?php

namespace App\Filament\Portal\Resources\CustomerQuoteResource\Pages;

use App\Filament\Portal\Resources\CustomerQuoteResource;
use App\Services\CustomerQuoteService;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewCustomerQuote extends ViewRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Quote Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('quote_number')
                                    ->label('Quote Number'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'sent' => 'info',
                                        'viewed' => 'warning',
                                        'accepted' => 'success',
                                        'rejected' => 'danger',
                                        'expired' => 'gray',
                                        default => 'gray',
                                    }),
                                TextEntry::make('expires_at')
                                    ->label('Expires At')
                                    ->dateTime(),
                            ]),
                    ]),

                Section::make('Quote Options')
                    ->description('Select the option that best fits your needs')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('display_name')
                                            ->label('Option')
                                            ->weight('bold')
                                            ->size('lg'),
                                        TextEntry::make('price_after_commission')
                                            ->label('Price')
                                            ->money('USD')
                                            ->weight('bold')
                                            ->color('success'),
                                        TextEntry::make('delivery_time')
                                            ->label('Delivery'),
                                        TextEntry::make('moq')
                                            ->label('MOQ'),
                                    ]),
                                TextEntry::make('highlights')
                                    ->label('Highlights')
                                    ->columnSpanFull(),
                                TextEntry::make('is_selected_by_customer')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Selected' : 'Not Selected')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->contained(true),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('select_option')
                ->label('Select Option')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status !== 'accepted')
                ->form([
                    \Filament\Forms\Components\Select::make('item_id')
                        ->label('Choose Your Preferred Option')
                        ->options(function () {
                            return $this->record->items->mapWithKeys(function ($item) {
                                return [
                                    $item->id => "{$item->display_name} - " . money($item->price_after_commission, 'USD') . 
                                        " - Delivery: {$item->delivery_time}"
                                ];
                            });
                        })
                        ->required()
                        ->helperText('Select the option that best meets your requirements'),
                ])
                ->action(function (array $data, CustomerQuoteService $service) {
                    try {
                        $service->approveItem($this->record, $data['item_id']);

                        Notification::make()
                            ->success()
                            ->title('Option Selected')
                            ->body('Your selection has been recorded. Our team will process your order.')
                            ->send();

                        return redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
