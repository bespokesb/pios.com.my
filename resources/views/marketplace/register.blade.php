<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register Customer — {{ config('app.name', 'PIOS') }} Marketplace</title>
    
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

    {{-- DRAFT DESIGN HERO BANNER --}}
    <div class="bg-[#174A78] text-white pt-8 pb-14 px-4 sm:px-6 relative overflow-hidden shadow-md">
        <div class="mx-auto max-w-4xl flex items-center justify-between gap-4 mb-6">
            <a href="{{ route('marketplace.index') }}" class="flex items-center gap-3 group">
                <div class="relative flex items-center justify-center h-11 w-11 rounded-full border-2 border-[#E7C46A] bg-[#16436E] p-1 shadow-md transition-transform group-hover:scale-105">
                    <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" class="h-9 w-9 rounded-full object-contain">
                </div>
                <div class="flex flex-col">
                    <span class="font-display font-black text-2xl leading-none text-white tracking-wide">pios</span>
                    <span class="text-[10px] font-extrabold tracking-widest text-[#E7C46A] uppercase leading-none mt-0.5">BizGrowth Engine</span>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <a href="{{ \App\Models\SystemSetting::isMarketplaceEnabled() ? route('marketplace.index') : url('/') }}" class="flex items-center gap-1 text-white/90 bg-white/10 hover:bg-white/20 px-3.5 py-1.5 rounded-full border border-white/20 text-xs font-bold transition">
                    &larr; {{ \App\Models\SystemSetting::isMarketplaceEnabled() ? 'Marketplace' : 'Home' }}
                </a>
            </div>
        </div>

        <div class="mx-auto max-w-2xl text-center">
            <div class="inline-block rounded-full border border-white/30 bg-white/10 px-4 py-1 text-[11px] font-extrabold uppercase tracking-widest text-[#E7C46A] shadow-xs mb-2">
                CUSTOMER PORTAL
            </div>
            <h1 class="font-display text-3xl sm:text-4xl font-black text-white tracking-tight uppercase leading-tight">
                REGISTER CUSTOMER
            </h1>
            <p class="mt-2 text-xs sm:text-sm text-sky-100/90 leading-relaxed max-w-md mx-auto">
                Register your customer profile to unlock VIP benefits, order tracking, and express checkout.
            </p>
        </div>
    </div>

    {{-- MAIN FORM CARD --}}
    <main class="mx-auto max-w-xl px-4 -mt-8 relative z-10">
        <div class="rounded-3xl border border-slate-200/80 bg-white/90 backdrop-blur-md p-6 sm:p-8 shadow-xl">
            
            <div class="text-center pb-2">
                <span class="inline-block rounded-full border border-[#174A78]/30 bg-[#174A78]/10 px-4 py-1 text-[11px] font-extrabold text-[#174A78] uppercase tracking-wider">
                    REGISTER PROFILE
                </span>
                <h2 class="font-display font-bold text-2xl text-[#0C2740] mt-2">Customer Registration</h2>
            </div>

            @if(session('customer_status'))
                <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 p-3.5 text-center text-xs font-bold text-emerald-800">
                    {{ session('customer_status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mt-4 rounded-xl bg-rose-50 border border-rose-200 p-3.5 text-xs text-rose-700">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('marketplace.register.process') }}" method="POST" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" required value="{{ old('name', $customer['name'] ?? '') }}" placeholder="e.g. Ahmad Razak"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Phone Number (WhatsApp) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="phone" id="phone" required value="{{ old('phone', $customer['phone'] ?? '') }}"
                           placeholder="e.g. 0123456789"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Email Address <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}"
                           placeholder="customer@example.com"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                </div>

                <div>
                    <label for="kp_no" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        IC / KP Number (Optional)
                    </label>
                    <input type="text" name="kp_no" id="kp_no" value="{{ old('kp_no') }}"
                           placeholder="e.g. 900101015566"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                </div>

                <div>
                    <label for="address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Delivery Address (Optional)
                    </label>
                    <textarea name="address" id="address" rows="2" placeholder="Street address, city, postcode"
                              class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">{{ old('address', $customer['address'] ?? '') }}</textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full cursor-pointer rounded-xl bg-[#174A78] py-3.5 font-extrabold text-white text-sm hover:bg-[#16436E] shadow-md transition-all text-center">
                        Register Account &rarr;
                    </button>
                </div>
            </form>

            {{-- BOTTOM ACTION PILLS --}}
            <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-center gap-3">
                @if(\App\Models\SystemSetting::isMarketplaceEnabled())
                    <a href="{{ route('marketplace.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                        Marketplace
                    </a>
                @endif
                <a href="{{ route('marketplace.checkout') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                    Checkout
                </a>
                <button @click="isLoginOpen = true" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors cursor-pointer">
                    Identify
                </button>
            </div>
        </div>
    </main>

    {{-- CUSTOMER LOGIN MODAL --}}
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
