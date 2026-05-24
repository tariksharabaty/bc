<?php

namespace App\Filament\Exports;

use App\Models\Shop;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ShopExporter extends Exporter
{
    protected static ?string $model = Shop::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('city')
                ->label('İl'),
            ExportColumn::make('district')
                ->label('İlçe'),
            ExportColumn::make('name')
                ->label('Dükkan Adı'),
            ExportColumn::make('user.name')
                ->label('Yetkili Kişi'),
            ExportColumn::make('address')
                ->label('Adres'),
            ExportColumn::make('phone')
                ->label('Telefon'),
            ExportColumn::make('is_active')
                ->label('Aktif'),
            ExportColumn::make('created_at')
                ->label('Oluşturulma Tarihi'),
            ExportColumn::make('updated_at')
                ->label('Güncellenme Tarihi'),
            ExportColumn::make('deleted_at')
                ->label('Silinme Tarihi'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your shop export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
