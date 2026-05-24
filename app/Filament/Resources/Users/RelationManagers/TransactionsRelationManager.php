<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Model;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('system.staff_transactions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('system.staff_transactions'))
            ->recordTitleAttribute('created_at')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('system.date_time'))
                    ->dateTime('d/m/Y H:i')
                    ->fontFamily('Poppins'),
                TextColumn::make('action_type')
                    ->label(__('system.action_type'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'collection' => __('system.collection'),
                        'reset' => __('system.reset'),
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'collection' => 'success',
                        'reset' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label(__('system.amount') ?? 'Miktar / Tutar')
                    ->fontFamily('Poppins')
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
            ->defaultSort('created_at', 'desc');
    }
}
