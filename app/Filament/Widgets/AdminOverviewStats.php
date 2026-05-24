<?php

namespace App\Filament\Widgets;

use App\Models\PiggyBank;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * AdminOverviewStats Widget
 * Displays key system-wide KPI statistics on the admin dashboard overview.
 * Each stat card is loaded with a single aggregate query to maximise performance.
 *
 * AdminOverviewStats Aracı
 * Yönetici panosu genel görünümünde sistem genelindeki temel KPI istatistiklerini gösterir.
 * Performansı en üst düzeye çıkarmak için her istatistik kartı tek bir toplu sorguyla yüklenir.
 */
class AdminOverviewStats extends StatsOverviewWidget
{
    /**
     * Build and return the array of stat cards shown on the dashboard.
     * Panelde gösterilen istatistik kartları dizisini oluşturur ve döndürür.
     *
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $money = Transaction::where('action_type', 'collection')->where('donation_category', 'money')->sum('amount');
        $qurbani = Transaction::where('action_type', 'collection')->where('donation_category', 'qurbani')->sum('amount');
        $food = Transaction::where('action_type', 'collection')->where('donation_category', 'food')->sum('amount');

        return [
            Stat::make(__('system.total_money_collected') ?? 'Toplam Para', number_format($money, 2, ',', '.') . ' ₺')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make(__('system.total_qurbani_collected') ?? 'Toplam Kurban', number_format($qurbani, 0, ',', '.'))
                ->icon('heroicon-o-heart')
                ->color('danger'),

            Stat::make(__('system.total_food_collected') ?? 'Toplam Gıda Paketleri', number_format($food, 0, ',', '.'))
                ->icon('heroicon-o-shopping-bag')
                ->color('warning'),
        ];
    }
}
