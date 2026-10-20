@php
    $sellerWa = $sellerWhatsApp ?? (isset($product) ? \App\Support\Phone::toWhatsApp($product->businessOwner?->hp_no) : null);
@endphp

<div x-show="isLoginOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <div x-show="isLoginOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="isLoginOpen = false"></div>

        <div x-show="isLoginOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-100">
            
            {{-- HEADER BANNER --}}
            <div class="bg-[#174A78] text-white p-6 text-center relative overflow-hidden">
                <button @click="isLoginOpen = false" class="absolute top-4 right-4 text-white/70 hover:text-white font-bold text-xl leading-none cursor-pointer" aria-label="Close">&times;</button>
                
                <div class="flex items-center justify-center gap-3 mb-3">
                    <div class="relative flex items-center justify-center h-12 w-12 rounded-full border-2 border-[#E7C46A] bg-[#16436E] p-1 shadow-md">
                        <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" class="h-10 w-10 rounded-full object-contain">
                    </div>
                    <div class="text-left">
                        <h2 class="font-display font-black text-2xl tracking-wide leading-none text-white">pios</h2>
                        <span class="text-[9px] font-bold uppercase tracking-wider text-[#E7C46A] block mt-0.5">BizGrowth Engine</span>
                    </div>
                </div>

                <div class="inline-block rounded-full border border-white/30 bg-white/10 px-4 py-1 text-[10px] font-extrabold uppercase tracking-wider text-white shadow-xs my-1">
                    CUSTOMER PORTAL
                </div>

                <h3 class="font-display font-black text-xl text-white tracking-tight mt-1">
                    CUSTOMER LOGIN
                </h3>
                <p class="text-xs text-sky-100/85 mt-1 leading-snug">
                    Enter your registered phone, IC number, or email to access your account.
                </p>
            </div>

            {{-- FORM BODY --}}
            <form action="{{ route('marketplace.login') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                
                @if(session('customer_login_error'))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 space-y-3">
                        <div class="flex items-start gap-2">
                            <span class="text-rose-600 font-extrabold text-sm leading-none">&excl;</span>
                            <p class="font-semibold leading-relaxed">{{ session('customer_login_error') }}</p>
                        </div>
                        @if(!empty($sellerWa))
                            <a href="https://wa.me/{{ $sellerWa }}?text={{ rawurlencode('Salam, saya ingin mendaftar sebagai pelanggan PIOS untuk membuat pesanan.') }}"
                               target="_blank"
                               class="inline-flex items-center justify-center gap-2 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition-all">
                                <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.099 4.018 4.142-1.087z"/></svg>
                                Hubungi Peniaga via WhatsApp &rarr;
                            </a>
                        @endif
                    </div>
                @endif

                <div class="text-center pb-1">
                    <span class="inline-block rounded-full border border-[#174A78]/20 bg-sky-50 px-3.5 py-1 text-[11px] font-bold text-[#174A78] uppercase tracking-wider">
                        CUSTOMER LOGIN
                    </span>
                    <h4 class="font-display text-lg font-bold text-[#0C2740] mt-1">Log in to your Account</h4>
                </div>

                <div>
                    <label for="modal_identifier" class="block text-xs font-bold text-slate-700 mb-1">
                        Phone Number, IC Number, or Email <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="identifier" id="modal_identifier" required value="{{ old('identifier') }}" placeholder="e.g. 0123456789 or customer@example.com"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                    <p class="mt-1 text-[11px] text-slate-500 leading-snug">
                        Use your registered phone, IC number, or email to auto-fill delivery details and unlock VIP discounts.
                    </p>
                </div>

                <div class="pt-2 space-y-2">
                    <button type="submit" class="w-full cursor-pointer rounded-xl bg-[#174A78] py-3.5 text-sm font-extrabold text-white hover:bg-[#16436E] shadow-md transition-all text-center">
                        Sign In &rarr;
                    </button>
                    <button type="button" @click="isLoginOpen = false" class="w-full cursor-pointer rounded-xl py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-100 transition-colors">
                        Continue as Guest
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
