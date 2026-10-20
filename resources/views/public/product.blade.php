@extends('public.layout')

@php
    $shopName = $businessOwner?->company_name ?: $businessOwner?->applicant_name;
    $price = number_format((float) $product->selling_price, 2);
    $confirmation = session('order_confirmation');
    
    $isProductLoginEnabled = \App\Models\SystemSetting::isPublicProductLoginEnabled();

    $customerId = session('market_customer_id');
    $customerModel = $customerId ? \App\Models\Customer::find($customerId) : null;
    
    $sessionCustomer = session('market_customer', []);
    $customer = [
        'id' => $sessionCustomer['id'] ?? $customerModel?->id,
        'name' => $sessionCustomer['name'] ?? $customerModel?->name ?? session('market_customer_name'),
        'phone' => $sessionCustomer['phone'] ?? $customerModel?->hp_no ?? session('market_customer_phone'),
        'email' => $sessionCustomer['email'] ?? $customerModel?->email ?? session('market_customer_email'),
        'address' => $sessionCustomer['address'] ?? $customerModel?->address ?? session('market_customer_address'),
        'address_line_2' => $sessionCustomer['address_line_2'] ?? $customerModel?->address_line_2,
        'address_line_3' => $sessionCustomer['address_line_3'] ?? $customerModel?->address_line_3,
        'poskod' => $sessionCustomer['poskod'] ?? $customerModel?->poskod,
        'bandar' => $sessionCustomer['bandar'] ?? $customerModel?->bandar,
        'negeri' => $sessionCustomer['negeri'] ?? $customerModel?->negeri,
    ];
@endphp

@section('title', $product->name.' — '.($shopName ?: config('app.name')))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 150))
@section('og_title', $product->name)
@section('og_description', __('pios.public.og_price', ['price' => $price]))
@if($images)
    @section('og_image', $images[0])
@endif

