<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * LatestTransactions Widget
 * Displays the five most recent financial transactions on the admin dashboard.
 * Eager-loads the `user` and `piggyBank` relations to eliminate N+1 query issues.
 *
 * LatestTransactions Aracı
 * Yönetici panosunda en son beş finansal işlemi görüntüler.
 * N+1 sorgu sorunlarını önlemek için `user` ve `piggyBank` ilişkilerini
 * önceden yükler (eager load).
 */
class LatestTransactions extends TableWidget
{
    /**
     * Full-width column span across the dashboard grid.
     * Panel ızgarasında tam genişlik sütun kapsamı.
     *
     * @var int|string|array<string, int|string>
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * Build and return the table configuration for this widget.
     * Eager-loads user and piggyBank relations on the base query to prevent N+1 queries.
     *
     * Bu aracın tablo yapılandırmasını oluşturur ve döndürür.
     * N+1 sorgularını önlemek için temel sorguda user ve piggyBank ilişkilerini
     * önceden yükler.
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Eager-load relations: eliminates N+1 on user.name and piggyBank.unique_box_id columns.
                // İlişkileri önceden yükle: user.name ve piggyBank.unique_box_id sütunlarındaki N+1'i ortadan kaldırır.
                Transaction::query()
                    ->with(['user', 'piggyBank'])
                    ->latest()
                    ->limit(5)
            )
            ->heading('Son 5 Finansal Hareket')
            ->paginated(false)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d/m H:i')
                    ->badge(),
                TextColumn::make('user.name')
                    ->label('Personel'),
                TextColumn::make('piggyBank.unique_box_id')
                    ->label('Kumbara')
                    ->fontFamily('mono'),
                TextColumn::make('action_type')
                    ->label('İşlem')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'collection' => 'Tahsilat',
                        'reset'      => 'Sıfırlama',
                        default      => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'collection' => 'success',
                        'reset'      => 'danger',
                        default      => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label('Tutar')
                    ->money('TRY')
                    ->color('success'),
            ]);
    }
}
