<?php

namespace App\Filament\Exports;

use App\Models\PiggyBank;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class PiggyBankExporter extends Exporter
{
    protected static ?string $model = PiggyBank::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('unique_box_id')
                ->label('Kumbara ID'),
            ExportColumn::make('shop.name')
                ->label('Dükkan'),
            ExportColumn::make('user.name')
                ->label('Sorumlu Personel'),
            ExportColumn::make('name')
                ->label('Ad / Etiket'),
            ExportColumn::make('current_balance')
                ->label('Bakiye (TL)'),
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
        $body = 'Your piggy bank export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
