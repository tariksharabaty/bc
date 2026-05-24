<?php

namespace App\Filament\Resources\Shops\Pages;

use App\Filament\Resources\Shops\ShopResource;
use App\Filament\Exports\ShopExporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListShops extends ListRecords
{
    protected static string $resource = ShopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
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
