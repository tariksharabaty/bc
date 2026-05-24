<?php

namespace App\Filament\Exports;

use App\Models\Transaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class TransactionExporter extends Exporter
{
    protected static ?string $model = Transaction::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('piggyBank.unique_box_id')
                ->label('Kumbara ID'),
            ExportColumn::make('piggyBank.shop.name')
                ->label('Dükkan'),
            ExportColumn::make('user.name')
                ->label('Personel'),
            ExportColumn::make('action_type')
                ->label('İşlem Türü'),
            ExportColumn::make('amount')
                ->label('Tutar (TL)'),
            ExportColumn::make('description')
                ->label('Açıklama'),
            ExportColumn::make('created_at')
                ->label('İşlem Tarihi'),
            ExportColumn::make('updated_at')
                ->label('Güncellenme Tarihi'),
            ExportColumn::make('deleted_at')
                ->label('Silinme Tarihi'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your transaction export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
