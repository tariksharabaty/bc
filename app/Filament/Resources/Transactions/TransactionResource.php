<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Models\PiggyBank;
use App\Models\Transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Filament\Exports\TransactionExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * TransactionResource Class
 * Read-only Filament admin resource representing the donation piggy bank transaction log.
 * All write operations (create, edit, delete) are intentionally disabled to preserve
 * the integrity of the financial audit trail.
 *
 * TransactionResource Sınıfı
 * Bağış kumbarası işlem kayıtlarını temsil eden salt okunur Filament yönetim kaynağı.
 * Finansal denetim geçmişinin bütünlüğünü korumak amacıyla tüm yazma işlemleri
 * (oluşturma, düzenleme, silme) kasıtlı olarak devre dışı bırakılmıştır.
 */
class TransactionResource extends Resource
{
    /** @var class-string<Transaction> */
    protected static ?string $model = Transaction::class;

    public static function getModelLabel(): string
    {
        return __('system.transaction');
    }

    public static function getPluralModelLabel(): string
    {
        return __('system.transactions');
    }

    public static function getNavigationLabel(): string
    {
        return __('system.transactions');
    }

    /** Navigation sidebar icon. — Kenar çubuğu simgesi. */
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    /**
     * Build the base Eloquent query.
     * Eager-loads `user` and `piggyBank` (with its `shop`) to eliminate N+1 queries
     * across all table columns, and sorts newest-first by default.
     *
     * Temel Eloquent sorgusunu oluşturur.
     * Tüm tablo sütunlarındaki N+1 sorgularını önlemek için `user` ve `piggyBank`
     * (kendi `shop`'uyla birlikte) ilişkilerini önceden yükler; varsayılan olarak
     * en yeniden eskiye doğru sıralar.
     *
     * @return Builder<Transaction>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ])
            ->with(['user', 'piggyBank.shop'])
            ->latest();
    }

    /**
     * Allow creation only in the Saha (field) panel.
     * Oluşturma yalnızca Saha panelinde mümkündür; admin paneli salt okunur kalır.
     *
     * @return bool
     */
    public static function canCreate(): bool
    {
        return filament()->getCurrentPanel()?->getId() === 'saha';
    }

