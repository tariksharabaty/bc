<?php

namespace App\Filament\Resources\Shops;

use App\Filament\Resources\Shops\Pages\CreateShop;
use App\Filament\Resources\Shops\Pages\EditShop;
use App\Filament\Resources\Shops\Pages\ListShops;
use App\Models\Shop;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as FormSchema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use App\Filament\Exports\ShopExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ShopResource Class
 * Filament admin resource for managing partner shops (dükkanlar) — the physical
 * locations where donation piggy banks are placed and collected from.
 *
 * ShopResource Sınıfı
 * Bağış kumbaralarının yerleştirilip tahsil edildiği ortak dükkanların (partner noktaların)
 * yönetimi için Filament yönetim kaynağı.
 */
class ShopResource extends Resource
{
    /** @var class-string<Shop> */
    protected static ?string $model = Shop::class;

    public static function getModelLabel(): string
    {
        return __('system.shop');
    }

    public static function getPluralModelLabel(): string
    {
        return __('system.shops');
    }

    public static function getNavigationLabel(): string
    {
        return __('system.shops');
    }

    /** Navigation sidebar icon. — Kenar çubuğu simgesi. */
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    /** Searchable record title attribute. — Aranabilir kayıt başlık niteliği. */
    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Define the attributes searchable globally.
     * Küresel aramada aranabilir nitelikleri tanımlar.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'city', 'district'];
    }

    /**
     * Return custom context/details for global search results.
     * Küresel arama sonuçları için özel bağlam/detaylar döndürür.
     *
     * @param Model $record
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('system.city')   => $record->city,
            __('system.district') => $record->district,
        ];
    }

    /**
     * Build the base Eloquent query, eager-loading the piggyBanks relation
     * to prevent N+1 queries when the relation manager is rendered.
     *
     * PiggyBanks ilişkisini önceden yükleyerek (eager load) N+1 sorgularını önleyen
     * temel Eloquent sorgusunu oluşturur.
     *
     * @return Builder<Shop>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ])
            ->with(['piggyBanks']);
    }

    /**
     * Define the create/edit form schema.
     * Oluşturma/düzenleme form şemasını tanımlar.
     *
     * @param FormSchema $form
     * @return FormSchema
     */
    public static function form(FormSchema $form): FormSchema
    {
        return $form
            ->components([
                Forms\Components\TextInput::make('city')
                    ->label(__('system.city'))
                    ->required()
                    ->helperText('Dükkanın bulunduğu ili giriniz (Örn: İstanbul)'),
                Forms\Components\TextInput::make('district')
                    ->label(__('system.district'))
                    ->required()
                    ->helperText('Dükkanın bulunduğu ilçeyi giriniz (Örn: Fatih)'),
                Forms\Components\TextInput::make('name')
                    ->label(__('system.shop_name'))
                    ->required()
                    ->helperText('Dükkanın tabelada görünen tam ticari adını yazınız (Örn: Lezzet Börekçisi)'),
                Forms\Components\Textarea::make('address')
                    ->label(__('system.address'))
                    ->columnSpanFull()
                    ->helperText('Mahalle, cadde, sokak ve kapı numarası dahil tam açık adresi buraya yazınız'),
                Forms\Components\TextInput::make('phone')
                    ->label(__('system.phone'))
                    ->tel()
                    ->helperText('İrtibat için dükkan sahibinin veya yetkilisinin telefon numarasını giriniz'),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('system.is_active'))
                    ->required()
                    ->default(true)
                    ->helperText('Dükkanın aktif olup olmadığını belirler. Pasif dükkanlara yeni kumbara zimmetlenemez.'),
            ]);
    }

    /**
     * Define the resource list table with columns, filters, and row actions.
     * Kayıtların listeleneceği tabloyu sütunlar, filtreler ve satır eylemleriyle tanımlar.
     *
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('city')
                    ->label(__('system.city'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('district')
                    ->label(__('system.district'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('system.shop_name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('system.phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('system.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('piggy_banks_count')
                    ->label(__('system.piggy_banks_count'))
                    ->counts('piggyBanks')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('system.created_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('system.updated_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city')
                    ->label(__('system.city'))
                    ->options(fn () => Shop::distinct()->pluck('city', 'city')->toArray()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('system.active_status'))
                    ->trueLabel(__('system.only_active'))
                    ->falseLabel(__('system.only_passive')),
                Tables\Filters\TrashedFilter::make()
                    ->label(__('system.deleted')),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    \Filament\Actions\RestoreAction::make(),
                    \Filament\Actions\ForceDeleteAction::make(),
                ])
                ->label('Eylemler')
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                ]),
                \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()
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
                    ->label(__('system.export')),
            ])
            ->defaultSort('city');
    }

    /**
     * Get the relation managers registered for this resource.
     * Bu kaynak için kayıtlı ilişki yöneticilerini getirir.
     *
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Get the page route definitions for this resource.
     * Bu kaynak için sayfa yönlendirme tanımlarını getirir.
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index'  => ListShops::route('/'),
            'create' => CreateShop::route('/create'),
            'edit'   => EditShop::route('/{record}/edit'),
        ];
    }
}
