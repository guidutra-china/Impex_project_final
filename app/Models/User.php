<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasPanelShield;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'avatar',
        'phone',
        'status',
        'is_admin',
        'client_id',
        'email_verified_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',

    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Admin panel access
        if ($panel->getId() === 'admin') {
            return $this->hasRole(['super_admin', 'panel_user']) || $this->is_admin == 1;
        }

        // Portal panel access (for customer users)
        if ($panel->getId() === 'portal') {
            return $this->hasRole(['purchasing', 'finance', 'logistics']);
        }

        return false;
    }

    /**
     * Get the user's avatar URL for Filament.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Fallback to UI Avatars if no avatar uploaded
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get the client that owns the user (for portal users).
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
