<?php

namespace App\Filament\Traits;

use App\Models\SavedFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

trait HasAdvancedFilters
{
    /**
     * Get date range filter
     */
    public static function getDateRangeFilter(string $column, string $label): Filter
    {
        return Filter::make($column . '_range')
            ->form([
                DatePicker::make($column . '_from')
                    ->label('From')
                    ->placeholder('Start date'),
                DatePicker::make($column . '_until')
                    ->label('Until')
                    ->placeholder('End date'),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                return $query
                    ->when(
                        $data[$column . '_from'],
                        fn (Builder $query, $date): Builder => $query->whereDate($column, '>=', $date),
                    )
                    ->when(
                        $data[$column . '_until'],
                        fn (Builder $query, $date): Builder => $query->whereDate($column, '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data) use ($column, $label): array {
                $indicators = [];

                if ($data[$column . '_from'] ?? null) {
                    $indicators[] = $label . ' from ' . \Carbon\Carbon::parse($data[$column . '_from'])->toFormattedDateString();
                }

                if ($data[$column . '_until'] ?? null) {
                    $indicators[] = $label . ' until ' . \Carbon\Carbon::parse($data[$column . '_until'])->toFormattedDateString();
                }

                return $indicators;
            });
    }

    /**
     * Get numeric range filter
     */
    public static function getNumericRangeFilter(
        string $column,
        string $label,
        bool $isCurrency = false,
        int $divisor = 1
    ): Filter {
        return Filter::make($column . '_range')
            ->form([
                TextInput::make($column . '_min')
                    ->label('Min ' . $label)
                    ->numeric()
                    ->placeholder('Minimum value')
                    ->prefix($isCurrency ? '$' : null),
                TextInput::make($column . '_max')
                    ->label('Max ' . $label)
                    ->numeric()
                    ->placeholder('Maximum value')
                    ->prefix($isCurrency ? '$' : null),
            ])
            ->query(function (Builder $query, array $data) use ($column, $divisor): Builder {
                return $query
                    ->when(
                        $data[$column . '_min'] ?? null,
                        fn (Builder $query, $value): Builder => $query->where($column, '>=', $value * $divisor),
                    )
                    ->when(
                        $data[$column . '_max'] ?? null,
                        fn (Builder $query, $value): Builder => $query->where($column, '<=', $value * $divisor),
                    );
            })
            ->indicateUsing(function (array $data) use ($column, $label, $isCurrency): array {
                $indicators = [];
                $prefix = $isCurrency ? '$' : '';

                if ($data[$column . '_min'] ?? null) {
                    $indicators[] = $label . ' ≥ ' . $prefix . number_format($data[$column . '_min'], 2);
                }

                if ($data[$column . '_max'] ?? null) {
                    $indicators[] = $label . ' ≤ ' . $prefix . number_format($data[$column . '_max'], 2);
                }

                return $indicators;
            });
    }

    /**
     * Get text search filter
     */
    public static function getTextSearchFilter(string $column, string $label, array $searchableColumns = []): Filter
    {
        return Filter::make($column . '_search')
            ->form([
                TextInput::make('search')
                    ->label('Search ' . $label)
                    ->placeholder('Enter search term...')
                    ->prefixIcon('heroicon-o-magnifying-glass'),
            ])
            ->query(function (Builder $query, array $data) use ($column, $searchableColumns): Builder {
                if (empty($data['search'])) {
                    return $query;
                }

                $searchTerm = '%' . $data['search'] . '%';
                $columns = empty($searchableColumns) ? [$column] : $searchableColumns;

                return $query->where(function (Builder $query) use ($columns, $searchTerm) {
                    foreach ($columns as $col) {
                        $query->orWhere($col, 'like', $searchTerm);
                    }
                });
            })
            ->indicateUsing(function (array $data) use ($label): array {
                if (empty($data['search'])) {
                    return [];
                }

                return [$label . ': "' . $data['search'] . '"'];
            });
    }

    /**
     * Get saved filters for current resource
     */
    public static function getSavedFilters(string $resourceType): array
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return [];
        }

        return SavedFilter::forResource($resourceType)
            ->accessibleBy($userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (SavedFilter $filter) {
                $label = $filter->name;
                if ($filter->is_default) {
                    $label .= ' ⭐';
                }
                if ($filter->is_public) {
                    $label .= ' 🌐';
                }
                return [$filter->id => $label];
            })
            ->toArray();
    }
}
