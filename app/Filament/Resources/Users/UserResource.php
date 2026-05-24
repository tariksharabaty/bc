<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\RelationManagers;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as FormSchema;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * UserResource Class
 * Filament admin resource for managing application users (personnel).
 * Supports creation of both super_admin and field_agent role accounts,
 * secure password hashing on save, and active/inactive status toggling.
 *
 * UserResource Sınıfı
 * Uygulama kullanıcılarının (personellerin) yönetimi için Filament yönetim kaynağı.
 * Hem super_admin hem de field_agent rol hesaplarının oluşturulmasını,
 * kaydetme sırasında güvenli şifre karma (hash) işlemini ve
 * aktif/pasif durum geçişini destekler.
 */
class UserResource extends Resource
{
    /** @var class-string<User> */
    protected static ?string $model = User::class;

    public static function getModelLabel(): string
    {
        return __('system.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('system.users');
    }

    public static function getNavigationLabel(): string
    {
        return __('system.users');
    }

    /** Navigation sidebar icon. — Kenar çubuğu simgesi. */
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

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
        return ['name', 'email'];
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
            __('system.email') => $record->email,
            __('system.role')  => $record->role === 'super_admin' ? __('system.super_admin') : __('system.field_agent'),
        ];
    }

    /**
     * Build the base Eloquent query for this resource.
     * The table only displays own-model columns so no eager loading is necessary here;
     * relation managers handle their own queries independently.
     *
     * Bu kaynak için temel Eloquent sorgusunu oluşturur.
     * Tablo yalnızca kendi model sütunlarını gösterdiğinden burada eager loading
     * gerekmez; ilişki yöneticileri kendi sorgularını bağımsız olarak yönetir.
     *
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
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
                Forms\Components\TextInput::make('name')
                    ->label(__('system.name'))
                    ->required()
                    ->helperText('Personelin adını ve soyadını giriniz (Örn: Ahmet Yılmaz)'),
                Forms\Components\TextInput::make('email')
                    ->label(__('system.email'))
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->helperText('Sisteme giriş için benzersiz e-posta adresini giriniz (Örn: ahmet@kumbara.org)'),
                Forms\Components\TextInput::make('password')
                    ->label(__('system.password'))
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->helperText('Kullanıcının sisteme giriş yaparken kullanacağı şifreyi giriniz. En az 8 karakter olmalıdır.'),
                Forms\Components\Select::make('role')
                    ->label(__('system.role'))
                    ->options([
                        'super_admin'  => __('system.super_admin'),
                        'field_agent'  => __('system.field_agent'),
                    ])
                    ->required()
                    ->helperText('Kullanıcının rolünü seçiniz. "Yönetici" tam yetkiye sahiptir, "Saha Elemanı" ise yalnızca bakiye toplama ekranlarını kullanabilir.'),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('system.is_active'))
                    ->default(true)
                    ->helperText('Personelin aktiflik durumu. Pasif personeller sisteme giriş yapamaz ve kendilerine yeni kumbara zimmetlenemez.'),
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
                Tables\Columns\TextColumn::make('name')
                    ->label(__('system.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('system.email'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label(__('system.role'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'field_agent' => 'info',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => __('system.super_admin'),
                        'field_agent' => __('system.field_agent'),
                        default       => $state,
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('system.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('system.created_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label(__('system.role'))
                    ->options([
                        'super_admin' => __('system.super_admin'),
                        'field_agent' => __('system.field_agent'),
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('system.active_status'))
                    ->trueLabel(__('system.only_active'))
                    ->falseLabel(__('system.only_passive')),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->label('Eylemler')
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            RelationManagers\PiggyBanksRelationManager::class,
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
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }
}
