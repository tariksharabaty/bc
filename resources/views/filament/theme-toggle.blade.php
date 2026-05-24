<div x-data="{
    theme: localStorage.getItem('theme') || 'system',
    init() {
        if (this.theme === 'system') {
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            this.applyTheme(systemTheme);
        } else {
            this.applyTheme(this.theme);
        }
    },
    toggle() {
        const next = this.theme === 'dark' ? 'light' : 'dark';
        this.theme = next;
        localStorage.setItem('theme', next);
        this.applyTheme(next);
        
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: next }));
    },
    applyTheme(mode) {
        if (mode === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}" class="flex items-center mr-3">
    <button @click="toggle()" type="button" 
            class="relative flex items-center justify-center rounded-lg text-gray-500 hover:text-amber-500 hover:bg-gray-500/10 focus:outline-none dark:text-gray-400 dark:hover:text-amber-400 dark:hover:bg-gray-500/10 w-9 h-9 transition-colors" 
            title="{{ __('system.change_theme') }}">
        <!-- Moon Icon (Visible in light mode) -->
        <x-heroicon-m-moon x-show="theme !== 'dark'" class="w-5 h-5 text-gray-500" />
        <!-- Sun Icon (Visible in dark mode) -->
        <x-heroicon-m-sun x-show="theme === 'dark'" class="w-5 h-5 text-gray-400" />
    </button>
</div>
