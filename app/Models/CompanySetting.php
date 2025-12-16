<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'logo_path',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'phone',
        'email',
        'website',
        'tax_id',
        'registration_number',
        'bank_name',
        'bank_account_number',
        'bank_routing_number',
        'bank_swift_code',
        'footer_text',
        'invoice_prefix',
        'quote_prefix',
        'po_prefix',
        'rfq_default_instructions',
        'po_terms',
        'packing_list_prefix',
        'commercial_invoice_prefix',
    ];

    /**
     * Get the singleton instance of company settings
     */
    public static function current(): ?self
    {
        return Cache::remember('company_settings', 3600, function () {
            return self::first();
        });
    }

    /**
     * Get the full logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        if (Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->url($this->logo_path);
        }

        return null;
    }

    /**
     * Get the full logo path for PDF generation
     */
    public function getLogoFullPathAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        $path = storage_path('app/public/' . $this->logo_path);
        
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Get formatted full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state . ' ' . $this->zip_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Clear the cache when model is saved
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('company_settings');
        });

        static::deleted(function () {
            Cache::forget('company_settings');
        });
    }
}
