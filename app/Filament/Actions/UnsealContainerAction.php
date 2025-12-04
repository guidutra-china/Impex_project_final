<?php

namespace App\Filament\Actions;

use App\Models\ShipmentContainer;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class UnsealContainerAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'unseal_container';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Unseal Container')
            ->icon('heroicon-o-lock-open')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Unseal Container')
            ->modalDescription('Are you sure you want to unseal this container? This action can be reversed.')
            ->action(function (ShipmentContainer $record): void {
                $record->update([
                    'status' => 'packed',
                    'seal_number' => null,
                    'sealed_at' => null,
                ]);
            })
            ->successNotificationTitle('Container unsealed successfully')
            ->visible(fn (Model $record): bool => $record->status === 'sealed');
    }
}