@section('body')
<div x-data="{ isLoginOpen: {{ ($isProductLoginEnabled && (session('customer_login_error') || session('show_login_modal'))) ? 'true' : 'false' }} }">

    {{-- HEADER BANNER --}}
    <header class="bg-[#174A78] text-white shadow-md sticky top-0 z-40 border-b border-sky-900/40 w-full">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <a href="{{ \App\Models\SystemSetting::isMarketplaceEnabled() ? route('marketplace.index') : url('/') }}" class="flex items-center gap-3 group">
                    <div class="relative flex items-center justify-center h-10 w-10 rounded-full border-2 border-[#E7C46A] bg-[#16436E] p-1 shadow-sm shrink-0">
                        <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" class="h-8 w-8 rounded-full object-contain">
                    </div>
                    <div class="min-w-0 flex flex-col">
                        <span class="font-display font-black text-lg leading-none text-white tracking-wide truncate">{{ $shopName ?: 'PIOS Store' }}</span>
                        <span class="text-[9px] font-extrabold tracking-widest text-[#E7C46A] uppercase leading-none mt-0.5">{{ __('pios.public.verified_seller') }}</span>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-3">
                @if(\App\Models\SystemSetting::isMarketplaceEnabled())
                    <a href="{{ route('marketplace.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-100 hover:text-white transition-colors bg-white/10 px-3.5 py-1.5 rounded-full border border-white/20">
                        &larr; Marketplace
                    </a>
                @endif
                @if(!empty($customer['name']))
                    <div class="hidden sm:flex items-center gap-1.5 rounded-full border border-sky-200/40 bg-white/10 px-3 py-1 text-xs font-bold text-white">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        <span class="truncate max-w-[120px]">{{ $customer['name'] }}</span>
                        <form action="{{ route('marketplace.logout') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                            <button type="submit" class="text-rose-300 hover:text-white font-bold ml-1 text-xs cursor-pointer border-l border-sky-200/30 pl-1" title="Logout">Logout &rarr;</button>
                        </form>
                    </div>
                @elseif($isProductLoginEnabled)
                    <button @click="isLoginOpen = true" class="cursor-pointer inline-flex items-center gap-1.5 rounded-full bg-white px-3.5 py-1.5 text-xs font-bold text-[#174A78] hover:bg-sky-50 shadow-sm transition-all">
                        <svg class="h-3.5 w-3.5 text-[#174A78]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        Login
                    </button>
                @endif
            </div>
        </div>
    </header>

    {{-- MAIN RESPONSIVE CONTAINER --}}
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 pb-20">

        {{-- Order confirmation (post-submit, PRG) --}}
        @if($confirmation)
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-5 shadow-sm">
                <p class="font-display text-lg font-semibold text-emerald-900">{{ __('pios.public.confirm.title') }}</p>
                <p class="mt-1 text-sm text-emerald-800">
                    {{ __('pios.public.confirm.body', ['order_no' => $confirmation['order_no']]) }}
                </p>
                @if(!empty($confirmation['seller_whatsapp']))
                    <a href="https://wa.me/{{ $confirmation['seller_whatsapp'] }}?text={{ rawurlencode(__('pios.public.confirm.wa_text', ['order_no' => $confirmation['order_no'], 'product' => $product->name])) }}"
                       class="mt-3 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-emerald-700 transition-all">
                        {{ __('pios.public.confirm.message_seller') }}
                    </a>
                @endif
            </div>
        @endif

        {{-- RESPONSIVE DESKTOP SPLIT GRID --}}
        <div class="lg:grid lg:grid-cols-12 lg:gap-8 lg:items-start space-y-6 lg:space-y-0">
            
            @php
                $mediaItems = [];
                if (!empty($videoUrl)) {
                    $mediaItems[] = ['type' => 'video', 'url' => $videoUrl];
                }
                foreach ($images as $img) {
                    $mediaItems[] = ['type' => 'image', 'url' => $img];
                }
            @endphp
            {{-- LEFT COLUMN: STICKY GALLERY --}}
            <div class="lg:col-span-6 lg:sticky lg:top-20">
                <div x-data="{
                    active: 0,
                    items: {{ json_encode($mediaItems, JSON_UNESCAPED_SLASHES) }},
                    timer: null,
                    intervalMs: 5000,
                    isZoomed: false,
                    init() {
                        if (this.items.length > 1 && this.items[0]?.type !== 'video') {
                            this.startAutoplay();
                        }
                    },
                    startAutoplay() {
                        this.stopAutoplay();
                        if (this.items.length <= 1) return;
                        this.timer = setInterval(() => {
                            if (this.items[this.active]?.type === 'video') return;
                            this.next();
                        }, this.intervalMs);
                    },
                    stopAutoplay() {
                        if (this.timer) {
                            clearInterval(this.timer);
                            this.timer = null;
                        }
                    },
                    goTo(index) {
                        this.active = index;
                        if (this.items[this.active]?.type === 'video') {
                            this.stopAutoplay();
                        } else if (this.items.length > 1) {
                            this.startAutoplay();
                        }
                    },
                    next() {
                        if (this.items.length === 0) return;
                        this.active = (this.active + 1) % this.items.length;
                    },
                    prev() {
                        if (this.items.length === 0) return;
                        this.active = (this.active - 1 + this.items.length) % this.items.length;
                    }
                }"
                @mouseenter="stopAutoplay()"
                @mouseleave="if(items.length > 1 && items[active]?.type !== 'video') startAutoplay()"
                class="overflow-hidden rounded-3xl bg-white/90 backdrop-blur-md border border-slate-200/80 p-3 sm:p-4 shadow-sm">
                    @if(count($mediaItems) > 0)
                        {{-- Main Media Preview (Video or Image) --}}
                        <div class="relative h-72 sm:h-[380px] lg:h-[400px] w-full rounded-2xl overflow-hidden bg-slate-900/5 group select-none">
                            <template x-for="(item, idx) in items" :key="idx">
                                <div x-show="active === idx"
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute inset-0 h-full w-full">
                                    <template x-if="item.type === 'video'">
                                        <video :src="item.url"
                                               autoplay
                                               muted
                                               loop
                                               playsinline
                                               controls
                                               class="h-full w-full object-cover object-center bg-black"></video>
                                    </template>
                                    <template x-if="item.type === 'image'">
                                        <img :src="item.url"
                                             alt="{{ $product->name }}"
                                             @click="isZoomed = true"
                                             class="h-full w-full object-cover object-center cursor-zoom-in">
                                    </template>
                                </div>
                            </template>

                            <span class="absolute top-3 left-3 rounded-full bg-white/90 backdrop-blur-md px-3 py-1 text-[11px] font-extrabold text-[#174A78] shadow-xs border border-white/50 z-10">
                                {{ $product->productType?->name ?? 'General Product' }}
                            </span>

                            @if(count($mediaItems) > 1)
                                {{-- Counter Pill --}}
                                <div class="absolute top-3 right-3 rounded-full bg-slate-900/70 backdrop-blur-xs px-2.5 py-0.5 text-[11px] font-bold text-white shadow-xs z-10">
                                    <span x-text="(active + 1) + ' / ' + items.length"></span>
                                </div>

                                {{-- Navigation Buttons --}}
                                <button type="button" @click.stop="prev();"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-white/80 text-slate-800 shadow-md backdrop-blur-xs hover:bg-white hover:scale-105 transition-all opacity-80 group-hover:opacity-100 cursor-pointer z-10"
                                        title="Previous Media">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                                </button>
                                <button type="button" @click.stop="next();"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 flex h-9 w-9 items-center justify-center rounded-full bg-white/80 text-slate-800 shadow-md backdrop-blur-xs hover:bg-white hover:scale-105 transition-all opacity-80 group-hover:opacity-100 cursor-pointer z-10"
                                        title="Next Media">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                </button>
                            @endif
                        </div>

                        {{-- Horizontal Thumbnail Carousel below main picture --}}
                        @if(count($mediaItems) > 1)
                            <div id="thumbContainer" class="thumbContainer mt-3 flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin select-none">
                                <template x-for="(item, idx) in items" :key="idx">
                                    <button type="button"
                                            @click="goTo(idx)"
                                            class="relative h-16 w-16 shrink-0 rounded-xl overflow-hidden border-2 transition-all cursor-pointer focus:outline-none"
                                            :class="active === idx ? 'border-[#174A78] ring-2 ring-[#174A78]/30 scale-[0.97] opacity-100 shadow-xs' : 'border-transparent opacity-60 hover:opacity-100 hover:border-slate-300'">
                                        <template x-if="item.type === 'video'">
                                            <div class="h-full w-full bg-slate-900 flex flex-col items-center justify-center text-white select-none">
                                                <svg class="h-5 w-5 text-teal-400" viewBox="0 0 24 24" fill="currentColor">
                                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                                </svg>
                                                <span class="text-[8px] font-bold tracking-wider text-teal-300 mt-0.5">VIDEO</span>
                                            </div>
                                        </template>
                                        <template x-if="item.type === 'image'">
                                            <img :src="item.url" :alt="'{{ e($product->name) }} thumbnail ' + (idx + 1)" class="h-full w-full object-cover">
                                        </template>
                                    </button>
                                </template>
                            </div>
                        @endif

                        {{-- Fullscreen Lightbox Zoom Modal (Images only) --}}
                        <div x-show="isZoomed && items[active]?.type === 'image'"
                             x-cloak
                             @keydown.escape.window="isZoomed = false"
                             class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm">
                            <button type="button" @click="isZoomed = false" class="absolute top-4 right-4 text-white hover:text-slate-300 p-2 cursor-pointer">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <img :src="items[active]?.url" alt="{{ $product->name }}" class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain shadow-2xl">
                        </div>
                    @else
                        <div class="flex h-72 sm:h-[380px] lg:h-[400px] w-full items-center justify-center bg-slate-100 text-slate-300 rounded-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 8.25h.008v.008H18V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT COLUMN: DETAILS & ORDER FORM --}}
            <div class="lg:col-span-6 space-y-6">

                {{-- Product Information Header Card --}}
                <div class="rounded-3xl bg-white/90 backdrop-blur-md border border-slate-200/80 p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                        <span>Seller: {{ $shopName ?: 'PIOS Merchant' }}</span>
                        <span class="text-emerald-600 font-bold flex items-center gap-1">&checkmark; Verified</span>
                    </div>

                    <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">{{ $product->name }}</h1>

                    <div class="mt-4 flex items-baseline gap-3">
                        <span class="text-3xl font-black text-[#174A78]">RM {{ $price }}</span>
                        <span class="text-xs text-slate-500 font-medium">Direct Seller Price</span>
                    </div>

                    @if(filled($product->description))
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-1.5">Description</h4>
                            <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700 max-h-48 overflow-y-auto pr-1">{{ $product->description }}</p>
                        </div>
                    @endif
                </div>

                {{-- CUSTOMER IDENTIFICATION PROMPT --}}
                @if($isProductLoginEnabled && empty($customer['name']))
                    <div class="rounded-2xl border border-sky-200/80 bg-sky-50/90 p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#174A78] text-white font-bold">
                                <svg class="h-4 w-4 text-[#E7C46A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-sm text-[#0C2740]">Registered Customer?</h3>
                                <p class="text-xs text-slate-600">
                                    Login with Phone, IC, or Email to auto-fill delivery details.
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="isLoginOpen = true" class="cursor-pointer shrink-0 rounded-full bg-[#174A78] px-4 py-2 text-xs font-bold text-white hover:bg-[#16436E] shadow-xs transition-all">
                            Login &rarr;
                        </button>
                    </div>
                @elseif(!empty($customer['name']))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-3.5 flex items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-white font-bold text-xs">&checkmark;</span>
                            <div>
                                <span class="text-[9px] font-extrabold uppercase tracking-wider text-emerald-800 block leading-none">Identified Customer</span>
                                <span class="font-bold text-xs text-emerald-950">{{ $customer['name'] }} ({{ $customer['phone'] ?? '' }})</span>
                            </div>
                        </div>
                        <form action="{{ route('marketplace.logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-xs font-bold text-rose-600 hover:underline cursor-pointer">Logout &rarr;</button>
                        </form>
                    </div>
                @endif

                {{-- Direct Order Form Card --}}
                @if($orderable)
                    <div class="rounded-3xl bg-white/90 backdrop-blur-md border border-slate-200/80 p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-3 mb-4 flex items-center justify-between">
                            <h2 class="font-display text-lg font-bold text-slate-900">{{ __('pios.public.form.heading') }}</h2>
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-[#C8941F] bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200/50">Direct Delivery</span>
                        </div>

                        @if($errors->any())
                            <div class="mb-4 rounded-xl bg-rose-50 p-3.5 text-xs text-rose-700 border border-rose-200">
                                <ul class="list-inside list-disc space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('public.product.order', ['product' => $product->product_no]) }}" class="space-y-4">
                            @csrf

                            {{-- Honeypot --}}
                            <div class="hidden" aria-hidden="true">
                                <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                            </div>

                            <div>
                                <label for="name" class="mb-1 block text-xs font-bold text-slate-700 uppercase tracking-wider">{{ __('pios.public.form.name') }} <span class="text-rose-500">*</span></label>
                                <input id="name" name="name" type="text" required value="{{ old('name', $customer['name'] ?? '') }}"
                                       placeholder="Your full name"
                                       class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-sm font-semibold focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                            </div>

                            <div>
                                <div class="flex justify-between items-baseline mb-1">
                                    <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">{{ __('pios.public.form.phone') }} <span class="text-rose-500">*</span></label>
                                    <span class="text-[11px] font-semibold text-slate-500">Format WhatsApp (+60)</span>
                                </div>
                                <div class="relative flex items-stretch border border-slate-300 rounded-xl bg-slate-50/50 overflow-hidden focus-within:border-[#174A78] focus-within:bg-white focus-within:ring-2 focus-within:ring-[#174A78]/20 transition-all shadow-xs" id="publicPhoneContainer">
                                    <div class="flex items-center gap-1 px-3 bg-slate-100 border-r border-slate-200 text-xs font-bold text-slate-700 select-none">
                                        <span>🇲🇾</span>
                                        <span>+60</span>
                                    </div>
                                    <input id="phone" name="phone" type="tel" inputmode="tel" required
                                           value="{{ old('phone') ? \App\Support\Phone::national(old('phone')) : ($customer['phone'] ? \App\Support\Phone::national($customer['phone']) : '') }}"
                                           placeholder="12-345 6789"
                                           class="w-full bg-transparent px-3 py-2.5 text-sm font-semibold text-slate-900 border-none outline-none focus:outline-none focus:ring-0">
                                </div>
                                <p id="publicPhoneError" class="text-xs font-medium text-rose-500 mt-1 hidden"></p>
                            </div>

                            <div x-data="{
                                qty: {{ (int) old('quantity', 1) }},
                                unitPrice: {{ (float) $product->selling_price }},
                                inc() {
                                    let v = parseInt(this.qty) || 1;
                                    this.qty = Math.min(999, v + 1);
                                },
                                dec() {
                                    let v = parseInt(this.qty) || 1;
                                    this.qty = Math.max(1, v - 1);
                                }
                            }">
                                <div class="flex items-center justify-between">
                                    <label for="quantity" class="mb-1 block text-xs font-bold text-slate-700 uppercase tracking-wider">{{ __('pios.public.form.quantity') }}</label>
                                    <span class="text-xs font-extrabold text-[#174A78]">
                                        Total: RM <span x-text="(qty * unitPrice).toFixed(2)">{{ $price }}</span>
                                    </span>
                                </div>

                                <div class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50/80 p-1 shadow-xs select-none">
                                    <button type="button"
                                            @click.prevent="dec()"
                                            :disabled="qty <= 1"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-700 shadow-xs hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition font-bold text-base leading-none"
                                            aria-label="Decrease quantity">
                                        &minus;
                                    </button>
                                    <input id="quantity"
                                           name="quantity"
                                           type="number"
                                           min="1"
                                           max="999"
                                           required
                                           x-model.number="qty"
                                           @input="if(!qty || qty < 1) qty = 1"
                                           class="w-14 bg-transparent text-center text-sm font-extrabold text-slate-900 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    <button type="button"
                                            @click.prevent="inc()"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-700 shadow-xs hover:bg-slate-100 cursor-pointer transition font-bold text-base leading-none"
                                            aria-label="Increase quantity">
                                        &#43;
                                    </button>
                                </div>
                            </div>

                            {{-- ADDRESS (3 STACKED INPUTS) --}}
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Address</label>
                                <div class="space-y-2">
                                    <input id="address" name="address" type="text"
                                           value="{{ old('address', $customer['address'] ?? '') }}"
                                           placeholder="Address line 1"
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:border-[#174A78] focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                                    <input id="address_line_2" name="address_line_2" type="text"
                                           value="{{ old('address_line_2', $customer['address_line_2'] ?? '') }}"
                                           placeholder="Address line 2"
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:border-[#174A78] focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                                    <input id="address_line_3" name="address_line_3" type="text"
                                           value="{{ old('address_line_3', $customer['address_line_3'] ?? '') }}"
                                           placeholder="Address line 3"
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:border-[#174A78] focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">
                                </div>
                            </div>

                            {{-- POSTCODE DROPDOWN --}}
                            <div>
                                <label for="postcodeSelect" class="mb-1 block text-xs font-bold text-slate-700 uppercase tracking-wider">Postcode</label>
                                <div class="relative">
                                    <select name="poskod" id="postcodeSelect"
                                            class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-10 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs cursor-pointer">
                                        <option value="">Select postcode</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- CITY DROPDOWN --}}
                            <div>
                                <label for="citySelect" class="mb-1 block text-xs font-bold text-slate-700 uppercase tracking-wider">City</label>
                                <div class="relative">
                                    <select name="bandar" id="citySelect"
                                            class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-10 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs cursor-pointer">
                                        <option value="">Select city</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- STATE DROPDOWN --}}
                            <div>
                                <label for="stateSelect" class="mb-1 block text-xs font-bold text-slate-700 uppercase tracking-wider">State</label>
                                <div class="relative">
                                    <select name="negeri" id="stateSelect"
                                            class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-10 text-sm font-semibold text-slate-800 focus:border-[#174A78] focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs cursor-pointer">
                                        <option value="">Select state</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="note" class="mb-1 block text-xs font-bold text-slate-700 uppercase tracking-wider">{{ __('pios.public.form.note') }}</label>
                                <textarea id="note" name="note" rows="2" placeholder="Order instructions or delivery preferences"
                                          class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-sm font-semibold focus:border-[#174A78] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#174A78]/20 transition-all shadow-xs">{{ old('note') }}</textarea>
                            </div>

                            <button type="submit"
                                    class="w-full cursor-pointer rounded-xl bg-[#174A78] px-4 py-3.5 text-sm font-extrabold text-white shadow-md hover:bg-[#16436E] transition active:scale-[.99]">
                                {{ __('pios.public.form.submit') }} &rarr;
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- CUSTOMER LOGIN MODAL INCLUDE --}}
    @if($isProductLoginEnabled)
        @include('marketplace.partials.customer-login-modal')
    @endif
