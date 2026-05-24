<?php

namespace App\Filament\Saha\Widgets;

use App\Models\PiggyBank;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class SahaDashboardActions extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.saha-dashboard-actions';

    protected int | string | array $columnSpan = 'full';

    protected $listeners = [
        'qr_code_scanned' => 'handleQrCodeScanned',
    ];

    #[On('qr_code_scanned')]
    public function handleQrCodeScanned(string $qrCode): void
    {
        $piggy = PiggyBank::where('unique_box_id', $qrCode)
            ->orWhere('id', is_numeric($qrCode) ? (int) $qrCode : null)
            ->first();

        if ($piggy) {
            // Close old modal and open the form modal for scan_qr action with parameters
            $this->mountAction('scan_qr', ['piggy_bank_id' => $piggy->id]);
        } else {
            Notification::make()
                ->title('Hata')
                ->body('Geçersiz kumbara kodu!')
                ->danger()
                ->send();
        }
    }

    public function scanQrAction(): Action
    {
        return Action::make('scan_qr')
            ->label('📷 QR Kodu Okut ve İşlem Yap')
            ->color('warning')
            ->size('lg')
            ->modalWidth('md')
            ->modalHeading(fn (array $arguments) => isset($arguments['piggy_bank_id']) ? 'Tahsilat İşlemi Yap' : __('system.kamera_qr_okut'))
            ->modalDescription(fn (array $arguments) => isset($arguments['piggy_bank_id']) ? 'Lütfen aşağıdaki işlem detaylarını doldurunuz.' : __('system.qr_description'))
            ->modalContent(function (array $arguments) {
                if (! isset($arguments['piggy_bank_id'])) {
                    return view('filament.qr-scanner');
                }
                return null;
            })
            ->form(function (array $arguments) {
                $piggyId = $arguments['piggy_bank_id'] ?? null;
                if (! $piggyId) {
                    return [];
                }

                $piggy = PiggyBank::with('shop')->find($piggyId);
                if (! $piggy) {
                    return [];
                }

                return [
                    \Filament\Forms\Components\Group::make([
                        \Filament\Forms\Components\Placeholder::make('info')
                            ->label('Kumbara Bilgisi')
                            ->content(new \Illuminate\Support\HtmlString("
                                <div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 text-sm space-y-1'>
                                    <div><strong>Dükkan:</strong> " . e($piggy->shop->name ?? 'Dükkan Atanmamış') . "</div>
                                    <div><strong>Kumbara Kodu:</strong> " . e($piggy->unique_box_id) . "</div>
                                    <div><strong>Kategori:</strong> " . e(match ($piggy->donation_category) {
                                        'money' => 'Para',
                                        'qurbani' => 'Kurban',
                                        'food' => 'Gıda Paketleri',
                                        default => $piggy->donation_category
                                    }) . "</div>
                                </div>
                            ")),

                        \Filament\Forms\Components\Hidden::make('piggy_bank_id')
                            ->default($piggy->id),

                        // Money fields
                        \Filament\Forms\Components\Select::make('action_type')
                            ->options([
                                'collection' => 'Tahsilat',
                                'reset'      => 'Sıfırlama',
                            ])
                            ->label('İşlem Türü')
                            ->default('collection')
                            ->required()
                            ->helperText("Kumbaradan nakit para çıkarttıysanız 'Tahsilat', içini tamamen boşaltıp sıfırladıysanız 'Sıfırlama' seçin.")
                            ->visible($piggy->donation_category === 'money'),

                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Tutar (TL)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->helperText('Kuruşları virgül veya nokta kullanarak giriniz. Örn: 750 veya 1250.50')
                            ->visible($piggy->donation_category === 'money'),

                        // Qurbani fields
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
                            ->visible($piggy->donation_category === 'qurbani'),

                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Adet / Hisse Sayısı')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->helperText('Girilen kurban türünden kaç adet veya kaç hisse bağışlandığını yazın. (Örn: 2 koyun için 2, 1 dana hissesi için 1 yazın)')
                            ->visible($piggy->donation_category === 'qurbani'),

                        // Food fields
                        \Filament\Forms\Components\TextInput::make('category_details.food_item_name')
                            ->label('Ürün Adı / Cinsi')
                            ->placeholder('Örn: Pirinç, Patates, Un, Yağ, Erzak Kolisi')
                            ->required()
                            ->helperText('Bağışlanan erzak ürününün adını yazın. Örn: Pirinç, Patates, Mercimek, Un, Sıvı Yağ veya Erzak Kolisi.')
                            ->visible($piggy->donation_category === 'food'),

                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Miktar / Değer')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->helperText('Bağışlanan erzak paketinin veya dökme gıdanın miktarını rakamla girin. (Örn: 5 koli için 5, 20 kg un için 20 yazın)')
                            ->visible($piggy->donation_category === 'food'),

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
                            ->visible($piggy->donation_category === 'food'),

                        \Filament\Forms\Components\Textarea::make('description')
                            ->label('Açıklama ve Bağışçı Notları')
                            ->helperText('Varsa bağışçının adını, soyadını, telefon numarasını veya makbuz/niyet taleplerini buraya serbest metin olarak yazabilirsiniz.'),
                    ])
                ];
            })
            ->modalSubmitAction(function (\Filament\Actions\Action $action, array $arguments) {
                if (! isset($arguments['piggy_bank_id'])) {
                    return false;
                }
                return $action;
            })
            ->action(function (array $data, array $arguments) {
                $piggyId = $data['piggy_bank_id'] ?? $arguments['piggy_bank_id'] ?? null;
                if (! $piggyId) {
                    return;
                }

                $piggy = PiggyBank::find($piggyId);
                if ($piggy) {
                    if ($piggy->donation_category === 'money') {
                        if (($data['action_type'] ?? 'collection') === 'reset') {
                            $piggy->current_balance = 0;
                        } else {
                            $piggy->current_balance += (float) $data['amount'];
                        }
                    } else {
                        $piggy->current_balance += (float) $data['amount'];
                    }
                    $piggy->save();

                    Transaction::create([
                        'piggy_bank_id' => $piggy->id,
                        'user_id' => auth()->id(),
                        'action_type' => $data['action_type'] ?? 'collection',
                        'amount' => $data['amount'],
                        'donation_category' => $piggy->donation_category,
                        'category_details' => $data['category_details'] ?? null,
                        'description' => $data['description'] ?? null,
                    ]);

                    $this->dispatch('refreshStats');

                    Notification::make()
                        ->title('İşlem Başarıyla Kaydedildi')
                        ->success()
                        ->send();
                }
            });
    }
}
