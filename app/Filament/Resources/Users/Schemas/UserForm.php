<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Personal Information')
                    ->description('Basic user information and contact details')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Profile Photo')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->disk('public')
                            ->maxSize(2048)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth(256)
                            ->imageResizeTargetHeight(256)
                            ->helperText('Upload a profile photo (max 2MB, square format recommended)')
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->columnSpan(1),

                        TextInput::make('email')
                            ->label(__('fields.email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->suffixIcon('heroicon-o-envelope')
                            ->columnSpan(1),

                        TextInput::make('phone')
                            ->label(__('fields.phone'))
                            ->tel()
                            ->mask('(999) 999-9999')
                            ->placeholder('(123) 456-7890')
                            ->maxLength(20)
                            ->suffixIcon('heroicon-o-phone')
                            ->helperText('Optional: For multi-channel communication')
                            ->columnSpan(1),

                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->nullable()
                            ->suffixIcon('heroicon-o-check-badge')
                            ->helperText('Set to verify user email manually')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Account Settings')
                    ->description('User status and administrative privileges')
                    ->schema([
                        Select::make('status')
                            ->label(__('fields.status'))
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'suspended' => 'Suspended',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false)
                            ->helperText('Active users can log in, inactive/suspended cannot')
                            ->columnSpan(1),

                        Toggle::make('is_admin')
                            ->label('Administrator')
                            ->helperText('Grants full system access and administrative privileges')
                            ->inline(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Security')
                    ->description('Password and authentication settings')
                    ->schema([
                        TextInput::make('password')
                            ->label(__('fields.password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->rules([
                                'nullable',
                                'min:12',
                                'regex:/[A-Z]/',      // At least one uppercase
                                'regex:/[a-z]/',      // At least one lowercase
                                'regex:/[0-9]/',      // At least one number
                                'regex:/[@$!%*#?&]/', // At least one special character
                            ])
                            ->autocomplete('new-password')
                            ->helperText('Minimum 12 characters with uppercase, lowercase, number, and special character (@$!%*#?&)')
                            ->validationMessages([
                                'min' => 'Password must be at least 12 characters',
                                'regex' => 'Password must contain uppercase, lowercase, number, and special character',
                            ])
                            ->placeholder(fn (string $context): string => 
                                $context === 'edit' ? 'Leave blank to keep current password' : 'Enter a strong password'
                            )
                            ->columnSpanFull(),
                    ]),

                Section::make('Roles & Permissions')
                    ->description('Assign roles to control user access and permissions')
                    ->schema([
                        Select::make('roles')
                            ->label('User Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->native(false)
                            ->helperText('Assign one or more roles to this user')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
