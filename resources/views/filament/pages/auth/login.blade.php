<x-filament-panels::page.simple>

    @once
    <style>
        /* ============================================================
           LOGIN PAGE — Premium Glassmorphism Design
           Clean, neutral styling with modern backdrop blur
           Giriş Sayfası — Premium Camlı Cam Tasarımı
           Temiz, nötr stil ve modern arka plan bulanıklığı
           ============================================================ */

        /* 1. Full-screen clean background */
        .fi-simple-layout {
            min-height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1.5rem !important;
            position: relative !important;
            overflow: hidden !important;
            background: #f9fafb !important; /* bg-gray-50 */
        }
        .dark .fi-simple-layout {
            background: #030712 !important; /* bg-gray-950 */
        }

        /* 2. Decorative subtle floating orbs */
        .fi-simple-layout::before,
        .fi-simple-layout::after {
            content: '' !important;
            position: absolute !important;
            border-radius: 50% !important;
            pointer-events: none !important;
            animation: float 8s ease-in-out infinite !important;
        }
        .fi-simple-layout::before {
            width: 400px !important;
            height: 400px !important;
            top: -100px !important;
            right: -100px !important;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, transparent 70%) !important;
        }
        .fi-simple-layout::after {
            width: 300px !important;
            height: 300px !important;
            bottom: -80px !important;
            left: -80px !important;
            background: radial-gradient(circle, rgba(148, 163, 184, 0.08) 0%, transparent 70%) !important;
            animation-delay: -4s !important;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50%       { transform: translateY(-20px) scale(1.05); }
        }

        /* 3. Strip native Filament card chrome */
        .fi-simple-main {
            all: unset !important;
            display: block !important;
            width: 100% !important;
            max-width: 440px !important;
            position: relative !important;
            z-index: 10 !important;
        }
        .fi-simple-header,
        .fi-simple-logo {
            display: none !important;
        }

        /* 4. Glassmorphism card */
        .st-login-card {
            background: rgba(255, 255, 255, 0.80);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(229, 231, 235, 0.6);
            border-radius: 1.5rem;
            box-shadow:
                0 20px 40px -12px rgba(0, 0, 0, 0.08),
                0 4px 12px -2px rgba(0, 0, 0, 0.04),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            padding: 2.5rem;
            width: 100%;
            transition: box-shadow 0.3s ease;
        }
        .dark .st-login-card {
            background: rgba(17, 24, 39, 0.80);
            border-color: rgba(55, 65, 81, 0.6);
            box-shadow:
                0 25px 50px -15px rgba(0, 0, 0, 0.5),
                0 6px 16px -4px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        /* 5. Logo pulse animation */
        .st-logo-icon {
            animation: logoPulse 3s ease-in-out infinite;
        }
        @keyframes logoPulse {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 8px rgba(99, 102, 241, 0.3)); }
            50%       { transform: scale(1.08); filter: drop-shadow(0 0 16px rgba(99, 102, 241, 0.5)); }
        }

        /* 6. Input fields */
        .fi-input-wrp {
            border-radius: 0.875rem !important;
            border-color: rgba(209, 213, 219, 0.8) !important;
            background: rgba(255,255,255,0.7) !important;
            transition: all 0.25s ease !important;
        }
        .dark .fi-input-wrp {
            border-color: rgba(55, 65, 81, 0.8) !important;
            background: rgba(31, 41, 55, 0.7) !important;
        }
        .fi-input-wrp:focus-within {
            border-color: #6366f1 !important;
            background: rgba(255,255,255,0.95) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15), 0 1px 3px rgba(0,0,0,0.06) !important;
        }
        .dark .fi-input-wrp:focus-within {
            background: rgba(31, 41, 55, 0.95) !important;
        }

        /* 7. Submit button — sleek dark/indigo gradient with lift on hover */
        .fi-btn[type="submit"],
        .fi-simple-main .fi-btn {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%) !important;
            color: #ffffff !important;
            border-radius: 0.875rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.04em !important;
            padding: 0.75rem 1.5rem !important;
            border: none !important;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3), 0 1px 3px rgba(0,0,0,0.1) !important;
            transition: all 0.2s ease !important;
            width: 100% !important;
        }
        .fi-btn[type="submit"]:hover,
        .fi-simple-main .fi-btn:hover {
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%) !important;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4), 0 2px 6px rgba(0,0,0,0.12) !important;
            transform: translateY(-2px) !important;
        }
        .fi-btn[type="submit"]:active,
        .fi-simple-main .fi-btn:active {
            transform: translateY(0px) !important;
        }
    </style>
    @endonce

    {{-- ── Fixed theme toggle (top-right corner) ──────────────────── --}}
    <div
        x-data="{
            theme: localStorage.getItem('theme') ?? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
            init() { this.applyTheme(this.theme); },
            toggle() {
                this.theme = (this.theme === 'dark') ? 'light' : 'dark';
                localStorage.setItem('theme', this.theme);
                this.applyTheme(this.theme);
            },
            applyTheme(t) {
                document.documentElement.classList.toggle('dark', t === 'dark');
            }
        }"
        class="fixed top-4 right-4 z-[99]"
    >
        <button
            @click="toggle()"
            type="button"
            title="{{ __('system.change_theme') }}"
            class="flex items-center justify-center w-10 h-10 rounded-xl
                   bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm
                   border border-white/60 dark:border-gray-700/60
                   text-gray-500 dark:text-gray-400
                   hover:text-indigo-500 dark:hover:text-indigo-400
                   shadow-md hover:shadow-lg
                   transition-all duration-200 focus:outline-none
                   hover:-translate-y-0.5"
        >
            {{-- Moon icon — shown in light mode --}}
            <svg x-show="theme !== 'dark'" class="w-[18px] h-[18px]" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
            </svg>
            {{-- Sun icon — shown in dark mode --}}
            <svg x-show="theme === 'dark'" class="w-[18px] h-[18px] text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

    {{-- ── Login Card ───────────────────────────────────────────────── --}}
    <div class="st-login-card">

        {{-- Brand mark with animated logo icon --}}
        <div class="flex flex-col items-center text-center mb-8 space-y-3">
            {{-- Animated secure shield icon --}}
            <div class="st-logo-icon w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700
                        flex items-center justify-center shadow-lg shadow-indigo-200 dark:shadow-indigo-900/40">
                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                </svg>
            </div>

            {{-- Brand name --}}
            <div class="space-y-1">
                <h1 class="text-xl font-black tracking-wide text-indigo-600 dark:text-indigo-400 text-center"
                    style="font-family:'Poppins',sans-serif;">
                    {{ __('system.kumbara_takip_sistemi') }}
                </h1>
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 tracking-wider uppercase">
                    {{ __('system.management_systems') }}
                </p>
            </div>

            {{-- Section divider --}}
            <div class="w-full pt-2">
                <div class="h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-800 to-transparent"></div>
            </div>

            <div class="space-y-0.5">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white"
                    style="font-family:'Poppins',sans-serif;">
                    {{ __('system.welcome_back') }}
                </h2>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{ __('system.please_login') }}
                </p>
            </div>
        </div>

        {{-- Filament Livewire form (email + password + submit) --}}
        {{ $this->content }}

    </div>

</x-filament-panels::page.simple>
