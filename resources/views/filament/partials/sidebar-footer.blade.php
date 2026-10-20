@php
    $user = auth()->user();
    $bo = $user?->businessOwner;
    $name = $bo?->applicant_name ?? $user?->name ?? 'Business Owner';
    $boNo = $bo?->business_owner_no ?? 'BO-'.str_pad((string) ($user?->id ?? 1), 4, '0', STR_PAD_LEFT);
@endphp

<div class="px-4 py-3.5 mt-auto border-t border-white/10 dark:border-white/10 bg-[#0F3D3B]/40 dark:bg-[#092625]/60 flex items-center justify-between gap-2">
    <div class="flex items-center gap-3 overflow-hidden min-w-0">
        <div class="relative shrink-0">
            <img 
                src="{{ asset('assets/images/lenny_avatar.png') }}" 
                alt="{{ $name }}" 
                class="h-9 w-9 rounded-full object-cover ring-2 ring-teal-400/40"
                onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($name) }}&background=0D9488&color=fff';"
            />
            <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-[#134C49]"></span>
        </div>
        <div class="overflow-hidden min-w-0">
            <span class="block text-xs font-bold text-white truncate max-w-[120px]">{{ $name }}</span>
            <span class="block text-[11px] font-mono text-teal-200/80 truncate">{{ $boNo }}</span>
        </div>
    </div>
    
    <form action="{{ filament()->getLogoutUrl() }}" method="POST" class="shrink-0 inline-flex">
        @csrf
        <button 
            type="submit" 
            class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-white/10 hover:bg-rose-600/90 text-teal-100 hover:text-white transition duration-150 focus:outline-none focus:ring-2 focus:ring-rose-500/50" 
            title="Keluar / Sign Out" 
            aria-label="Sign Out">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </button>
    </form>
</div>
