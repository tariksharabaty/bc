<?php

namespace App\Filament\Resources\PiggyBanks\Pages;

use App\Filament\Resources\PiggyBanks\PiggyBankResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * ViewPiggyBank Page Class
 * Handles rendering the read-only detail view of a physical donation piggy bank.
 * Automatically displays the configured infolist summary and attached relation timelines.
 *
 * ViewPiggyBank Sayfa Sınıfı
 * Fiziksel bağış kumbarasının salt okunur detay görünümünün işlenmesini yönetir.
 * Yapılandırılmış bilgi listesi özetini ve ekli ilişki zaman akışlarını otomatik olarak görüntüler.
 */
class ViewPiggyBank extends ViewRecord
{
    /** @var class-string<PiggyBankResource> */
    protected static string $resource = PiggyBankResource::class;
}
