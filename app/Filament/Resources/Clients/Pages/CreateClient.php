<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Repositories\ClientRepository;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected ClientRepository $clientRepository;

    public function __construct()
    {
        parent::__construct();
        $this->clientRepository = app(ClientRepository::class);
    }
}
