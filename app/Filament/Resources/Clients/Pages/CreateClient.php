<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Repositories\ClientRepository;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected ?ClientRepository $clientRepository = null;

    public function mount(): void
    {
        parent::mount();
        $this->clientRepository = app(ClientRepository::class);
    }
}
