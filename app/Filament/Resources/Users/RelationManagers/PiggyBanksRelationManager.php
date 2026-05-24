<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Model;

/**
 * PiggyBanksRelationManager Class
 * Displays the list of piggy banks (donation boxes) assigned (zimmetlenmiş) to a specific user
 * within the UserResource edit page. Read-oriented: the tab shows box ID, location, and balance.
 *
 * PiggyBanksRelationManager Sınıfı
 * UserResource düzenleme sayfasında belirli bir kullanıcıya zimmetlenmiş
 * kumbaraların (bağış kutularının) listesini görüntüler.
 * Okuma odaklıdır: sekme, kutu kimliğini, konumunu ve bakiyesini gösterir.
 */
class PiggyBanksRelationManager extends RelationManager
{
    /**
     * The Eloquent relationship method name on the parent model.
     * Üst modeldeki Eloquent ilişki yöntemi adı.
     *
     * @var string
     */
    protected static string $relationship = 'piggyBanks';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('system.assigned_piggy_banks');
    }

    /**
     * Form schema (write operations not needed for this relation manager).
     * Form şeması (bu ilişki yöneticisi için yazma işlemi gerekmez).
     *
     * @param Schema $schema
     * @return Schema
     */
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    /**
     * Configure the piggy bank list table shown within the user edit page.
     * Note: the `status` column does not exist in the `piggy_banks` schema.
     * `current_balance` and `shop.name` are used instead.
     *
     * Kullanıcı düzenleme sayfasında gösterilen kumbara listesi tablosunu yapılandırır.
     * Not: `status` sütunu `piggy_banks` şemasında mevcut değildir.
     * Bunun yerine `current_balance` ve `shop.name` kullanılmaktadır.
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->heading(__('system.assigned_piggy_banks'))
            ->recordTitleAttribute('unique_box_id')
            ->columns([
                TextColumn::make('unique_box_id')
                    ->label(__('system.unique_box_id'))
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('shop.name')
                    ->label(__('system.shop'))
                    ->sortable(),
                TextColumn::make('shop.district')
                    ->label(__('system.district'))
                    ->sortable(),
                // SCHEMA FIX: 'status' column does not exist — replaced with 'current_balance'.
                // ŞEMA DÜZELTME: 'status' sütunu mevcut değil — 'current_balance' ile değiştirildi.
                TextColumn::make('current_balance')
                    ->label(__('system.current_balance') ?? 'Bakiye / Miktar')
                    ->fontFamily('mono')
                    ->formatStateUsing(fn ($state, $record): string => match ($record->donation_category) {
                        'money' => number_format((float) $state, 2, ',', '.') . ' ₺',
                        'qurbani' => (int) $state . ' Hisse/Adet',
                        'food' => (int) $state . ' Birim',
                        default => $state,
                    })
                    ->sortable(),
            ])
            ->defaultSort('current_balance', 'desc');
    }
}
