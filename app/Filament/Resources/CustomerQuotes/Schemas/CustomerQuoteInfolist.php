<?php

namespace App\Filament\Resources\CustomerQuotes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerQuoteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quote Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('quote_number')
                                    ->label('Quote Number')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->copyable(),

                                TextEntry::make('order.order_number')
                                    ->label('RFQ')
                                    ->url(fn ($record) => $record->order ? route('filament.admin.resources.orders.edit', $record->order) : null)
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('order.customer.name')
                                    ->label('Customer')
                                    ->weight('semibold'),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'secondary',
                                        'sent' => 'info',
                                        'viewed' => 'warning',
                                        'accepted' => 'success',
                                        'rejected' => 'danger',
                                        'expired' => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                                TextEntry::make('expires_at')
                                    ->label('Expiry Date')
                                    ->date()
                                    ->color(fn ($record) => $record->expires_at < now() ? 'danger' : 'success'),

                                TextEntry::make('items_count')
                                    ->label('Number of Options')
                                    ->state(fn ($record) => $record->items->count())
                                    ->badge(),
                            ]),
                    ])
                    ->columns(2),

                Section::make('Customer Access')
                    ->schema([
                        TextEntry::make('public_token')
                            ->label('Public Access Token')
                            ->copyable()
                            ->helperText('Share this token with the customer for access without login'),

                        TextEntry::make('public_url')
                            ->label('Public URL')
                            ->state(fn ($record) => route('customer-quote.public', ['token' => $record->public_token]))
                            ->copyable()
                            ->url(fn ($record) => route('customer-quote.public', ['token' => $record->public_token]))
                            ->openUrlInNewTab(),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Commission Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('commission_type')
                                    ->label('Commission Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'embedded' => 'success',
                                        'separate' => 'info',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                                TextEntry::make('commission_percent')
                                    ->label('Commission Percentage')
                                    ->suffix('%')
                                    ->default('N/A'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Activity Tracking')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('sent_at')
                                    ->label('Sent At')
                                    ->dateTime()
                                    ->placeholder('Not sent yet'),

                                TextEntry::make('viewed_at')
                                    ->label('Viewed At')
                                    ->dateTime()
                                    ->placeholder('Not viewed yet'),

                                TextEntry::make('responded_at')
                                    ->label('Responded At')
                                    ->dateTime()
                                    ->placeholder('No response yet'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Notes')
                    ->schema([
                        TextEntry::make('internal_notes')
                            ->label('Internal Notes')
                            ->placeholder('No internal notes')
                            ->columnSpan('full'),

                        TextEntry::make('customer_notes')
                            ->label('Customer Notes')
                            ->placeholder('No customer notes')
                            ->columnSpan('full'),
                    ])
                    ->collapsible(),

                Section::make('Metadata')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime()
                                    ->since(),

                                TextEntry::make('createdBy.name')
                                    ->label('Created By')
                                    ->default('N/A'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
