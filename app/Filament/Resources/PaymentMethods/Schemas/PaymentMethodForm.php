<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Method Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., PayPal Business, Bank Transfer USD'),

                        Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'bank_transfer' => 'Bank Transfer',
                                'wire_transfer' => 'Wire Transfer',
                                'paypal' => 'PayPal',
                                'credit_card' => 'Credit Card',
                                'debit_card' => 'Debit Card',
                                'check' => 'Check',
                                'cash' => 'Cash',
                                'wise' => 'Wise (TransferWise)',
                                'cryptocurrency' => 'Cryptocurrency',
                                'other' => 'Other',
                            ])
                            ->searchable(),

                        Select::make('bank_account_id')
                            ->label('Bank Account')
                            ->relationship('bankAccount', 'account_name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Optional: Link this payment method to a specific bank account'),

                        Select::make('processing_time')
                            ->label('Processing Time')
                            ->required()
                            ->options([
                                'immediate' => 'Immediate',
                                'same_day' => 'Same Day',
                                '1_3_days' => '1-3 Days',
                                '3_5_days' => '3-5 Days',
                                '5_7_days' => '5-7 Days',
                            ])
                            ->default('immediate'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive payment methods will not be available for selection'),
                    ])
                    ->columns(2),

                Section::make('Fee Configuration')
                    ->schema([
                        Select::make('fee_type')
                            ->label('Fee Type')
                            ->required()
                            ->options([
                                'none' => 'No Fee',
                                'fixed' => 'Fixed Fee',
                                'percentage' => 'Percentage Fee',
                                'fixed_plus_percentage' => 'Fixed + Percentage',
                            ])
                            ->default('none')
                            ->live()
                            ->helperText('How fees are calculated for this payment method'),

                        TextInput::make('fixed_fee')
                            ->label('Fixed Fee')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->helperText('Amount in dollars (e.g., 5.00 for $5.00)')
                            ->visible(fn ($get) => in_array($get('fee_type'), ['fixed', 'fixed_plus_percentage'])),

                        TextInput::make('percentage_fee')
                            ->label('Percentage Fee')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->helperText('Percentage of transaction amount (e.g., 2.9 for 2.9%)')
                            ->visible(fn ($get) => in_array($get('fee_type'), ['percentage', 'fixed_plus_percentage'])),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(65535)
                            ->rows(3)
                            ->placeholder('Any additional information about this payment method...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
