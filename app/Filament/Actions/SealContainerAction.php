<?php

namespace App\Filament\Actions;

use App\Models\ShipmentContainer;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class SealContainerAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'seal_container';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Seal Container')
            ->icon('heroicon-o-lock-closed')
            ->color('success')
            ->requiresConfirmation()
            ->form([
                TextInput::make('seal_number')
                    ->label('Seal Number')
                    ->required()
                    ->placeholder('e.g., SEAL123456')
                    ->unique('shipment_containers', 'seal_number', ignoreRecord: true),
            ])
            ->action(function (ShipmentContainer $record, array $data): void {
                $record->update([
                    'status' => 'sealed',
                    'seal_number' => $data['seal_number'],
                    'sealed_at' => now(),
                ]);
            })
            ->successNotification()
            ->visible(fn (Model $record): bool => $record->status === 'packed');
    }
}
