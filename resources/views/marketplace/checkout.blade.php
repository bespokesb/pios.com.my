<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout — {{ config('app.name', 'PIOS') }} Marketplace</title>
    
    {{-- PWA META TAGS --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#174A78">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PIOS">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700;9..144,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --pios: #174A78;
            --pios-deep: #16436E;
            --pios-ink: #0C2740;
            --gold: #C8941F;
            --gold-soft: #E7C46A;
            --paper: #FBFAF7;
        }
        .font-display { font-family: 'Fraunces', Georgia, serif; }
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-(--paper) text-slate-800 antialiased selection:bg-(--pios)/15 pb-20 md:pb-10" x-data="{ isLoginOpen: false }">

    {{-- DRAFT DESIGN HEADER --}}
    {{-- SCREENSHOT DESIGN HEADER --}}
    <header class="bg-[#174A78] text-white shadow-md sticky top-0 z-40 border-b border-sky-900/40 w-full">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('marketplace.index') }}" class="flex items-center gap-3 group">
                <div class="relative flex items-center justify-center h-10 w-10 rounded-full border-2 border-[#E7C46A] bg-[#16436E] p-1 shadow-sm transition-transform group-hover:scale-105">
                    <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" class="h-8 w-8 rounded-full object-contain">
                </div>
                <div class="flex flex-col">
                    <span class="font-display font-black text-xl leading-none text-white tracking-wide">pios</span>
                    <span class="text-[9px] font-extrabold tracking-widest text-[#E7C46A] uppercase leading-none mt-0.5">BizGrowth Engine</span>
                </div>
            </a>
            
            <div class="flex items-center gap-4 text-xs font-semibold">
                @if(\App\Models\SystemSetting::isMarketplaceEnabled())
                    <a href="{{ route('marketplace.index') }}" class="hidden sm:inline-flex items-center gap-1 text-sky-100 hover:text-white transition-colors">
                        &larr; Return to Marketplace
                    </a>
                @endif
            </div>
        </div>
    </header>

    {{-- MAIN CHECKOUT CONTAINER --}}
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        
        {{-- CHECKOUT BANNER --}}
        <div class="rounded-3xl bg-gradient-to-r from-[#174A78] via-[#1B5285] to-[#16436E] text-white p-6 sm:p-8 shadow-xl mb-8 relative overflow-hidden">
            <div class="relative z-10 max-w-2xl">
                <div class="inline-block rounded-full border border-white/30 bg-white/10 px-3.5 py-1 text-[10px] font-extrabold uppercase tracking-widest text-[#E7C46A] shadow-xs mb-2">
                    CHECKOUT PORTAL
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-black tracking-tight text-white">ORDER CHECKOUT</h1>
                <p class="mt-1 text-xs sm:text-sm text-sky-100/90 leading-relaxed">
                    Review your order items, verify customer details, and confirm your order placement.
                </p>
            </div>
        </div>

        {{-- CUSTOMER LOGIN / IDENTIFY PROMPT BANNER --}}
        @if(empty($customer['name']))
            <div class="rounded-2xl border border-sky-200/80 bg-sky-50/90 p-5 mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs backdrop-blur-xs">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#174A78] text-white font-bold">
                        <svg class="h-5 w-5 text-[#E7C46A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-display font-bold text-base text-[#0C2740]">Already a Registered Customer?</h3>
                        <p class="text-xs text-slate-600 mt-0.5">
                            Identify using your Phone Number, IC, or Email to auto-fill delivery details.
                        </p>
                    </div>
                </div>
                <button type="button" @click="isLoginOpen = true" class="cursor-pointer shrink-0 rounded-full bg-[#174A78] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#16436E] shadow-sm transition-all">
                    Customer Login / Identify &rarr;
                </button>
            </div>
        @else
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 mb-8 flex items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-600 text-white font-bold">
                        &checkmark;
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 block">Identified Customer</span>
                        <h3 class="font-bold text-sm text-emerald-950">{{ $customer['name'] }} <span class="text-xs font-semibold text-emerald-700">({{ $customer['phone'] ?? '' }})</span></h3>
                    </div>
                </div>
                <form action="{{ route('marketplace.logout') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                    <button type="submit" class="text-xs font-bold text-rose-600 hover:underline cursor-pointer">Change / Sign Out</button>
                </form>
            </div>
        @endif

        {{-- FORM & ORDER SUMMARY GRID --}}
        <form action="{{ route('marketplace.checkout.process') }}" method="POST" class="grid gap-8 md:grid-cols-12">
            @csrf

            {{-- CUSTOMER DETAILS FORM CARD --}}
            <div class="md:col-span-7 space-y-6">
                <div class="rounded-3xl border border-slate-200/80 bg-white/90 backdrop-blur-md p-6 sm:p-7 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-sky-100 text-[#174A78] px-3 py-0.5 text-xs font-extrabold uppercase tracking-wider">Step 1</span>
                            <h3 class="font-display text-lg font-bold text-[#16436E]">Register Customer Details</h3>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Full Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" required value="{{ old('name', $customer['name'] ?? '') }}"
                                   placeholder="e.g. Ahmad Razak"
                                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                            @error('name') <span class="text-xs text-rose-600 font-semibold">{{ $message }}</span> @error
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Phone Number (WhatsApp) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="phone" id="phone" required value="{{ old('phone', $customer['phone'] ?? '') }}"
                                   placeholder="e.g. 0123456789"
                                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                            @error('phone') <span class="text-xs text-rose-600 font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="address" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Delivery Address <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="address" id="address" rows="3" required placeholder="Full street address, city, postcode, state"
                                      class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">{{ old('address', $customer['address'] ?? '') }}</textarea>
                            @error('address') <span class="text-xs text-rose-600 font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="note" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Order Notes (Optional)
                            </label>
                            <input type="text" name="note" id="note" value="{{ old('note') }}"
                                   placeholder="Specific instructions or delivery preferences"
                                   class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ORDER SUMMARY CARD --}}
            <div class="md:col-span-5">
                <div class="sticky top-20 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-amber-100 text-[#C8941F] px-3 py-0.5 text-xs font-extrabold uppercase tracking-wider">Step 2</span>
                            <h3 class="font-display text-lg font-bold text-[#16436E]">Order Summary</h3>
                        </div>
                    </div>

                    <ul class="divide-y divide-slate-100 mb-6 max-h-80 overflow-y-auto pr-1">
                        @foreach($items as $item)
                            <li class="py-3 flex items-center justify-between text-sm">
                                <div class="pr-2">
                                    <h4 class="font-bold text-slate-800 leading-tight">{{ $item['product']->name }}</h4>
                                    <span class="text-xs text-slate-400">Qty: {{ $item['quantity'] }} &times; RM {{ number_format($item['unit_price'], 2) }}</span>
                                </div>
                                <span class="font-bold text-slate-900 shrink-0">RM {{ number_format($item['subtotal'], 2) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-slate-200 pt-4 space-y-2">
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Payment Method</span>
                            <span class="font-semibold text-slate-700">Manual / Cash / Direct Transfer</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold text-slate-900 pt-2 border-t border-slate-100">
                            <span>Total Amount</span>
                            <span class="text-[#174A78]">RM {{ number_format($grandTotal, 2) }}</span>
                        </div>
                    </div>

                    <button type="submit" class="mt-6 w-full cursor-pointer rounded-full bg-[#C8941F] py-4 font-black text-[#0C2740] hover:bg-[#E7C46A] shadow-lg transition-all text-center text-base">
                        Sign in / Confirm Order &rarr;
                    </button>
                </div>
            </div>
        </form>
    </main>

    {{-- CUSTOMER LOGIN MODAL INCLUDE --}}
    @include('marketplace.partials.customer-login-modal')

    {{-- SERVICE WORKER REGISTER FOR PWA --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('PWA Service Worker registered:', reg.scope))
                    .catch(err => console.log('PWA Service Worker registration failed:', err));
            });
        }
    </script>
</body>
</html>