    /**
     * Disable editing of existing transactions to maintain audit trail integrity.
     * Denetim geçmişi bütünlüğünü korumak için mevcut işlemlerin düzenlenmesini engeller.
     *
     * @param Model $record
     * @return bool
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Disable deletion of transaction records to guarantee security audit history.
     * Güvenlik denetim geçmişini güvenceye almak için işlem kayıtlarının silinmesini engeller.
     *
     * @param Model $record
     * @return bool
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Define the form schema.
     * On the Saha panel, piggy_bank_id is pre-filled from the URL query parameter
     * and locked so field workers cannot change the scanned box.
     * user_id is automatically set to the logged-in agent and hidden.
     *
     * Form şemasını tanımlar. Saha panelinde piggy_bank_id URL parametresinden
     * otomatik doldurulur ve kilitlenir; user_id oturum açmış personele atanır.
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function form(Schema $schema): Schema
    {
        $isSahaPanel = filament()->getCurrentPanel()?->getId() === 'saha';

        // Resolve the piggy bank ID from the URL query parameter.
        // The QR scanner passes `unique_box_id` as the value.
        $scannedCode = request()->query('piggy_bank_id');
        $prefilledPiggyBankId = null;
        if ($scannedCode) {
            // Try matching by unique_box_id first (QR code value), then by primary key.
            $piggyBank = PiggyBank::where('unique_box_id', $scannedCode)
                ->orWhere('id', is_numeric($scannedCode) ? (int) $scannedCode : null)
                ->first();
            $prefilledPiggyBankId = $piggyBank?->id;
        }

        return $schema
            ->components([
                \Filament\Forms\Components\Section::make('Kumbara ve İşlem Bilgisi')
                    ->schema([
                        \Filament\Forms\Components\Select::make('piggy_bank_id')
                            ->relationship('piggyBank', 'unique_box_id', function (Builder $query) {
                                $query->whereHas('shop', function (Builder $q) {
                                    $q->where('is_active', true);
                                });
                            })
                            ->label(__('system.piggy_bank'))
                            ->required()
                            ->default($prefilledPiggyBankId)
                            ->disabled($isSahaPanel && $prefilledPiggyBankId !== null)
                            ->dehydrated()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $piggyBank = PiggyBank::find($state);
                                    if ($piggyBank) {
                                        $set('donation_category', $piggyBank->donation_category);
                                        $set('donation_sub_category', $piggyBank->donation_sub_category);
                                        $set('category_details_view', $piggyBank->category_details);
                                    }
                                }
                            })
                            ->helperText('Tarattığınız fiziksel kumbaranın üzerindeki kod ile buradaki kodun eşleştiğinden emin olun.'),
                        
                        \Filament\Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label(__('system.user'))
                            ->required()
                            ->default(auth()->id())
                            ->disabled($isSahaPanel)
                            ->dehydrated()
                            ->hidden($isSahaPanel),
                    ])
                    ->columns(2),

                \Filament\Forms\Components\Section::make('Bağış ve Tahsilat Detayları')
                    ->schema([
                        // Money Category
                        \Filament\Forms\Components\Select::make('action_type')
                            ->options([
                                'collection' => 'Tahsilat',
                                'reset'      => 'Sıfırlama',
                            ])
                            ->label(__('system.action_type'))
                            ->default('collection')
                            ->required()
                            ->helperText("Kumbaradan nakit para çıkarttıysanız 'Tahsilat', içini tamamen boşaltıp sıfırladıysanız 'Sıfırlama' seçin.")
                            ->visible(fn ($get) => $get('donation_category') === 'money' || (request()->query('piggy_bank_id') && \App\Models\PiggyBank::where('unique_box_id', request()->query('piggy_bank_id'))->orWhere('id', request()->query('piggy_bank_id'))->first()?->donation_category === 'money')),

                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Tutar (₺)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->helperText('Kuruşları virgül veya nokta kullanarak giriniz. Örn: 750₺ veya 1250,50₺')
                            ->visible(fn ($get) => $get('donation_category') === 'money' || (request()->query('piggy_bank_id') && \App\Models\PiggyBank::where('unique_box_id', request()->query('piggy_bank_id'))->orWhere('id', request()->query('piggy_bank_id'))->first()?->donation_category === 'money')),

                        // Qurbani Category
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
                            ->required()
                            ->helperText('Bağışlanan kurban cinsini (koyun, koç, keçi, dana, deve) veya büyükbaş hissesini seçiniz.')
                            ->visible(fn ($get) => $get('donation_category') === 'qurbani' || (request()->query('piggy_bank_id') && \App\Models\PiggyBank::where('unique_box_id', request()->query('piggy_bank_id'))->orWhere('id', request()->query('piggy_bank_id'))->first()?->donation_category === 'qurbani')),

                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Adet / Hisse Sayısı')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->helperText('Girilen kurban türünden kaç adet veya kaç hisse bağışlandığını yazın. (Örn: 2 koyun için 2, 1 dana hissesi için 1 yazın)')
                            ->visible(fn ($get) => $get('donation_category') === 'qurbani' || (request()->query('piggy_bank_id') && \App\Models\PiggyBank::where('unique_box_id', request()->query('piggy_bank_id'))->orWhere('id', request()->query('piggy_bank_id'))->first()?->donation_category === 'qurbani')),

                        // Food Category
                        \Filament\Forms\Components\TextInput::make('category_details.food_item_name')
                            ->label('Ürün Adı / Cinsi')
                            ->placeholder('Örn: Pirinç, Patates, Un, Yağ, Erzak Kolisi')
                            ->required()
                            ->helperText('Bağışlanan erzak ürününün adını yazın. Örn: Pirinç, Patates, Mercimek, Un, Sıvı Yağ veya Erzak Kolisi.')
                            ->visible(fn ($get) => $get('donation_category') === 'food' || (request()->query('piggy_bank_id') && \App\Models\PiggyBank::where('unique_box_id', request()->query('piggy_bank_id'))->orWhere('id', request()->query('piggy_bank_id'))->first()?->donation_category === 'food')),

                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Miktar / Değer')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->helperText('Bağışlanan erzak paketinin veya dökme gıdanın miktarını rakamla girin. (Örn: 5 koli için 5, 20 kg un için 20 yazın)')
                            ->visible(fn ($get) => $get('donation_category') === 'food' || (request()->query('piggy_bank_id') && \App\Models\PiggyBank::where('unique_box_id', request()->query('piggy_bank_id'))->orWhere('id', request()->query('piggy_bank_id'))->first()?->donation_category === 'food')),

                        \Filament\Forms\Components\Select::make('category_details.food_unit')
                            ->label('Ölçü Birimi')
                            ->options([
                                'koli' => 'Koli',
                                'kg' => 'Kg',
                                'cuval' => 'Çuval',
                                'adet' => 'Adet',
                                'litre' => 'Litre',
                            ])
                            ->required()
                            ->helperText('Miktar kısmına girdiğiniz sayının birimini buradan seçin.')
                            ->visible(fn ($get) => $get('donation_category') === 'food' || (request()->query('piggy_bank_id') && \App\Models\PiggyBank::where('unique_box_id', request()->query('piggy_bank_id'))->orWhere('id', request()->query('piggy_bank_id'))->first()?->donation_category === 'food')),
                    ])
                    ->columns(2),

                \Filament\Forms\Components\Section::make('Saha Notları ve Açıklama')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('description')
                            ->label('Açıklama ve Bağışçı Notları')
                            ->helperText('Varsa bağışçının adını, soyadını, telefon numarasını veya makbuz/niyet taleplerini buraya serbest metin olarak yazabilirsiniz.'),
                    ]),

                \Filament\Forms\Components\Hidden::make('donation_category')
                    ->default(function () use ($prefilledPiggyBankId) {
                        if ($prefilledPiggyBankId) {
                            $piggyBank = PiggyBank::find($prefilledPiggyBankId);
                            return $piggyBank?->donation_category ?? 'money';
                        }
                        return 'money';
                    })
                    ->dehydrated(),
            ]);
    }

    /**
     * Configure the audit log table: columns, filters, and default sort.
     * Denetim günlüğü tablosunu yapılandırır: sütunlar, filtreler ve varsayılan sıralama.
     *
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('system.date_time'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('system.user'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('piggyBank.unique_box_id')
                    ->label(__('system.unique_box_id'))
                    ->fontFamily('mono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('piggyBank.shop.name')
                    ->label(__('system.shop'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('action_type')
                    ->label(__('system.action_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state, Transaction $record): string => match ($state) {
                        'reset' => 'Sıfırlandı',
                        'collection' => match ($record->donation_category) {
                            'money' => 'Para Alındı',
                            'qurbani' => 'Kurban Kaydedildi',
                            'food' => 'Erzak Eklendi',
                            default => $state,
                        },
                        default => $state,
                    })
                    ->color(fn (string $state, Transaction $record): string => match ($state) {
                        'reset' => 'danger',
                        'collection' => match ($record->donation_category) {
                            'money' => 'success',
                            'qurbani' => 'warning',
                            'food' => 'info',
                            default => 'gray',
                        },
                        default => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label(__('system.amount') ?? 'Miktar / Tutar')
                    ->fontFamily('mono')
                    ->formatStateUsing(fn ($state, Transaction $record): string => match ($record->donation_category) {
                        'money' => number_format((float) $state, 2, ',', '.') . ' ₺',
                        'qurbani' => (int) $state . ' Adet/Hisse (' . match ($record->category_details['qurbani_type'] ?? '') {
                            'kucukbas' => 'Küçükbaş',
                            'koyun' => 'Koyun',
                            'koc' => 'Koç',
                            'keci' => 'Keçi',
                            'dana' => 'Dana',
                            'deve' => 'Deve',
                            'buyukbas_hisse' => 'Büyükbaş Hisse',
                            'buyukbas_tam' => 'Büyükbaş Tam',
                            default => 'Kurban',
                        } . ')',
                        'food' => (int) $state . ' ' . match ($record->category_details['food_unit'] ?? '') {
                            'koli' => 'Koli',
                            'kg' => 'Kg',
                            'cuval' => 'Çuval',
                            'adet' => 'Adet',
                            'litre' => 'Litre',
                            default => 'Birim',
                        } . ' (' . ($record->category_details['food_item_name'] ?? 'Erzak') . ')',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('donation_category')
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
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label(__('system.user')),
                SelectFilter::make('action_type')
                    ->options([
                        'collection' => __('system.collection'),
                        'reset'      => __('system.reset'),
                    ])
                    ->label(__('system.action_type')),
                SelectFilter::make('donation_category')
                    ->options([
                        'money' => 'Para',
                        'qurbani' => 'Kurban',
                        'food' => 'Gıda Paketleri',
                    ])
                    ->label(__('system.donation_category') ?? 'Kategori'),
                Tables\Filters\TrashedFilter::make()
                    ->label(__('system.deleted')),
            ])
            ->bulkActions([
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
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Get the relation managers registered for this resource.
     * Bu kaynak için kayıtlı ilişki yöneticilerini getirir.
     *
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [];
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
            'index'  => ListTransactions::route('/'),
            'create' => CreateTransaction::route('/create'),
        ];
    }
}
