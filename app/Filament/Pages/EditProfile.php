<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EditProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'My Profile';

    protected static ?string $title = 'My Profile';

    protected static string $view = 'filament.pages.edit-profile';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'phone' => Auth::user()->phone,
            'avatar' => Auth::user()->avatar,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Profile')
                    ->tabs([
                        Tabs\Tab::make('Personal Information')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Profile Photo')
                                    ->description('Update your profile photo. This will be visible to other users.')
                                    ->schema([
                                        FileUpload::make('avatar')
                                            ->label('Avatar')
                                            ->image()
                                            ->avatar()
                                            ->directory('avatars')
                                            ->disk('public')
                                            ->maxSize(2048)
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('1:1')
                                            ->imageResizeTargetWidth(256)
                                            ->imageResizeTargetHeight(256)
                                            ->helperText('Upload a square photo (max 2MB). Recommended size: 256x256 pixels.')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Basic Information')
                                    ->description('Update your name and contact information.')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Full Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-user')
                                            ->columnSpan(1),

                                        TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-envelope')
                                            ->unique(ignoreRecord: true, table: 'users', column: 'email')
                                            ->helperText('Your email address is used for login and notifications.')
                                            ->columnSpan(1),

                                        TextInput::make('phone')
                                            ->label('Phone Number')
                                            ->tel()
                                            ->mask('(999) 999-9999')
                                            ->placeholder('(123) 456-7890')
                                            ->maxLength(20)
                                            ->prefixIcon('heroicon-o-phone')
                                            ->helperText('Optional: For multi-channel communication.')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Security')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Section::make('Change Password')
                                    ->description('Update your password to keep your account secure.')
                                    ->schema([
                                        TextInput::make('current_password')
                                            ->label('Current Password')
                                            ->password()
                                            ->revealable()
                                            ->required()
                                            ->currentPassword()
                                            ->prefixIcon('heroicon-o-key')
                                            ->helperText('Enter your current password to confirm your identity.')
                                            ->columnSpanFull(),

                                        TextInput::make('password')
                                            ->label('New Password')
                                            ->password()
                                            ->revealable()
                                            ->required()
                                            ->rules([
                                                'confirmed',
                                                'min:12',
                                                'regex:/[A-Z]/',      // At least one uppercase
                                                'regex:/[a-z]/',      // At least one lowercase
                                                'regex:/[0-9]/',      // At least one number
                                                'regex:/[@$!%*#?&]/', // At least one special character
                                            ])
                                            ->prefixIcon('heroicon-o-lock-closed')
                                            ->helperText('Minimum 12 characters with uppercase, lowercase, number, and special character (@$!%*#?&).')
                                            ->validationMessages([
                                                'min' => 'Password must be at least 12 characters.',
                                                'regex' => 'Password must contain uppercase, lowercase, number, and special character.',
                                            ])
                                            ->columnSpanFull(),

                                        TextInput::make('password_confirmation')
                                            ->label('Confirm New Password')
                                            ->password()
                                            ->revealable()
                                            ->required()
                                            ->prefixIcon('heroicon-o-lock-closed')
                                            ->helperText('Re-enter your new password to confirm.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Account Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Account Details')
                                    ->description('View your account information and status.')
                                    ->schema([
                                        TextInput::make('status_display')
                                            ->label('Account Status')
                                            ->default(fn () => ucfirst(Auth::user()->status ?? 'active'))
                                            ->disabled()
                                            ->prefixIcon('heroicon-o-check-circle')
                                            ->helperText('Your current account status.')
                                            ->columnSpan(1),

                                        TextInput::make('roles_display')
                                            ->label('Assigned Roles')
                                            ->default(fn () => Auth::user()->roles->pluck('name')->join(', ') ?: 'No roles assigned')
                                            ->disabled()
                                            ->prefixIcon('heroicon-o-shield-check')
                                            ->helperText('Roles determine your access permissions.')
                                            ->columnSpan(1),

                                        TextInput::make('created_at_display')
                                            ->label('Member Since')
                                            ->default(fn () => Auth::user()->created_at?->format('F d, Y'))
                                            ->disabled()
                                            ->prefixIcon('heroicon-o-calendar')
                                            ->columnSpan(1),

                                        TextInput::make('last_login_display')
                                            ->label('Last Login')
                                            ->default(fn () => Auth::user()->last_login_at 
                                                ? Auth::user()->last_login_at->format('F d, Y H:i') . ' (' . Auth::user()->last_login_at->diffForHumans() . ')'
                                                : 'Never'
                                            )
                                            ->disabled()
                                            ->prefixIcon('heroicon-o-clock')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function updateProfile(): void
    {
        $data = $this->form->getState();

        $user = Auth::user();

        // Update basic information
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;

        // Update avatar if changed
        if (isset($data['avatar']) && $data['avatar'] !== $user->avatar) {
            $user->avatar = $data['avatar'];
        }

        // Update password if provided
        if (!empty($data['password'])) {
            // Verify current password
            if (!Hash::check($data['current_password'], $user->password)) {
                Notification::make()
                    ->title('Error')
                    ->body('Current password is incorrect.')
                    ->danger()
                    ->send();
                return;
            }

            $user->password = Hash::make($data['password']);
        }

        $user->save();

        // Clear password fields
        $this->form->fill([
            'current_password' => null,
            'password' => null,
            'password_confirmation' => null,
        ]);

        Notification::make()
            ->title('Profile Updated')
            ->body('Your profile has been updated successfully.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Changes')
                ->action('updateProfile')
                ->color('primary')
                ->icon('heroicon-o-check'),
        ];
    }
}
