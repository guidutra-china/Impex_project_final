<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailableWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'widget_id',
        'title',
        'description',
        'class',
        'icon',
        'category',
        'is_available',
        'default_visible',
        'requires_permission',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'default_visible' => 'boolean',
    ];

    /**
     * Obter todos os widgets disponíveis
     */
    public static function getAvailable(): array
    {
        return self::where('is_available', true)
            ->get()
            ->pluck('widget_id')
            ->toArray();
    }

    /**
     * Obter widgets padrão (visíveis por padrão)
     */
    public static function getDefaults(): array
    {
        return self::where('default_visible', true)
            ->where('is_available', true)
            ->get()
            ->pluck('widget_id')
            ->toArray();
    }

    /**
     * Obter widgets por categoria
     */
    public static function getByCategory(string $category): array
    {
        return self::where('category', $category)
            ->where('is_available', true)
            ->get()
            ->pluck('widget_id')
            ->toArray();
    }

    /**
     * Obter widget por ID
     */
    public static function getById(string $widgetId): ?self
    {
        return self::where('widget_id', $widgetId)->first();
    }

    /**
     * Verificar se widget requer permissão
     */
    public function requiresPermission(): ?string
    {
        return $this->requires_permission;
    }

    /**
     * Obter todas as categorias
     */
    public static function getCategories(): array
    {
        return self::where('is_available', true)
            ->distinct()
            ->pluck('category')
            ->toArray();
    }
}
