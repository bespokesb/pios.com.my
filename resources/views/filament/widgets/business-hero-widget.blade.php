@php
    $metrics = $this->metrics;
@endphp

@if(!empty($metrics))
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#134C49] via-[#0F3D3B] to-[#092625] p-6 text-white shadow-xl border border-teal-500/20 mb-6">
    <!-- Decorative background glow and pattern -->
    <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-teal-400/10 blur-3xl pointer-events-none"></div>
    <div class="absolute right-1/3 -bottom-16 h-48 w-48 rounded-full bg-emerald-500/10 blur-2xl pointer-events-none"></div>

    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <!-- Left: Welcome & Profile Info -->
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-teal-200 border border-white/15 backdrop-blur-sm">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ $this->greeting }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-teal-400/20 text-teal-100 border border-teal-300/30">
                    {{ $metrics['bo_no'] }}
                </span>
                @if($metrics['is_amway'])
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-amber-400/20 text-amber-200 border border-amber-300/30">
                        Amway Partner
                    </span>
                @endif
            </div>

            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                    <span>{{ $metrics['name'] }}</span>
                </h1>
                <p class="text-sm text-teal-100/80 mt-1 flex items-center gap-2">
                    <svg class="h-4 w-4 text-teal-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>{{ $metrics['company'] }}</span>
                    <span class="text-teal-400/60">•</span>
                    <span>PIOS BizGrowth Engine</span>
                </p>
            </div>

            <!-- Quick Action Links -->
            <div class="pt-2 flex flex-wrap items-center gap-2.5">
                <a href="{{ route('filament.business.resources.customers.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-500 hover:bg-teal-400 text-white font-semibold text-xs transition shadow-lg shadow-teal-900/30 hover:scale-[1.02] active:scale-[0.98]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    + Daftar Pelanggan
                </a>

                <a href="{{ route('filament.business.resources.products.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium text-xs border border-white/20 transition backdrop-blur-sm">
                    <svg class="h-4 w-4 text-teal-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Katalog Produk
                </a>

                <a href="{{ route('filament.business.resources.incoming-orders.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium text-xs border border-white/20 transition backdrop-blur-sm">
                    <svg class="h-4 w-4 text-teal-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Pesanan Masuk
                </a>

                <!-- Store Link Copy Button -->
                <div x-data="{ copied: false, url: '{{ $metrics['store_url'] }}' }" class="relative inline-block">
                    <button 
                        @click="navigator.clipboard.writeText(url); copied = true; setTimeout(() => copied = false, 2500)" 
                        type="button" 
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-900/60 hover:bg-teal-900 text-teal-100 text-xs font-medium border border-teal-400/30 transition backdrop-blur-sm">
                        <svg class="h-4 w-4 text-teal-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span x-text="copied ? '✓ Link Kedai Disalin!' : 'Salin Link Kedai'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right: Mini Quick Stats Highlights -->
        <div class="grid grid-cols-3 gap-3 sm:gap-4 shrink-0 bg-black/20 p-4 rounded-xl border border-white/10 backdrop-blur-md">
            <div class="text-center px-2">
                <span class="block text-xs uppercase tracking-wider text-teal-200/80 font-semibold">Pelanggan</span>
                <span class="block text-2xl font-black text-white mt-0.5">{{ $metrics['total_customers'] }}</span>
                <span class="block text-[11px] text-teal-300/90 font-medium">{{ $metrics['vip_customers'] }} VIP</span>
            </div>
            <div class="text-center px-2 border-x border-white/10">
                <span class="block text-xs uppercase tracking-wider text-teal-200/80 font-semibold">Bulan Ini</span>
                <span class="block text-2xl font-black text-emerald-300 mt-0.5">+{{ $metrics['new_this_month'] }}</span>
                <span class="block text-[11px] text-teal-200/80">Baru</span>
            </div>
            <div class="text-center px-2">
                <span class="block text-xs uppercase tracking-wider text-teal-200/80 font-semibold">Produk</span>
                <span class="block text-2xl font-black text-white mt-0.5">{{ $metrics['active_products'] }}</span>
                <span class="block text-[11px] text-teal-300/90 font-medium">Aktif</span>
            </div>
        </div>
    </div>
</div>
@endif
