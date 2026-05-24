<?php

namespace App\Filament\Resources\PiggyBanks\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Model;

/**
 * TransactionsRelationManager Class
 * Displays the full transaction history (collections and resets) for a specific piggy bank
 * within the PiggyBankResource edit page. Each row represents one financial event.
 *
 * TransactionsRelationManager Sınıfı
 * PiggyBankResource düzenleme sayfasında belirli bir kumbaranın tam işlem geçmişini
 * (tahsilatlar ve sıfırlamalar) görüntüler. Her satır bir finansal olayı temsil eder.
 */
class TransactionsRelationManager extends RelationManager
{
    /**
     * The Eloquent relationship method name on the parent model.
     * Üst modeldeki Eloquent ilişki yöntemi adı.
     *
     * @var string
     */
    protected static string $relationship = 'transactions';

    /**
     * Localised panel heading for this relation manager tab.
     * Bu ilişki yöneticisi sekmesi için yerelleştirilmiş panel başlığı.
     */
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('system.transaction_history');
    }

    /**
     * The attribute used as the record title in modals.
     * Modal pencerelerinde kayıt başlığı olarak kullanılan nitelik.
     *
     * @var string|null
     */
    protected static ?string $recordTitleAttribute = 'action_type';

    /**
     * Minimal form schema — direct creation of transactions via relation manager
     * is intentionally discouraged; they should be created via table actions instead.
     *
     * Minimal form şeması — ilişki yöneticisi aracılığıyla doğrudan işlem oluşturmak
     * kasıtlı olarak önerilmez; bunun yerine tablo eylemleri kullanılmalıdır.
     *
     * @param Schema $schema
     * @return Schema
     */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('action_type')
                ->label(__('system.action_type'))
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->label(__('system.amount_tl'))
                ->numeric(),
        ]);
    }

    /**
     * Configure the transaction history table shown within the piggy bank edit page.
     * Sorted newest-first by default for immediate auditability.
     *
     * Kumbara düzenleme sayfasında gösterilen işlem geçmişi tablosunu yapılandırır.
     * Anlık denetim kolaylığı için varsayılan olarak en yeniden eskiye doğru sıralanır.
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action_type')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('system.date_time'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('system.performed_by')),
                TextColumn::make('action_type')
                    ->label(__('system.action_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state, $record): string => match ($state) {
                        'reset' => 'Sıfırlandı',
                        'collection' => match ($record->donation_category) {
                            'money' => 'Para Alındı',
                            'qurbani' => 'Kurban Kaydedildi',
                            'food' => 'Erzak Eklendi',
                            default => $state,
                        },
                        default => $state,
                    })
                    ->color(fn (string $state, $record): string => match ($state) {
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
                    ->formatStateUsing(fn ($state, $record): string => match ($record->donation_category) {
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
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->contentGrid(fn () => filament()->getCurrentPanel()?->getId() === 'saha' ? ['md' => 2, 'xl' => 3] : null);
    }
}
