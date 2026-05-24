<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Filament\Exports\TransactionExporter;
use Filament\Actions\ExportAction;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tümü'),
            'money' => Tab::make('Para')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('donation_category', 'money')),
            'qurbani' => Tab::make('Kurban')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('donation_category', 'qurbani')),
            'food' => Tab::make('Gıda')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('donation_category', 'food')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \pxlrbt\FilamentExcel\Actions\ExportAction::make()
                ->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make('excel')
                        ->fromTable()
                        ->withFilename('export')
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX),
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make('csv')
                        ->fromTable()
                        ->withFilename('export')
                        ->withWriterType(\Maatwebsite\Excel\Excel::CSV),
                ])
                ->label('Dışa Aktar'),
        ];
    }
}
