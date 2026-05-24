<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;

/**
 * MonthlyCollectionsChart Widget
 * Renders a smooth line chart of total donation collection amounts (TRY)
 * for the past six months on the admin dashboard. Only 'collection' type
 * transactions are aggregated — 'reset' transactions are excluded.
 *
 * MonthlyCollectionsChart Aracı
 * Yönetici panosunda son altı ayın toplam bağış tahsilat tutarlarını (TL)
 * akıcı bir çizgi grafiği olarak gösterir. Yalnızca 'collection' türündeki
 * işlemler toplanır — 'reset' işlemleri hariç tutulur.
 */
class MonthlyCollectionsChart extends ChartWidget
{
    /**
     * The heading displayed above the chart.
     * Grafiğin üzerinde gösterilen başlık.
     *
     * @var string|null
     */
    protected ?string $heading = 'Kategori Bazlı Tahsilat & Bağış Grafiği';

    /**
     * Restrict chart height.
     * Grafik yüksekliğini sınırlar.
     *
     * @var string|null
     */
    protected ?string $maxHeight = '250px';

    /**
     * Full-width column span across the dashboard grid.
     * Panel ızgarasında tam genişlik sütun kapsamı.
     *
     * @var int|string|array<string, int|string>
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * Define dynamic period filters.
     * Dinamik zaman aralığı filtrelerini tanımlar.
     *
     * @return array<string, string>|null
     */
    protected function getFilters(): ?array
    {
        return [
            '30' => 'Son 30 Gün',
            '90' => 'Son 90 Gün',
            '365' => 'Son 1 Yıl',
        ];
    }

    /**
     * Build and return the Chart.js dataset for the selected period.
     *
     * Seçilen dönem için Chart.js veri kümesini oluşturur ve döndürür.
     *
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $activeFilter = $this->filter ?? '30';
        $days = (int) $activeFilter;

        $pointsCount = 6;
        $intervalDays = (int) ceil($days / $pointsCount);

        $moneyData = [];
        $qurbaniData = [];
        $foodData = [];
        $labels = [];

        for ($i = $pointsCount - 1; $i >= 0; $i--) {
            $startDate = now()->subDays(($i + 1) * $intervalDays)->startOfDay();
            $endDate = now()->subDays($i * $intervalDays)->endOfDay();

            if ($i === 0) {
                $endDate = now();
            }

            $moneySum = Transaction::where('action_type', 'collection')
                ->where('donation_category', 'money')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');

            $qurbaniSum = Transaction::where('action_type', 'collection')
                ->where('donation_category', 'qurbani')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');

            $foodSum = Transaction::where('action_type', 'collection')
                ->where('donation_category', 'food')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');

            $moneyData[] = (float) $moneySum;
            $qurbaniData[] = (float) $qurbaniSum;
            $foodData[] = (float) $foodSum;
            
            if ($intervalDays === 1) {
                $labels[] = $startDate->translatedFormat('d M');
            } else {
                $labels[] = $startDate->translatedFormat('d M') . ' - ' . $endDate->translatedFormat('d M');
            }
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Para Tahsilatı (TL)',
                    'data'            => $moneyData,
                    'borderColor'     => '#10b981', // Green
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill'            => false,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Kurban Bağışı (Hisse/Adet)',
                    'data'            => $qurbaniData,
                    'borderColor'     => '#ef4444', // Red
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill'            => false,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Gıda Paketleri (Adet)',
                    'data'            => $foodData,
                    'borderColor'     => '#f59e0b', // Orange/Amber
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill'            => false,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Return the Chart.js chart type identifier.
     * Chart.js grafik türü tanımlayıcısını döndürür.
     *
     * @return string
     */
    protected function getType(): string
    {
        return 'line';
    }
}
