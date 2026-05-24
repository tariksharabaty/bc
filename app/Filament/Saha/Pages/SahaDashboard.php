<?php

namespace App\Filament\Saha\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use App\Models\PiggyBank;
use App\Models\Transaction;

use BackedEnum;

class SahaDashboard extends Page
{
    protected string $view = 'filament.saha.pages.saha-dashboard';

    public function getTitle(): string
    {
        return __('system.saha_paneli');
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    /**
     * Get the list of piggy banks strictly assigned to the logged-in agent.
     */
    public function getAssignedPiggyBanks()
    {
        return PiggyBank::where('assigned_to_user_id', auth()->id())
            ->with('shop')
            ->get();
    }

    /**
     * Calculate and format today's collection total for the logged-in agent.
     */
    public function getTodayTotal(): string
    {
        $todayTotal = Transaction::where('user_id', auth()->id())
            ->where('action_type', 'collection')
            ->whereDate('created_at', today())
            ->sum('amount');

        return number_format($todayTotal, 2, ',', '.') . ' TL';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('qr_okut')
                ->label(__('system.kamera_qr_okut'))
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->modalContent(fn () => view('filament.qr-scanner'))
                ->modalWidth('md')
                ->modalHeading(__('system.kamera_qr_okut'))
                ->modalDescription(__('system.qr_description'))
                ->form([
                    TextInput::make('scanned_code')
                        ->label(__('system.kumbara_kodu'))
                        ->placeholder(__('system.scanned_code_placeholder'))
                        ->helperText(__('system.manual_code_helper'))
                        ->autofocus(),
                ])
                ->action(function (array $data) {
                    $code = trim($data['scanned_code'] ?? '');
                    if ($code === '') {
                        return;
                    }
                    redirect('/saha/transactions/create?piggy_bank_id=' . urlencode($code));
                }),

            Action::make('tahsilat_ekle')
                ->label(__('system.hizli_tahsilat_ekle'))
                ->form([
                    TextInput::make('amount')
                        ->label(__('system.tutar_tl'))
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data, array $arguments) {
                    $piggy = PiggyBank::find($arguments['piggy_bank_id']);
                    if ($piggy) {
                        $piggy->current_balance += (float) $data['amount'];
                        $piggy->save();

                        Transaction::create([
                            'piggy_bank_id' => $piggy->id,
                            'user_id'       => auth()->id(),
                            'action_type'   => 'collection',
                            'amount'        => $data['amount'],
                            'donation_category' => $piggy->donation_category,
                        ]);
                    }
                }),
        ];
    }
}
