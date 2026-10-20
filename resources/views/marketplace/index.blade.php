<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PIOS') }} Marketplace — Premium Products</title>
    
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
<body class="min-h-screen bg-(--paper) text-slate-800 antialiased selection:bg-(--pios)/15 pb-20 md:pb-10 overflow-x-hidden w-full max-w-full" x-data="{ isCartOpen: false, isLoginOpen: false }">

    {{-- SCREENSHOT DESIGN HEADER --}}
    <header class="bg-[#174A78] text-white sticky top-0 z-40 shadow-md w-full">
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-4">
                {{-- LEFT (MOBILE ONLY): HAMBURGER MENU BUTTON --}}
                <button type="button" @click="isLoginOpen = true" class="md:hidden text-white hover:text-[#E7C46A] p-1.5 focus:outline-none cursor-pointer" aria-label="Menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                {{-- LOGO BRAND --}}
                <a href="{{ route('marketplace.index') }}" class="flex items-center gap-2 group">
                    <div class="relative flex items-center justify-center h-10 w-10 sm:h-11 sm:w-11 rounded-full border-2 border-[#E7C46A] bg-[#16436E] p-1 shadow-sm transition-transform group-hover:scale-105">
                        <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" class="h-8 w-8 sm:h-9 sm:w-9 rounded-full object-contain">
                    </div>
                    <div class="flex flex-col">
                        <span class="font-display font-black text-xl sm:text-2xl leading-none text-white tracking-wide">pios</span>
                        <span class="text-[9px] font-extrabold tracking-widest text-[#E7C46A] uppercase leading-none mt-0.5">BizGrowth Engine</span>
                    </div>
                </a>

                {{-- DESKTOP NAVIGATION LINKS (NO BURGER MENU ON DESKTOP) --}}
                <nav class="hidden md:flex items-center gap-6">
                    <a href="{{ route('marketplace.index') }}" class="text-xs font-extrabold text-white hover:text-[#E7C46A] transition-colors uppercase tracking-wider">Home</a>
                    <a href="#products-grid" class="text-xs font-extrabold text-sky-100 hover:text-[#E7C46A] transition-colors uppercase tracking-wider">Categories</a>
                    <a href="#products-grid" class="text-xs font-extrabold text-sky-100 hover:text-[#E7C46A] transition-colors uppercase tracking-wider">Products</a>
                </nav>

                {{-- RIGHT: HELP, CART & USER LOGIN BUTTON --}}
                <div class="flex items-center gap-3">
                    <a href="#help" @click.prevent="isLoginOpen = true" class="hidden sm:inline-block text-xs font-bold text-sky-100 hover:text-white transition-colors">
                        Help
                    </a>

                    {{-- CART BUTTON --}}
                    <button @click="isCartOpen = true" class="relative text-white hover:text-[#E7C46A] p-1.5 cursor-pointer transition-colors" aria-label="Cart">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        @if($cartCount > 0)
                            <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#C8941F] text-[10px] font-extrabold text-[#0C2740] shadow-xs">{{ $cartCount }}</span>
                        @endif
                    </button>

                    {{-- USER / IDENTIFY BUTTON (DESKTOP ONLY - HIDDEN ON MOBILE AS MOBILE HAS BOTTOM NAV) --}}
                    @if(!empty($customer['name']))
                        <div class="max-md:hidden flex items-center gap-1.5 rounded-full border border-sky-200/40 bg-white/10 px-3 py-1 text-xs font-bold text-white">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            <span class="truncate max-w-[120px] text-xs">{{ $customer['name'] }}</span>
                            <form action="{{ route('marketplace.logout') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                <button type="submit" class="text-rose-300 hover:text-white font-bold ml-1 text-xs cursor-pointer border-l border-sky-200/30 pl-1" title="Logout">Logout &rarr;</button>
                            </form>
                        </div>
                    @else
                        <button @click="isLoginOpen = true" class="max-md:hidden inline-flex items-center gap-1.5 rounded-full bg-white px-3.5 py-1.5 text-xs font-bold text-[#174A78] hover:bg-sky-50 shadow-sm transition-all cursor-pointer">
                            <svg class="h-4 w-4 text-[#174A78]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <span>Customer Login</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- SEARCH BAR (PROMINENT FULL WIDTH BELOW HEADER) --}}
            <form action="{{ route('marketplace.index') }}" method="GET" class="mt-2.5">
                @if($selectedCategory)
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                @endif
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Search products..."
                           class="w-full rounded-xl border-0 bg-white/95 py-2 pl-9 pr-4 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#E7C46A] shadow-xs backdrop-blur-xs">
                </div>
            </form>
        </div>
    </header>

    {{-- MARQUEE TICKER BANNER (CENTERED TAGLINE WITH ROUNDED BULLETS BEFORE EVERY TEXT) --}}
    <div class="bg-white border-y border-slate-200 py-2.5 text-xs font-black tracking-wider uppercase text-slate-900 shadow-xs w-full max-w-full">
        <div class="mx-auto flex flex-wrap items-center justify-center gap-4 sm:gap-7 text-center w-full px-4">
            <span class="inline-flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-[#174A78] shrink-0"></span>
                <span>AUTOMATE GROWTH, ACCELERATE SUCCESS</span>
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-[#174A78] shrink-0"></span>
                <span>DIRECT LOCAL MERCHANT MARKETPLACE</span>
            </span>
            <span class="inline-flex items-center gap-2 hidden md:inline-flex">
                <span class="h-2 w-2 rounded-full bg-[#174A78] shrink-0"></span>
                <span>AUTOMATE GROWTH, ACCELERATE SUCCESS</span>
            </span>
        </div>
    </div>

    {{-- HERO CAROUSEL BANNER --}}
    <section class="mx-auto max-w-7xl px-4 pt-4 sm:px-6">
        <div class="relative overflow-hidden rounded-3xl bg-slate-900 text-white shadow-lg" x-data="{ slide: 1 }">
            <div class="relative aspect-16/9 sm:aspect-21/9 w-full overflow-hidden bg-slate-800">
                <img src="{{ asset('brand/pak-itam-logo.png') }}" alt="Supermarket Hero" class="h-full w-full object-cover object-center opacity-85">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0C2740] via-[#174A78]/60 to-transparent p-6 sm:p-10 flex flex-col justify-end">
                    <span class="inline-block rounded-full bg-[#E7C46A] px-3 py-1 text-[10px] font-black uppercase tracking-widest text-[#0C2740] w-max shadow-sm">
                        PREMIUM QUALITY &bull; DIRECT SELLER
                    </span>
                    <h1 class="font-display mt-2 text-2xl sm:text-4xl font-black tracking-tight text-white">
                        Online Supermarket & Direct Marketplace
                    </h1>
                    <p class="mt-1 text-xs sm:text-sm text-sky-100 max-w-lg">
                        Browse verified exclusive products, organic daily essentials, and unique items.
                    </p>
                </div>
            </div>

            {{-- CAROUSEL PAGINATION DOTS --}}
            <div class="absolute bottom-4 right-6 flex items-center gap-1.5 z-10">
                <span class="h-2 w-5 rounded-full bg-white transition-all"></span>
                <span class="h-2 w-2 rounded-full bg-white/50"></span>
                <span class="h-2 w-2 rounded-full bg-white/50"></span>
                <span class="h-2 w-2 rounded-full bg-white/50"></span>
                <span class="h-2 w-2 rounded-full bg-white/50"></span>
            </div>
        </div>
    </section>

    {{-- FLASH MESSAGES --}}
    @if(session('cart_success'))
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6">
            <div class="flex items-center justify-between rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-semibold text-emerald-800">
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    {{ session('cart_success') }}
                </span>
                <button @click="isCartOpen = true" class="text-xs font-bold underline text-emerald-900 hover:text-emerald-700">View Cart &rarr;</button>
            </div>
        </div>
    @endif

    @if(session('customer_status'))
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6">
            <div class="rounded-2xl bg-sky-50 border border-sky-200 p-4 text-sm font-semibold text-sky-900">
                {{ session('customer_status') }}
            </div>
        </div>
    @endif

    {{-- MAIN CONTENT AREA (GATED IF UNAUTHENTICATED) --}}
    <main id="products-grid" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 relative">

        {{-- LOCK OVERLAY IF UNAUTHENTICATED --}}
        @if(empty($customer['name']))
            <div class="absolute inset-0 z-30 flex flex-col items-center justify-start pt-24 bg-white/75 backdrop-blur-md text-center p-6 rounded-3xl">
                <div class="w-full max-w-md rounded-3xl border-2 border-[#174A78] bg-white p-7 shadow-2xl">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#174A78] text-white font-bold mx-auto mb-3 shadow-md">
                        <svg class="h-7 w-7 text-[#E7C46A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    </div>
                    <h3 class="font-display text-xl font-black text-[#0C2740]">Marketplace Locked</h3>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                        This marketplace is exclusive for registered & identified customers. Enter your details below to log in.
                    </p>

                    <form action="{{ route('marketplace.login') }}" method="POST" class="mt-5 space-y-3.5 text-left">
                        @csrf
                        @if(session('customer_login_error'))
                            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800 space-y-2">
                                <div class="flex items-start gap-2">
                                    <span class="text-rose-600 font-extrabold text-sm leading-none">&excl;</span>
                                    <p class="font-semibold leading-relaxed">{{ session('customer_login_error') }}</p>
                                </div>
                                @php
                                    $boWa = \App\Support\Phone::toWhatsApp(\App\Models\BusinessOwner::query()->first()?->hp_no);
                                @endphp
                                @if($boWa)
                                    <a href="https://wa.me/{{ $boWa }}?text={{ rawurlencode('Salam, saya ingin mendaftar sebagai pelanggan PIOS untuk membuat pesanan.') }}"
                                       target="_blank"
                                       class="inline-flex items-center justify-center gap-1.5 w-full rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 transition">
                                        <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.099 4.018 4.142-1.087z"/></svg>
                                        Hubungi Peniaga via WhatsApp &rarr;
                                    </a>
                                @endif
                            </div>
                        @endif
                        <div>
                            <label for="locked_identifier" class="block text-xs font-bold text-slate-700 mb-1">
                                Phone Number, IC Number, or Email <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="identifier" id="locked_identifier" required value="{{ old('identifier') }}" placeholder="e.g. 0123456789 or customer@example.com"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                        </div>
                        <button type="submit" class="w-full cursor-pointer rounded-xl bg-[#174A78] py-3.5 text-xs font-black text-white hover:bg-[#16436E] shadow-md transition-all text-center">
                            Sign In / Login &rarr;
                        </button>
                    </form>
                </div>
            </div>
        @endif
        {{-- BROWSE BY CATEGORIES (SCREENSHOT CIRCULAR LOOK) --}}
        <div class="pt-6 pb-6 border-b border-slate-200">
            <div class="text-center mb-6">
                <h2 class="font-display text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Browse by Categories</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Explore handpicked collections for every part of your life</p>
            </div>

            <div class="flex items-center gap-6 overflow-x-auto pb-4 justify-start sm:justify-center scrollbar-none px-2">
                {{-- ALL --}}
                <a href="{{ route('marketplace.index') }}" class="flex flex-col items-center gap-2 group shrink-0">
                    <div class="h-20 w-20 rounded-full bg-gradient-to-br from-[#174A78] to-[#16436E] p-1 flex items-center justify-center shadow-md transition-transform group-hover:scale-105 {{ empty($selectedCategory) ? 'ring-4 ring-[#E7C46A]' : '' }}">
                        <div class="h-full w-full rounded-full bg-white/10 flex items-center justify-center text-white font-black text-xs uppercase">
                            ALL
                        </div>
                    </div>
                    <span class="text-xs font-bold text-slate-700 group-hover:text-[#174A78]">All Categories</span>
                </a>

                @foreach($categories as $cat)
                    <a href="{{ route('marketplace.index', ['category' => $cat->code, 'search' => $searchQuery, 'sort' => $currentSort]) }}" class="flex flex-col items-center gap-2 group shrink-0">
                        <div class="h-20 w-20 rounded-full bg-slate-100 p-2 flex items-center justify-center border-2 border-slate-200 shadow-sm transition-transform group-hover:scale-105 {{ $selectedCategory === $cat->code ? 'border-[#174A78] ring-4 ring-[#174A78]/20 bg-sky-50' : '' }}">
                            <div class="h-full w-full rounded-full bg-white flex items-center justify-center p-2 text-[#174A78]">
                                <svg class="h-7 w-7 text-[#174A78]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.25 10.5a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm9 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                </svg>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-slate-700 group-hover:text-[#174A78] text-center max-w-[90px] truncate">{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>

            {{-- SORT DROPDOWN --}}
            <div class="mt-4 flex items-center justify-end">
                <form action="{{ route('marketplace.index') }}" method="GET" class="flex items-center gap-2 shrink-0">
                    @if($selectedCategory) <input type="hidden" name="category" value="{{ $selectedCategory }}"> @endif
                    @if($searchQuery) <input type="hidden" name="search" value="{{ $searchQuery }}"> @endif
                    <label for="sort" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Sort:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white py-1.5 pl-3 pr-8 text-xs font-semibold text-slate-700 focus:border-[#174A78] focus:outline-none">
                        <option value="newest" {{ $currentSort === 'newest' ? 'selected' : '' }}>Newest Arrival</option>
                        <option value="price_low" {{ $currentSort === 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_high" {{ $currentSort === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="name" {{ $currentSort === 'name' ? 'selected' : '' }}>Name A-Z</option>
                    </select>
                </form>
            </div>
        </div>

        {{-- PRODUCTS GRID --}}
        @if($products->count() > 0)
            <div class="mt-8 grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                @foreach($products as $product)
                    @php
                        $img = $product->firstShareableImageUrl() ?? asset('brand/pak-itam-logo.png');
                    @endphp
                    <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white/80 backdrop-blur-sm shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:border-[#174A78]/30">
                        {{-- IMAGE --}}
                        <div class="relative aspect-4/3 w-full overflow-hidden bg-slate-100">
                            <img src="{{ $img }}" alt="{{ $product->name }}" class="h-full w-full object-cover object-center transition-transform duration-500 group-hover:scale-105">
                            <span class="absolute top-3 left-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-[#16436E] backdrop-blur-md shadow-xs border border-white/40">
                                {{ $product->productType?->name ?? 'General' }}
                            </span>
                        </div>

                        {{-- CARD BODY --}}
                        <div class="flex flex-1 flex-col justify-between p-4">
                            <div>
                                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                    Seller: {{ $product->businessOwner?->applicant_name ?? 'PIOS Partner' }}
                                </div>
                                <h3 class="font-display mt-1 text-base font-bold text-[#0C2740] line-clamp-2">
                                    <a href="{{ route('public.product.show', ['product' => $product->product_no]) }}" class="hover:text-[#174A78]">
                                        {{ $product->name }}
                                    </a>
                                </h3>
                                <p class="mt-1.5 text-xs text-slate-500 line-clamp-2">
                                    {{ $product->description ?? 'Premium daily-use product available for direct order.' }}
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 block uppercase leading-none">Price</span>
                                    <span class="text-base font-extrabold text-[#16436E]">
                                        RM {{ number_format((float) $product->selling_price, 2) }}
                                    </span>
                                </div>

                                <form action="{{ route('marketplace.cart.add') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="cursor-pointer inline-flex items-center gap-1.5 rounded-xl bg-[#C8941F] px-3.5 py-2 text-xs font-bold text-[#0C2740] hover:bg-[#E7C46A] transition-all shadow-xs">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                        Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- PAGINATION --}}
            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @else
            <div class="mt-12 rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-50 text-(--pios)">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                </div>
                <h3 class="font-display mt-4 text-lg font-bold text-slate-800">No products found</h3>
                <p class="mt-1 text-sm text-slate-500">Try adjusting your search query or selecting another category.</p>
                <a href="{{ route('marketplace.index') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-(--pios) px-5 py-2.5 text-xs font-bold text-white hover:bg-(--pios-deep)">
                    Reset Filters
                </a>
            </div>
        @endif
    </main>

    {{-- CART SLIDE-OVER DRAWER --}}
    <div x-show="isCartOpen" class="relative z-50" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="isCartOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div x-show="isCartOpen" x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="pointer-events-auto w-screen max-w-md">
                        <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-2xl">
                            <div class="flex items-center justify-between bg-(--pios-deep) p-6 text-white">
                                <h2 class="font-display text-lg font-bold">Your Shopping Cart</h2>
                                <button @click="isCartOpen = false" class="rounded-full p-1 hover:bg-white/10 text-white">&times;</button>
                            </div>

                            <div class="flex-1 overflow-y-auto p-6">
                                @if(!empty($cart))
                                    @php $cartTotal = 0; @endphp
                                    <ul class="divide-y divide-slate-200">
                                        @foreach($cart as $id => $item)
                                            @php
                                                $lineTotal = $item['quantity'] * $item['selling_price'];
                                                $cartTotal += $lineTotal;
                                            @endphp
                                            <li class="py-4 flex gap-4">
                                                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                                    <img src="{{ $item['image_url'] ?? asset('brand/pak-itam-logo.png') }}" class="h-full w-full object-cover">
                                                </div>
                                                <div class="flex flex-1 flex-col justify-between">
                                                    <div>
                                                        <h4 class="font-bold text-sm text-slate-800 leading-tight">{{ $item['name'] }}</h4>
                                                        <span class="text-xs text-slate-400">Seller: {{ $item['seller_name'] }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-between mt-2">
                                                        <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden">
                                                            <form action="{{ route('marketplace.cart.update') }}" method="POST" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="product_id" value="{{ $id }}">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="quantity" value="{{ $item['quantity'] - 1 }}">
                                                                <button type="submit" class="px-2 py-0.5 text-slate-500 hover:bg-slate-100 font-bold text-xs">-</button>
                                                            </form>
                                                            <span class="px-2.5 text-xs font-bold text-slate-700">{{ $item['quantity'] }}</span>
                                                            <form action="{{ route('marketplace.cart.update') }}" method="POST" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="product_id" value="{{ $id }}">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="quantity" value="{{ $item['quantity'] + 1 }}">
                                                                <button type="submit" class="px-2 py-0.5 text-slate-500 hover:bg-slate-100 font-bold text-xs">+</button>
                                                            </form>
                                                        </div>
                                                        <span class="font-bold text-sm text-(--pios-deep)">RM {{ number_format($lineTotal, 2) }}</span>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="py-16 text-center text-slate-400">
                                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        <p class="mt-3 font-semibold text-sm">Your cart is empty</p>
                                    </div>
                                @endif
                            </div>

                            @if(!empty($cart))
                                <div class="border-t border-slate-200 p-6 bg-slate-50">
                                    <div class="flex items-center justify-between text-base font-bold text-slate-900 mb-4">
                                        <span>Subtotal</span>
                                        <span class="text-(--pios-deep)">RM {{ number_format($cartTotal ?? 0, 2) }}</span>
                                    </div>
                                    <a href="{{ route('marketplace.checkout') }}" class="block w-full text-center rounded-full bg-(--gold) py-3.5 font-extrabold text-(--pios-ink) hover:bg-(--gold-soft) shadow-lg transition-all">
                                        Proceed to Checkout &rarr;
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CUSTOMER LOGIN MODAL --}}
    @include('marketplace.partials.customer-login-modal')

    {{-- MOBILE BOTTOM NAVIGATION BAR (PWA APP-LIKE NAVIGATION) --}}
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-[#174A78] border-t border-sky-900/40 text-white flex justify-around items-center py-2.5 px-2 md:hidden shadow-2xl backdrop-blur-md bg-opacity-95">
        <a href="{{ route('marketplace.index') }}" class="flex flex-col items-center gap-1 text-white hover:text-[#E7C46A] transition-colors">
            <svg class="h-5 w-5 text-[#E7C46A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            <span class="text-[10px] font-bold">Home</span>
        </a>
        <a href="#categories" class="flex flex-col items-center gap-1 text-sky-100 hover:text-white transition-colors">
            <svg class="h-5 w-5 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6h16.5M3.75 12h16.5m-16.5 6h16.5"/></svg>
            <span class="text-[10px] font-bold">Categories</span>
        </a>
        <button @click="isCartOpen = true" class="flex flex-col items-center gap-1 text-sky-100 hover:text-white transition-colors relative cursor-pointer">
            <div class="relative">
                <svg class="h-5 w-5 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                @if($cartCount > 0)
                    <span class="absolute -top-1.5 -right-2 flex h-4 w-4 items-center justify-center rounded-full bg-[#C8941F] text-[9px] font-black text-[#0C2740]">{{ $cartCount }}</span>
                @endif
            </div>
            <span class="text-[10px] font-bold">Cart</span>
        </button>
        <button @click="isLoginOpen = true" class="flex flex-col items-center gap-1 text-sky-100 hover:text-white transition-colors cursor-pointer">
            <svg class="h-5 w-5 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
            <span class="text-[10px] font-bold">Identify</span>
        </button>
    </nav>

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
