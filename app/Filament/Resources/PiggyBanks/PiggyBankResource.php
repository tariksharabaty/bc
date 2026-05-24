<?php

namespace App\Filament\Resources\PiggyBanks;

use App\Filament\Resources\PiggyBanks\Pages\CreatePiggyBank;
use App\Filament\Resources\PiggyBanks\Pages\EditPiggyBank;
use App\Filament\Resources\PiggyBanks\Pages\ListPiggyBanks;
use App\Filament\Resources\PiggyBanks\Pages\ViewPiggyBank;
use App\Filament\Resources\PiggyBanks\RelationManagers;
use App\Models\PiggyBank;
use App\Models\Transaction;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use App\Filament\Exports\PiggyBankExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

/**
 * PiggyBankResource Class
 * Filament admin resource for managing physical donation piggy banks (kumbara).
 * Supports full CRUD, QR code generation/download/print, balance collection,
 * and balance reset operations. Access is role-scoped: field agents only see
 * their own assigned boxes.
 *
 * PiggyBankResource Sınıfı
 * Fiziksel bağış kumbaralarının yönetimi için Filament yönetim kaynağı.
 * Tam CRUD, QR kod üretme/indirme/yazdırma, bakiye tahsili ve sıfırlama
 * işlemlerini destekler. Erişim rol bazlıdır: saha personeli yalnızca
 * zimmetindeki kutuları görür.
 */
class PiggyBankResource extends Resource
{
    /** @var class-string<PiggyBank> */
    protected static ?string $model = PiggyBank::class;

    public static function getModelLabel(): string
    {
        return __('system.piggy_bank');
    }

    public static function getPluralModelLabel(): string
    {
        return __('system.piggy_banks');
    }

    public static function getNavigationLabel(): string
    {
        return __('system.piggy_banks');
    }

    /** Navigation sidebar icon. — Kenar çubuğu simgesi. */
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    /** Searchable record title attribute. — Aranabilir kayıt başlık niteliği. */
    protected static ?string $recordTitleAttribute = 'unique_box_id';

