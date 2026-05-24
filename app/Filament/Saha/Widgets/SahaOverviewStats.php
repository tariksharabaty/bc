<?php

namespace App\Filament\Saha\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SahaOverviewStats extends BaseWidget
{
    protected $listeners = [
        'refreshStats' => '$refresh',
    ];

    protected function getStats(): array
    {
        $user = auth()->user();
        $money = $user->transactions()->where('action_type', 'collection')->where('donation_category', 'money')->sum('amount');
        $qurbani = $user->transactions()->where('action_type', 'collection')->where('donation_category', 'qurbani')->sum('amount');
        $food = $user->transactions()->where('action_type', 'collection')->where('donation_category', 'food')->sum('amount');

        return [
            Stat::make(__('system.bugun_toplanan_tutar') ?? 'Topladığım Para', number_format($money, 2, ',', '.') . ' ₺')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make(__('system.total_qurbani_collected') ?? 'Topladığım Kurban', number_format($qurbani, 0, ',', '.'))
                ->icon('heroicon-o-heart')
                ->color('danger'),

            Stat::make(__('system.total_food_collected') ?? 'Topladığım Gıda Paketleri', number_format($food, 0, ',', '.'))
                ->icon('heroicon-o-shopping-bag')
                ->color('warning'),
        ];
    }
}

