<?php

namespace App\Filament\Resources\Shops\Pages;

use App\Filament\Resources\Shops\ShopResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShop extends CreateRecord
{
    protected static string $resource = ShopResource::class;
    
    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->hidden();
    }
}