    /**
     * Define the attributes searchable globally.
     * Küresel aramada aranabilir nitelikleri tanımlar.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['unique_box_id', 'name'];
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
            __('system.shop') => $record->shop?->name ?? __('system.shop_not_assigned'),
            __('system.current_balance') => number_format($record->current_balance, 2, ',', '.') . ' ₺',
        ];
    }

    /**
     * Build the base Eloquent query for this resource.
     * Eager-loads `shop` and `user` relations to eliminate N+1 queries.
     * Field agents are automatically scoped to their own assigned piggy banks.
     *
     * Bu kaynak için temel Eloquent sorgusunu oluşturur.
     * N+1 sorgularını önlemek için `shop` ve `user` ilişkilerini önceden yükler.
     * Saha personeli otomatik olarak kendi zimmetindeki kumbaralarla kısıtlanır.
     *
     * @return Builder<PiggyBank>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ])
            ->with(['shop', 'user']);

        if (auth()->user()->role === 'field_agent') {
            $query->where('assigned_to_user_id', auth()->id());
        }

        return $query;
    }

    /**
     * Define the create/edit form schema.
     * Oluşturma/düzenleme form şemasını tanımlar.
     *
     * @param Schema $form
     * @return Schema
     */
    public static function form(Schema $form): Schema
    {
        return $form
            ->components([
                \Filament\Schemas\Components\Section::make(__('system.piggy_bank_details'))
                    ->schema([
                        Forms\Components\TextInput::make('unique_box_id')
                            ->label(__('system.unique_box_id'))
                            ->default(fn () => 'KMB-' . (\App\Models\PiggyBank::max('id') + 1))
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Sistem tarafından otomatik üretilen benzersiz kumbara kodudur. QR kod bu değerle eşleşir.'),

                        Forms\Components\Select::make('shop_id')
                            ->relationship('shop', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->label(__('system.shop'))
                            ->required()
                            ->helperText('Kumbaranın fiziksel olarak bırakılacağı aktif anlaşmalı dükkanı seçiniz.')
                            ->createOptionForm([
                                \Filament\Schemas\Components\Section::make(__('system.shop'))
                                    ->schema([
                                        Forms\Components\Select::make('country')
                                            ->label(__('system.country'))
                                            ->options(['Türkiye' => 'Türkiye'])
                                            ->live()
                                            ->afterStateUpdated(function ($set) {
                                                $set('city', null);
                                                $set('district', null);
                                            }),
                                        Forms\Components\Select::make('city')
                                            ->label(__('system.city'))
                                            ->options(function ($get) {
                                                if ($get('country') !== 'Türkiye') {
                                                    return [];
                                                }
                                                try {
                                                    $response = Http::timeout(3)
                                                        ->get('https://turkiyeapi.dev/api/v1/provinces')
                                                        ->json('data') ?? [];
                                                    return collect($response)->pluck('name', 'name')->toArray();
                                                } catch (\Exception $e) {
                                                    return ['İstanbul' => 'İstanbul'];
                                                }
                                            })
                                            ->live()
                                            ->afterStateUpdated(function ($set) {
                                                $set('district', null);
                                            }),
                                        Forms\Components\Select::make('district')
                                            ->label(__('system.district'))
                                            ->options(function ($get) {
                                                $cityName = $get('city');
                                                if (! $cityName) {
                                                    return [];
                                                }
                                                try {
                                                    $provinces = Http::timeout(3)
                                                        ->get('https://turkiyeapi.dev/api/v1/provinces')
                                                        ->json('data') ?? [];
                                                    $province = collect($provinces)->firstWhere('name', $cityName);
                                                    if (! $province) {
                                                        return [];
                                                    }
                                                    $response = Http::timeout(3)
                                                        ->get("https://turkiyeapi.dev/api/v1/provinces/{$province['id']}")
                                                        ->json('data.districts') ?? [];
                                                    return collect($response)->pluck('name', 'name')->toArray();
                                                } catch (\Exception $e) {
                                                    return ['Fatih' => 'Fatih', 'Üsküdar' => 'Üsküdar'];
                                                }
                                            })
                                            ->live(),
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('system.shop_name'))
                                            ->required(),
                                        Forms\Components\Select::make('user_id')
                                            ->label(__('system.authorized_person'))
                                            ->relationship('user', 'name')
                                            ->searchable()
                                            ->preload(),
                                        Forms\Components\TextInput::make('phone')
                                            ->label(__('system.phone')),
                                    ])->columns(2)
                            ]),

                        Forms\Components\Select::make('assigned_to_user_id')
                            ->relationship('user', 'name', fn (Builder $query) => $query->where('is_active', true)->where('role', 'field_agent'))
                            ->label(__('system.assigned_to_user_id'))
                            ->hidden(fn () => filament()->getCurrentPanel()?->getId() === 'saha')
                            ->default(fn () => auth()->id())
                            ->helperText('Kumbaranın bakiye takibinden ve tahsilatından sorumlu olacak saha elemanını seçiniz.'),

                        Forms\Components\TextInput::make('name')
                            ->label(__('system.name'))
                            ->helperText('Varsa bu kumbaraya tanımlamak istediğiniz özel bir isim yazabilirsiniz (Örn: Giriş Kasası Kumbarası)'),

                        Forms\Components\TextInput::make('current_balance')
                            ->label(__('system.current_balance_tl'))
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Kutunun başlangıç bakiyesini veya içindeki mevcut birikmiş miktarı giriniz.'),

                        Forms\Components\Select::make('donation_category')
                            ->label(__('system.donation_category') ?? 'Kategori')
                            ->options([
                                'money' => 'Para',
                                'qurbani' => 'Kurban',
                                'food' => 'Gıda Paketleri',
                            ])
                            ->default('money')
                            ->live()
                            ->helperText('Bu kumbaranın hangi yardım kategorisinde bağış toplayacağını seçiniz. Seçime göre form alanları güncellenir.'),

                        Forms\Components\Select::make('category_details.qurbani_type')
                            ->label('Kurban Türü')
                            ->options([
                                'kucukbas' => 'Küçükbaş',
                                'buyukbas' => 'Büyükbaş',
                            ])
                            ->visible(fn ($get) => $get('donation_category') === 'qurbani')
                            ->required(fn ($get) => $get('donation_category') === 'qurbani')
                            ->helperText('Bu kurban kumbarasının varsayılan hayvan türünü seçiniz.'),

                        Forms\Components\TextInput::make('category_details.food_package_count')
                            ->label('Erzak Kolisi Adedi')
                            ->numeric()
                            ->visible(fn ($get) => $get('donation_category') === 'food')
                            ->required(fn ($get) => $get('donation_category') === 'food')
                            ->helperText('Bu erzak kumbarasının hedeflenen varsayılan koli miktarını veya kapasitesini giriniz.'),
                    ])->columns(2),

                \Filament\Schemas\Components\Actions::make([
                    \Filament\Actions\Action::make('download_qr')
                        ->label(__('system.qr_actions'))
                        ->icon('heroicon-o-qr-code')
                        ->color('info')
                        ->visible(fn ($record) => $record !== null)
                        ->modalHeading(__('system.piggy_bank_qr'))
                        ->modalDescription(__('system.qr_modal_description'))
                        ->modalContent(fn ($record) => new \Illuminate\Support\HtmlString('
                            <div class="flex flex-col items-center justify-center p-6 text-center space-y-6">
                                <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 flex items-center justify-center">
                                    ' . \SimpleSoftwareIO\QrCode\Facades\QrCode::size(250)->format('svg')->generate($record->unique_box_id) . '
                                </div>
                                <p class="text-lg font-bold font-poppins text-gray-800 tracking-wider">' . $record->unique_box_id . '</p>
                                <div class="flex gap-4 w-full justify-center">
                                    <a href="' . route('piggy-banks.qr-download', $record) . '" class="inline-flex items-center justify-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-lg shadow transition-colors duration-200 gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        ' . __('system.download_svg') . '
                                    </a>
                                    <a href="' . route('piggy-banks.qr-print', $record) . '" target="_blank" class="inline-flex items-center justify-center px-4 py-2 bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-medium rounded-lg shadow transition-colors duration-200 gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        ' . __('system.print_pdf') . '
                                    </a>
                                </div>
                            </div>
                        '))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(__('system.close')),
                ])->columnSpanFull(),
            ]);
    }

    /**
     * Define the summary infolist for the record details.
     * Kayıt detayları için özet bilgi listesini tanımlar.
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // 1. Money Category Infolist Section
                \Filament\Schemas\Components\Section::make('Nakit Para Kumbarası Detayları')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn ($record) => $record?->donation_category === 'money')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('unique_box_id')
                            ->label('Kumbara Kodu')
                            ->icon('heroicon-o-qr-code')
                            ->weight('bold'),
                        \Filament\Infolists\Components\TextEntry::make('shop.name')
                            ->label(__('system.shop_name'))
                            ->icon('heroicon-o-building-storefront')
                            ->weight('bold'),
                        \Filament\Infolists\Components\TextEntry::make('user.name')
                            ->label(__('system.assigned_to_user_id'))
                            ->icon('heroicon-o-user')
                            ->default('Zimmet Atanmamış'),
                        \Filament\Infolists\Components\TextEntry::make('current_balance')
                            ->label('Biriken Nakit Tutar')
                            ->icon('heroicon-o-banknotes')
                            ->formatStateUsing(fn ($state) => (float) $state > 0 ? number_format((float) $state, 2, ',', '.') . ' ₺' : '')
                            ->weight('black')
                            ->color('success'),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Oluşturulma Tarihi')
                            ->icon('heroicon-o-calendar')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(['default' => 1, 'sm' => 2, 'md' => 3]),

                // 2. Qurbani Category Infolist Section
                \Filament\Schemas\Components\Section::make('Kurban Kumbarası / Hisse Detayları')
                    ->icon('heroicon-o-heart')
                    ->visible(fn ($record) => $record?->donation_category === 'qurbani')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('unique_box_id')
                            ->label('Kumbara Kodu')
                            ->icon('heroicon-o-qr-code')
                            ->weight('bold'),
                        \Filament\Infolists\Components\TextEntry::make('shop.name')
                            ->label(__('system.shop_name'))
                            ->icon('heroicon-o-building-storefront')
                            ->weight('bold'),
                        \Filament\Infolists\Components\TextEntry::make('user.name')
                            ->label(__('system.assigned_to_user_id'))
                            ->icon('heroicon-o-user')
                            ->default('Zimmet Atanmamış'),
                        \Filament\Infolists\Components\TextEntry::make('current_balance')
                            ->label('Biriken Kurban Hissesi')
                            ->icon('heroicon-o-gift')
                            ->formatStateUsing(fn ($state) => (int) $state > 0 ? (int) $state . ' Hisse/Adet' : '')
                            ->weight('black')
                            ->color('danger'),
                        \Filament\Infolists\Components\TextEntry::make('category_details.qurbani_type')
                            ->label('Kurban Cinsi')
                            ->icon('heroicon-o-tag')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'kucukbas' => 'Küçükbaş',
                                'buyukbas' => 'Büyükbaş',
                                default => 'Kurban'
                            }),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Oluşturulma Tarihi')
                            ->icon('heroicon-o-calendar')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(['default' => 1, 'sm' => 2, 'md' => 3]),

                // 3. Food Category Infolist Section
                \Filament\Schemas\Components\Section::make('Erzak Kumbarası / Bağış Detayları')
                    ->icon('heroicon-o-shopping-bag')
                    ->visible(fn ($record) => $record?->donation_category === 'food')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('unique_box_id')
                            ->label('Kumbara Kodu')
                            ->icon('heroicon-o-qr-code')
                            ->weight('bold'),
                        \Filament\Infolists\Components\TextEntry::make('shop.name')
                            ->label(__('system.shop_name'))
                            ->icon('heroicon-o-building-storefront')
                            ->weight('bold'),
                        \Filament\Infolists\Components\TextEntry::make('user.name')
                            ->label(__('system.assigned_to_user_id'))
                            ->icon('heroicon-o-user')
                            ->default('Zimmet Atanmamış'),
                        \Filament\Infolists\Components\TextEntry::make('current_balance')
                            ->label('Biriken Erzak Miktarı')
                            ->icon('heroicon-o-archive-box')
                            ->formatStateUsing(function ($state, PiggyBank $record) {
                                if ((int)$state === 0) {
                                    return '';
                                }
                                $unit = match($record->category_details['food_unit'] ?? '') {
                                    'koli' => 'Koli',
                                    'kg' => 'Kg',
                                    'cuval' => 'Çuval',
                                    'adet' => 'Adet',
                                    'litre' => 'Litre',
                                    default => 'Birim'
                                };
                                $itemName = $record->category_details['food_item_name'] ?? '';
                                $text = (int)$state . ' ' . $unit;
                                if ($itemName) {
                                    $text .= ' (' . $itemName . ')';
                                }
                                return $text;
                            })
                            ->weight('black')
                            ->color('warning'),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Oluşturulma Tarihi')
                            ->icon('heroicon-o-calendar')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(['default' => 1, 'sm' => 2, 'md' => 3]),

                // 4. Recent Collections & Resets History
                \Filament\Schemas\Components\Section::make('Son Tahsilat ve Sıfırlama Hareketleri')
                    ->icon('heroicon-o-clock')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('transactions')
                            ->label('')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('created_at')
                                    ->label('İşlem Tarihi')
                                    ->icon('heroicon-o-calendar')
                                    ->dateTime('d/m/Y H:i'),
                                \Filament\Infolists\Components\TextEntry::make('user.name')
                                    ->label('İşlemi Yapan')
                                    ->icon('heroicon-o-user')
                                    ->default('Bilinmeyen Kullanıcı'),
                                \Filament\Infolists\Components\TextEntry::make('action_type')
                                    ->label('İşlem Türü')
                                    ->badge()
                                    ->formatStateUsing(fn ($state, $record) => $state === 'reset' ? 'Sıfırlama' : match ($record->donation_category) {
                                        'money' => 'Para Alındı',
                                        'qurbani' => 'Kurban Kaydedildi',
                                        'food' => 'Erzak Eklendi',
                                        default => $state,
                                    })
                                    ->color(fn ($state) => $state === 'reset' ? 'danger' : 'success'),
                                \Filament\Infolists\Components\TextEntry::make('amount')
                                    ->label('Miktar / Değer')
                                    ->weight('bold')
                                    ->formatStateUsing(fn ($state, $record) => match ($record->donation_category) {
                                        'money' => number_format((float) $state, 2, ',', '.') . ' ₺',
                                        'qurbani' => (int) $state . ' Hisse/Adet',
                                        'food' => (int) $state . ' Birim',
                                        default => $state,
                                    }),
                            ])
                            ->columns(['default' => 1, 'sm' => 2, 'md' => 4])
                            ->limit(5)
                            ->emptyStateHeading('Henüz bir işlem hareketi kaydedilmemiş.')
                            ->emptyStateIcon('heroicon-o-clock')
                    ]),
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
                Tables\Columns\TextColumn::make('unique_box_id')
                    ->label(__('system.unique_box_id'))
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('shop.name')
                    ->label(__('system.shop'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('shop.district')
                    ->label(__('system.district'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('system.assigned_to_user_id'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('system.name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('current_balance')
                    ->label(__('system.current_balance') ?? 'Bakiye / Miktar')
                    ->fontFamily('mono')
                    ->formatStateUsing(fn ($state, PiggyBank $record): string => match ($record->donation_category) {
                        'money' => (float) $state > 0 ? number_format((float) $state, 2, ',', '.') . ' ₺' : '',
                        'qurbani' => (int) $state > 0 ? (int) $state . ' Hisse/Adet' : '',
                        'food' => (int) $state > 0 ? (int) $state . ' Birim' : '',
                        default => $state,
                    })
                    ->sortable(),
                 Tables\Columns\TextColumn::make('donation_category')
                      ->label(__('system.donation_category') ?? 'Kategori')
                      ->badge()
                      ->formatStateUsing(fn (string $state): string => match ($state) {
                          'money' => 'Para',
                          'qurbani' => 'Kurban',
                          'food' => 'Gıda Paketleri',
                          default => $state,
                      })
                      ->color(fn (string $state): string => match ($state) {
                          'money' => 'gray',
                          'qurbani' => 'warning',
                          'food' => 'success',
                          default => 'gray',
                      })
                      ->description(fn (PiggyBank $record) => $record->donation_category === 'qurbani' ? (isset($record->category_details['qurbani_type']) ? ($record->category_details['qurbani_type'] === 'kucukbas' ? 'Küçükbaş' : 'Büyükbaş') : null) : null)
                      ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('system.status'))
                    ->getStateUsing(fn ($record) => $record->current_balance >= 1000 ? __('system.full') : __('system.active'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        __('system.active') => 'success',
                        __('system.full') => 'danger',
                        default => 'gray',
                    })
                    ->sortable(false),
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
                Tables\Filters\SelectFilter::make('shop_id')
                    ->relationship('shop', 'name')
                    ->label(__('system.shop')),
                Tables\Filters\SelectFilter::make('assigned_to_user_id')
                    ->relationship('user', 'name')
                    ->label(__('system.assigned_to_user_id'))
                    ->hidden(fn () => filament()->getCurrentPanel()?->getId() === 'saha'),
                Tables\Filters\TrashedFilter::make()
                    ->label(__('system.deleted'))
                    ->hidden(fn () => filament()->getCurrentPanel()?->getId() === 'saha'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    EditAction::make(),
                    Action::make('download_qr')
                        ->label(__('system.download_qr'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->url(fn ($record) => route('piggy-banks.qr-download', $record)),
                    Action::make('print_qr')
                        ->label(__('system.print_qr'))
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn ($record) => route('piggy-banks.qr-print', $record))
                        ->openUrlInNewTab(),
                    Action::make('tahsilat_ekle')
                        ->label(fn (PiggyBank $record) => match($record->donation_category) {
                            'money' => 'Tahsilat Ekle',
                            'qurbani' => 'Kurban Kaydet',
                            'food' => 'Erzak Eklendi',
                            default => 'İşlem Ekle',
                        })
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form(fn (PiggyBank $record) => match ($record->donation_category) {
                            'money' => [
                                \Filament\Forms\Components\TextInput::make('amount')
                                    ->label('Tutar (₺)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01),
                            ],
                            'qurbani' => [
                                \Filament\Forms\Components\Select::make('category_details.qurbani_type')
                                    ->label('Kurban Bağış Türü')
                                    ->options([
                                        'koyun' => 'Koyun (Küçükbaş)',
                                        'koc' => 'Koç (Küçükbaş)',
                                        'keci' => 'Keçi (Küçükbaş)',
                                        'dana' => 'Dana (Büyükbaş)',
                                        'deve' => 'Deve (Büyükbaş)',
                                        'buyukbas_hisse' => 'Büyükbaş Hisse (1/7 Hisse)',
                                        'buyukbas_tam' => 'Büyükbaş Tam (7 Hisse)',
                                    ])
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('amount')
                                    ->label('Adet / Hisse Sayısı')
                                    ->numeric()
                                    ->integer()
                                    ->required()
                                    ->minValue(1),
                            ],
                            'food' => [
                                \Filament\Forms\Components\TextInput::make('category_details.food_item_name')
                                    ->label('Ürün Adı / Cinsi')
                                    ->placeholder('Örn: Pirinç, Patates, Un, Yağ, Erzak Kolisi')
                                    ->required(),
                                \Filament\Forms\Components\TextInput::make('amount')
                                    ->label('Miktar / Değer')
                                    ->numeric()
                                    ->integer()
                                    ->required()
                                    ->minValue(1),
                                \Filament\Forms\Components\Select::make('category_details.food_unit')
                                    ->label('Ölçü Birimi')
                                    ->options([
                                        'koli' => 'Koli',
                                        'kg' => 'Kg',
                                        'cuval' => 'Çuval',
                                        'adet' => 'Adet',
                                        'litre' => 'Litre',
                                    ])
                                    ->required(),
                            ],
                            default => [
                                \Filament\Forms\Components\TextInput::make('amount')
                                    ->numeric()
                                    ->required(),
                            ]
                        })
                        ->action(function ($record, array $data): void {
                            $record->current_balance += (float) $data['amount'];
                            
                            $details = array_merge(
                                $record->category_details ?? [],
                                $data['category_details'] ?? []
                            );
                            $record->category_details = $details;
                            $record->save();

                            Transaction::create([
                                'piggy_bank_id' => $record->id,
                                'user_id'       => auth()->id(),
                                'action_type'   => 'collection',
                                'amount'        => $data['amount'],
                                'donation_category' => $record->donation_category,
                                'category_details' => $details,
                            ]);
                        }),
                    Action::make('sifirla')
                        ->label(__('system.collect_and_reset'))
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('system.reset_piggy_bank'))
                        ->modalDescription(__('system.reset_modal_description'))
                        ->visible(fn () => auth()->user()->role === 'super_admin')
                        ->action(function ($record): void {
                            $amount = (float) $record->current_balance;
                            if ($amount > 0) {
                                Transaction::create([
                                    'piggy_bank_id' => $record->id,
                                    'user_id'       => auth()->id(),
                                    'action_type'   => 'reset',
                                    'amount'        => $amount,
                                    'donation_category' => $record->donation_category,
                                    'category_details' => $record->category_details,
                                ]);
                                $record->current_balance = 0;
                                $record->save();
                            }
                        }),
                    \Filament\Actions\RestoreAction::make()
                        ->hidden(fn () => filament()->getCurrentPanel()?->getId() === 'saha'),
                    \Filament\Actions\ForceDeleteAction::make()
                        ->hidden(fn () => filament()->getCurrentPanel()?->getId() === 'saha'),
                ])
                ->label('Eylemler')
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn () => filament()->getCurrentPanel()?->getId() === 'saha'),
                    \Filament\Actions\RestoreBulkAction::make()
                        ->hidden(fn () => filament()->getCurrentPanel()?->getId() === 'saha'),
                    \Filament\Actions\ForceDeleteBulkAction::make()
                        ->hidden(fn () => filament()->getCurrentPanel()?->getId() === 'saha'),
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
                    ->label(__('system.export'))
                    ->hidden(fn () => filament()->getCurrentPanel()?->getId() === 'saha'),
            ])
            ->defaultSort('created_at', 'desc')
            ->contentGrid(fn () => filament()->getCurrentPanel()?->getId() === 'saha' ? ['md' => 2, 'xl' => 3] : null);
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
            RelationManagers\TransactionsRelationManager::class,
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
            'index'  => ListPiggyBanks::route('/'),
            'create' => CreatePiggyBank::route('/create'),
            'view'   => ViewPiggyBank::route('/{record}'),
            'edit'   => EditPiggyBank::route('/{record}/edit'),
        ];
    }
}
