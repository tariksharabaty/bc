<?php

namespace App\Filament\Resources\PiggyBanks\Pages;

use App\Filament\Resources\PiggyBanks\PiggyBankResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePiggyBank extends CreateRecord
{
    protected static string $resource = PiggyBankResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (filament()->getCurrentPanel()?->getId() === 'saha') {
            $data['assigned_to_user_id'] = auth()->id();
        }

        return $data;
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
