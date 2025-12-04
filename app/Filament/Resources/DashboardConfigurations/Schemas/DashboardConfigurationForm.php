<?php

namespace App\Filament\Resources\DashboardConfigurations\Schemas;

use App\Models\AvailableWidget;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DashboardConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Widgets Visíveis')
                    ->description('Selecione quais widgets deseja visualizar')
                    ->schema([
                        CheckboxList::make('visible_widgets')
                            ->label('Widgets')
                            ->options(function () {
                                return AvailableWidget::where('is_available', true)
                                    ->get()
                                    ->mapWithKeys(fn($w) => [$w->widget_id => $w->title])
                                    ->toArray();
                            })
                            ->required(),
                    ]),

                Section::make('Ordem dos Widgets')
                    ->description('Arraste para reordenar os widgets')
                    ->schema([
                        Textarea::make('widget_order')
                            ->label('Ordem (JSON)')
                            ->rows(5)
                            ->disabled()
                            ->hint('Use a interface de personalização para reordenar'),
                    ]),
            ]);
    }
}
