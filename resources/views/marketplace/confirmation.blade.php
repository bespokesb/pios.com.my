<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmed — {{ config('app.name', 'PIOS') }} Marketplace</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --pios: #215F9A;
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
<body class="min-h-screen bg-(--paper) text-slate-800 antialiased selection:bg-(--pios)/15">

    <main class="mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100/80 text-emerald-600 border border-emerald-200">
            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
        </div>

        <h1 class="font-display mt-6 text-3xl font-bold text-[#0C2740]">Thank You! Your Order is Placed.</h1>
        <p class="mt-2 text-base text-slate-600 max-w-lg mx-auto">
            Your order details have been recorded and sent directly to the assigned Business Owner(s) for fulfilment.
        </p>

        <div class="mt-8 rounded-3xl border border-slate-200/80 bg-white/90 backdrop-blur-md p-6 sm:p-8 shadow-sm text-left">
            <h3 class="font-display text-base font-bold text-[#16436E] mb-4 border-b border-slate-100 pb-3">
                Placed Order References & Seller WhatsApp
            </h3>

            <div class="space-y-4">
                @foreach($placedOrders as $item)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl border border-slate-200/70 bg-slate-50/70 p-4">
                        <div>
                            <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-bold text-sky-800">
                                Order #{{ $item['order']->order_no }}
                            </span>
                            <h4 class="font-bold text-sm text-slate-800 mt-1">{{ $item['product']->name }}</h4>
                            <p class="text-xs text-slate-500">Seller: {{ $item['product']->businessOwner?->applicant_name ?? 'PIOS Seller' }}</p>
                        </div>

                        @if(!empty($item['whatsapp']))
                            <a href="https://wa.me/{{ $item['whatsapp'] }}?text={{ rawurlencode('Hello, I placed order #' . $item['order']->order_no . ' for ' . $item['product']->name) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 shadow-sm transition-all shrink-0">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                Contact Seller on WhatsApp
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-8 flex justify-center gap-4">
            @if(\App\Models\SystemSetting::isMarketplaceEnabled())
                <a href="{{ route('marketplace.index') }}" class="inline-flex items-center gap-2 rounded-full bg-[#174A78] px-6 py-3 text-sm font-bold text-white hover:bg-[#16436E] transition-all shadow-sm">
                    Back to Marketplace
                </a>
            @else
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-full bg-[#174A78] px-6 py-3 text-sm font-bold text-white hover:bg-[#16436E] transition-all shadow-sm">
                    Back to Home
                </a>
            @endif
        </div>
    </main>

</body>
</html>