</div>

{{-- MALAYSIA ADDRESS CASCADING SCRIPT --}}
@include('partials.malaysia-locations-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.attachMalaysiaLocationSync({
        stateSelect: 'stateSelect',
        citySelect: 'citySelect',
        postcodeSelect: 'postcodeSelect',
        initialState: @json(old('negeri', $customer['negeri'] ?? '')),
        initialCity: @json(old('bandar', $customer['bandar'] ?? '')),
        initialPostcode: @json(old('poskod', $customer['poskod'] ?? ''))
    });

    // --- Phone Normalization & Sufficiency Validation (+60 WhatsApp) ---
    const publicPhoneInput = document.getElementById('phone');
    const publicPhoneContainer = document.getElementById('publicPhoneContainer');
    const publicPhoneError = document.getElementById('publicPhoneError');
    const publicOrderForm = publicPhoneInput ? publicPhoneInput.closest('form') : null;

    function inspectPublicPhone(raw) {
        let digits = (raw || '').replace(/\D/g, '');
        if (digits.startsWith('60')) digits = digits.substring(2);
        if (digits.startsWith('0')) digits = digits.substring(1);
        if (!digits) return { valid: false, reason: 'empty', digits: '' };

        if (!digits.startsWith('1')) {
            return { valid: false, reason: 'not_mobile', digits, message: 'Nombor mestilah telefon bimbit bermula dengan 1 (cth: 12, 13, 11, 19).' };
        }

        const minDigits = digits.startsWith('11') ? 10 : 9;
        if (digits.length < minDigits) {
            const rem = minDigits - digits.length;
            return { valid: false, reason: 'too_short', digits, message: `Nombor telefon tidak mencukupi (perlu sekurang-kurangnya ${rem} digit lagi, cth: 12-345 6789).` };
        }

        if (digits.length > 10) {
            return { valid: false, reason: 'too_long', digits, message: 'Nombor telefon melebihi had (maksimum 10 digit selepas +60).' };
        }

        return { valid: true, digits, fullE164: '+60' + digits };
    }

    if (publicPhoneInput) {
        publicPhoneInput.addEventListener('input', function() {
            const inspection = inspectPublicPhone(this.value);
            if (this.value && inspection.digits && this.value !== inspection.digits && (this.value.startsWith('0') || this.value.startsWith('+60') || this.value.startsWith('60'))) {
                this.value = inspection.digits;
            }

            if (publicPhoneError && publicPhoneContainer) {
                if (inspection.reason === 'empty') {
                    publicPhoneError.classList.add('hidden');
                    publicPhoneContainer.classList.remove('border-rose-400');
                } else if (!inspection.valid) {
                    publicPhoneError.textContent = '⚠️ ' + inspection.message;
                    publicPhoneError.classList.remove('hidden');
                    publicPhoneContainer.classList.add('border-rose-400');
                } else {
                    publicPhoneError.classList.add('hidden');
                    publicPhoneContainer.classList.remove('border-rose-400');
                }
            }
        });

        if (publicOrderForm) {
            publicOrderForm.addEventListener('submit', function(e) {
                const inspection = inspectPublicPhone(publicPhoneInput.value);
                if (!inspection.valid) {
                    e.preventDefault();
                    publicPhoneInput.focus();
                    if (publicPhoneError) {
                        publicPhoneError.textContent = '⚠️ ' + (inspection.message || 'Nombor telefon tidak mencukupi.');
                        publicPhoneError.classList.remove('hidden');
                    }
                    if (publicPhoneContainer) {
                        publicPhoneContainer.classList.add('border-rose-400');
                    }
                    return false;
                }
                publicPhoneInput.value = inspection.fullE164;
            });
        }
    }
});
</script>
@endsection
