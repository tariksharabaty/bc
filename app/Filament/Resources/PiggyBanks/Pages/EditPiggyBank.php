<?php

namespace App\Filament\Resources\PiggyBanks\Pages;

use App\Filament\Resources\PiggyBanks\PiggyBankResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPiggyBank extends EditRecord
{
    protected static string $resource = PiggyBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => filament()->getCurrentPanel()?->getId() === 'saha'),
        ];
    }
}
