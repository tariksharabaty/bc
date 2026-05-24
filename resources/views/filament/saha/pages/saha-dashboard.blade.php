<x-filament-panels::page>
    @once
    <style>
        @keyframes scanPulse {
            0%   { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
            70%  { box-shadow: 0 0 0 16px rgba(255, 255, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
        }
        .m3-pulse-btn {
            animation: scanPulse 1.8s infinite cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
    </style>
    @endonce

    <div class="space-y-6 max-w-lg mx-auto">
        {{-- ── 1. Massive Primary Action Card: Scan QR ────────────────── --}}
        <div class="relative overflow-hidden rounded-3xl shadow-xl p-6 text-white border border-amber-400/30"
             style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
            {{-- Glowing radial elements --}}
            <div class="absolute -top-12 -right-12 w-40 h-40 rounded-full blur-xl pointer-events-none" style="background-color: rgba(255, 255, 255, 0.15);"></div>
            <div class="absolute -bottom-8 -left-8 w-24 h-24 rounded-full blur-lg pointer-events-none" style="background-color: rgba(255, 255, 255, 0.08);"></div>

            <div class="relative z-10 flex flex-col items-center text-center space-y-4">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-inner animate-pulse" style="background-color: rgba(255, 255, 255, 0.2); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
                    <x-heroicon-o-qr-code class="w-10 h-10" style="color: #ffffff;" />
                </div>
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight" style="color: #ffffff; font-family: 'Montserrat', sans-serif; letter-spacing: -0.02em;">{{ __('system.kumbara_qr_kodu_tara') }}</h2>
                    <p class="text-xs mt-1 leading-relaxed" style="color: rgba(255, 255, 255, 0.9); font-weight: 500;">
                        {{ __('system.qr_description') }}
                    </p>
                </div>
                <button 
                    type="button" 
                    wire:click="mountAction('qr_okut')"
                    class="m3-pulse-btn w-full py-4 px-6 font-bold rounded-2xl shadow-lg transition-transform active:scale-[0.98] duration-150 flex items-center justify-center gap-2 text-base cursor-pointer hover:opacity-95"
                    style="background-color: #ffffff; color: #ea580c; border: none;"
                >
                    <x-heroicon-o-camera class="w-5 h-5" style="color: #ea580c;" />
                    <span style="color: #ea580c; font-family: 'Montserrat', sans-serif;">{{ __('system.kamera_ile_qr_okut') }}</span>
                </button>
            </div>
        </div>

        {{-- ── 2. Active Stats Section ────────────────────────────────── --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border border-gray-100 dark:border-gray-800 rounded-2xl p-4 shadow-sm flex flex-col justify-between space-y-2">
                <span class="text-[10px] font-bold tracking-wider text-gray-400 dark:text-gray-500 uppercase">{{ __('system.bugun_toplanan') }}</span>
                <span class="text-xl font-black text-emerald-500 dark:text-emerald-400 leading-tight">
                    {{ $this->getTodayTotal() }}
                </span>
            </div>
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border border-gray-100 dark:border-gray-800 rounded-2xl p-4 shadow-sm flex flex-col justify-between space-y-2">
                <span class="text-[10px] font-bold tracking-wider text-gray-400 dark:text-gray-500 uppercase">{{ __('system.kumbaralarim') }}</span>
                <span class="text-xl font-black text-amber-500 dark:text-amber-400 leading-tight">
                    {{ count($this->getAssignedPiggyBanks()) }} {{ __('system.adet') }}
                </span>
            </div>
        </div>

        {{-- ── 3. Bana Atanan Kumbaralar List ─────────────────────────── --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between px-1">
                <h3 class="text-base font-extrabold text-gray-800 dark:text-gray-200 tracking-tight font-poppins">
                    {{ __('system.bana_atanan_kumbaralar') }}
                </h3>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('system.aktif_liste') }}</span>
            </div>

            @php
                $assigned = $this->getAssignedPiggyBanks();
            @endphp

            @if($assigned->isEmpty())
                <div class="bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm border border-dashed border-gray-200 dark:border-gray-800 rounded-2xl p-8 text-center text-gray-400 dark:text-gray-500">
                    <x-heroicon-o-archive-box class="w-10 h-10 mx-auto opacity-30 mb-2" />
                    <p class="text-xs font-semibold">{{ __('system.no_active_piggy_banks') }}</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($assigned as $piggy)
                        <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800/80 rounded-2xl p-4 shadow-sm flex flex-col space-y-3 hover:border-amber-300 dark:hover:border-amber-700/60 transition-all duration-200">
                            
                            {{-- Top Row: Code & Cash Status --}}
                            <div class="flex justify-between items-start">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-sm font-bold text-gray-800 dark:text-gray-100">
                                            {{ $piggy->unique_box_id }}
                                        </span>
                                        @if($piggy->name)
                                            <span class="text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-2 py-0.5 rounded-full font-medium">
                                                {{ $piggy->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">
                                        {{ $piggy->shop->name ?? __('system.shop_not_assigned') }}
                                    </p>
                                </div>
                                <span class="font-mono text-sm font-black text-emerald-500 dark:text-emerald-400">
                                    @if($piggy->donation_category === 'money')
                                        {{ number_format($piggy->current_balance, 2, ',', '.') }} ₺
                                    @elseif($piggy->donation_category === 'qurbani')
                                        {{ (int)$piggy->current_balance }} Hisse/Adet
                                    @else
                                        {{ (int)$piggy->current_balance }} Birim
                                    @endif
                                </span>
                            </div>

                            {{-- Divider line --}}
                            <hr class="border-gray-100 dark:border-gray-800" />

                            {{-- Bottom Actions --}}
                            <div class="flex gap-2 w-full">
                                <button 
                                    type="button"
                                    wire:click="mountAction('tahsilat_ekle', { piggy_bank_id: {{ $piggy->id }} })"
                                    class="flex-1 py-2 px-4 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 text-xs font-bold rounded-xl transition-all duration-150 flex items-center justify-center gap-1 active:scale-[0.98]"
                                >
                                    <x-heroicon-o-banknotes class="w-4 h-4" />
                                    {{ __('system.hizli_tahsilat') }}
                                </button>
                                <a 
                                    href="/saha/piggy-banks/{{ $piggy->id }}"
                                    class="py-2 px-4 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800/40 dark:hover:bg-gray-800/80 text-gray-600 dark:text-gray-300 text-xs font-bold rounded-xl transition-all duration-150 flex items-center justify-center gap-1 active:scale-[0.98] border border-gray-100 dark:border-gray-800/60"
                                >
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                    {{ __('system.detaylar') }}
                                </a>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
