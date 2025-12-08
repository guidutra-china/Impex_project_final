<?php

namespace App\Filament\Resources\SupplierQuotes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierQuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Quote Information')
                    ->schema([
                        TextInput::make('quote_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->columnSpan(1),

                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'accepted' => 'Accepted',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->default('draft')
                            ->columnSpan(1),

                        TextInput::make('revision_number')
                            ->numeric()
                            ->default(1)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Toggle::make('is_latest')
                            ->label('Latest Version')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(4),

                Section::make('Order & Supplier')
                    ->schema([
                        Select::make('order_id')
                            ->relationship('order', 'order_number')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Select::make('currency_id')
                            ->relationship('currency', 'code', fn ($query) => $query->where('is_active', true))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Quote Currency')
                            ->helperText('Currency supplier is quoting in')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Section::make('Pricing')
                    ->schema([
                        Placeholder::make('total_price_before_commission_display')
                            ->label('Subtotal (Before Commission)')
                            ->content(function ($record) {
                                if (!$record || !$record->total_price_before_commission) {
                                    return 'Not calculated';
                                }
                                $currency = $record->currency;
                                $amount = number_format($record->total_price_before_commission / 100, 2);
                                return $currency ? "{$currency->symbol}{$amount}" : "\${$amount}";
                            })
                            ->columnSpan(1),

                        Placeholder::make('commission_amount_display')
                            ->label('Commission')
                            ->content(function ($record) {
                                if (!$record || !$record->commission_amount) {
                                    return 'Not calculated';
                                }
                                $currency = $record->currency;
                                $amount = number_format($record->commission_amount / 100, 2);
                                return $currency ? "{$currency->symbol}{$amount}" : "\${$amount}";
                            })
                            ->columnSpan(1),

                        Placeholder::make('total_price_after_commission_display')
                            ->label('Total (After Commission)')
                            ->content(function ($record) {
                                if (!$record || !$record->total_price_after_commission) {
                                    return 'Not calculated';
                                }
                                $currency = $record->currency;
                                $amount = number_format($record->total_price_after_commission / 100, 2);
                                return $currency ? "{$currency->symbol}{$amount}" : "\${$amount}";
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->visibleOn('edit'),

                Section::make('Exchange Rate')
                    ->schema([
                        Placeholder::make('locked_exchange_rate_display')
                            ->label('Locked Rate')
                            ->content(function ($record) {
                                if (!$record || !$record->locked_exchange_rate) {
                                    return 'Not locked yet';
                                }
                                return number_format($record->locked_exchange_rate, 6);
                            })
                            ->columnSpan(1),

                        Placeholder::make('locked_exchange_rate_date_display')
                            ->label('Rate Date')
                            ->content(function ($record) {
                                return $record?->locked_exchange_rate_date?->format('Y-m-d') ?? 'Not locked yet';
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->visibleOn('edit')
                    ->collapsible(),

                Section::make('Validity')
                    ->schema([
                        TextInput::make('validity_days')
                            ->label('Valid for (days)')
                            ->numeric()
                            ->default(30)
                            ->minValue(1)
                            ->columnSpan(1),

                        DatePicker::make('valid_until')
                            ->label('Valid Until')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Procurement Details')
                    ->schema([
                        TextInput::make('moq')
                            ->label('MOQ (Minimum Order Quantity)')
                            ->numeric()
                            ->helperText('Minimum quantity required by supplier')
                            ->columnSpan(1),

                        TextInput::make('lead_time_days')
                            ->label('Lead Time (days)')
                            ->numeric()
                            ->helperText('Production + shipping time in days')
                            ->columnSpan(1),

                        Select::make('incoterm')
                            ->label('Incoterm')
                            ->options([
                                'FOB' => 'FOB - Free On Board',
                                'CIF' => 'CIF - Cost, Insurance & Freight',
                                'EXW' => 'EXW - Ex Works',
                                'DDP' => 'DDP - Delivered Duty Paid',
                                'FCA' => 'FCA - Free Carrier',
                                'CPT' => 'CPT - Carriage Paid To',
                                'CIP' => 'CIP - Carriage & Insurance Paid',
                                'DAP' => 'DAP - Delivered At Place',
                                'DPU' => 'DPU - Delivered At Place Unloaded',
                            ])
                            ->searchable()
                            ->helperText('International Commercial Terms')
                            ->columnSpan(1),

                        Select::make('payment_terms')
                            ->label(__('fields.payment_terms'))
                            ->options([
                                'advance_100' => '100% Advance Payment',
                                'advance_50_balance_50' => '50% Advance, 50% Before Shipment',
                                'advance_30_balance_70' => '30% Advance, 70% Before Shipment',
                                'net_30' => 'Net 30 days',
                                'net_60' => 'Net 60 days',
                                'lc_at_sight' => 'L/C at Sight',
                                'lc_90_days' => 'L/C 90 days',
                            ])
                            ->searchable()
                            ->helperText('Payment conditions')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('supplier_notes')
                            ->label('Supplier Notes')
                            ->helperText('Notes from supplier')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->helperText('Internal notes (not visible to supplier)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
