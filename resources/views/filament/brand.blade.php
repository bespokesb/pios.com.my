@php
    // Rendered inside the fi-logo wrapper (evaluated at request time, so the
    // current panel is known). Shows the PIOS wordmark plus a panel tag so each
    // panel is identifiable beside the logo.
    $tag = match (filament()->getCurrentPanel()?->getId()) {
        'partner' => __('pios.panel.tag.partner'),
        'business' => __('pios.panel.tag.business'),
        'admin' => __('pios.panel.tag.admin'),
        default => null,
    };
@endphp

<div class="flex h-full items-center gap-2">
    <div class="relative flex items-center justify-center h-7 w-7 rounded-full border border-[#E7C46A] bg-[#16436E] p-0.5 shrink-0 shadow-xs">
        <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" class="h-full w-full rounded-full object-contain" />
    </div>

    <img
        src="{{ asset('brand/pios-wordmark.png') }}"
        alt="{{ filament()->getBrandName() }}"
        style="height: 100%; width: auto;"
    />

    <span class="pios-brand-name">BizGrowth Engine</span>

    @if ($tag)
        <span class="pios-brand-tag">{{ $tag }}</span>
    @endif
</div>
