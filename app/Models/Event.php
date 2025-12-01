<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start',
        'end',
        'all_day',
        'color',
        'event_type',
        'related_type',
        'related_id',
        'is_automatic',
        'is_completed',
        'reminder_sent',
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'all_day' => 'boolean',
        'is_automatic' => 'boolean',
        'is_completed' => 'boolean',
        'reminder_sent' => 'boolean',
    ];

    /**
     * Event types
     */
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_SHIPMENT = 'shipment';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_DEADLINE = 'deadline';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_OTHER = 'other';

    public static function getEventTypes(): array
    {
        return [
            self::TYPE_PAYMENT => 'Pagamento',
            self::TYPE_SHIPMENT => 'Chegada de Remessa',
            self::TYPE_DOCUMENT => 'Envio de Documento',
            self::TYPE_MEETING => 'Reunião',
            self::TYPE_DEADLINE => 'Prazo',
            self::TYPE_REMINDER => 'Lembrete',
            self::TYPE_OTHER => 'Outro',
        ];
    }

    /**
     * Event colors by type
     */
    public static function getEventColors(): array
    {
        return [
            self::TYPE_PAYMENT => '#ef4444',      // Red
            self::TYPE_SHIPMENT => '#3b82f6',     // Blue
            self::TYPE_DOCUMENT => '#f59e0b',     // Amber
            self::TYPE_MEETING => '#8b5cf6',      // Purple
            self::TYPE_DEADLINE => '#dc2626',     // Dark Red
            self::TYPE_REMINDER => '#10b981',     // Green
            self::TYPE_OTHER => '#6b7280',        // Gray
        ];
    }

    /**
     * Get default color for event type
     */
    public function getDefaultColor(): string
    {
        return self::getEventColors()[$this->event_type] ?? '#6b7280';
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model (polymorphic)
     */
    public function related()
    {
        if (!$this->related_type || !$this->related_id) {
            return null;
        }

        return $this->related_type::find($this->related_id);
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start', '>=', now())
            ->orderBy('start', 'asc');
    }

    public function scopeOverdue($query)
    {
        return $query->where('start', '<', now())
            ->where('is_completed', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Helper methods
     */
    public function markAsCompleted(): void
    {
        $this->update(['is_completed' => true]);
    }

    public function markReminderSent(): void
    {
        $this->update(['reminder_sent' => true]);
    }

    public function isOverdue(): bool
    {
        return $this->start < now() && !$this->is_completed;
    }

    public function isToday(): bool
    {
        return $this->start->isToday();
    }

    public function isThisWeek(): bool
    {
        return $this->start->isCurrentWeek();
    }
}
