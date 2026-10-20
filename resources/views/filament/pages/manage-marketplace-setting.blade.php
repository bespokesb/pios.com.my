<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5 space-y-6">
            
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-4 dark:border-white/10">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="h-5 w-5 text-[#174A78] dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36rem M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        Marketplace Status (Hide / Show)
                    </h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Controls whether the public customer marketplace (<code class="font-mono text-[#174A78] dark:text-sky-300">/market</code>) is visible to public visitors.
                    </p>
                </div>

                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $is_marketplace_enabled ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20' }}">
                    <span class="h-2 w-2 rounded-full {{ $is_marketplace_enabled ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    {{ $is_marketplace_enabled ? 'STATUS: VISIBLE & ACTIVE' : 'STATUS: HIDDEN & DISABLED' }}
                </span>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 items-center">
                <div>
                    <label class="relative inline-flex items-center cursor-pointer gap-3">
                        <input type="checkbox" wire:model.live="is_marketplace_enabled" class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:after:border-gray-600 peer-checked:bg-[#174A78]"></div>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $is_marketplace_enabled ? 'Show Marketplace' : 'Hide Marketplace' }}
                        </span>
                    </label>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                        @if($is_marketplace_enabled)
                            Marketplace is currently <strong>VISIBLE</strong>. Public customers can access <code class="font-mono text-[#174A78] dark:text-sky-300">/market</code>, browse products, add to cart, and checkout.
                        @else
                            Marketplace is currently <strong>HIDDEN</strong>. Any public user attempting to visit <code class="font-mono text-rose-600 dark:text-rose-400">/market</code> will automatically be redirected back to the main homepage (<code class="font-mono">/</code>).
                        @endif
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5 border border-gray-100 dark:border-white/5 space-y-2 text-xs text-gray-600 dark:text-gray-300">
                    <p class="font-bold text-gray-800 dark:text-gray-200">Behavior when Hidden:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Direct link to <code class="font-mono">/market</code> redirects immediately to <code class="font-mono">/</code>.</li>
                        <li>Marketplace buttons in navigation headers will be hidden.</li>
                        <li>Direct product share links via WhatsApp (<code class="font-mono">/p/PRD...</code>) remain accessible to customer links.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Public Product Page (/p/xxxx) Customer Login Visibility Card --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5 space-y-6">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-4 dark:border-white/10">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="h-5 w-5 text-[#174A78] dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        Public Product Page (<code class="font-mono text-sm">/p/xxxx</code>) Customer Login
                    </h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Controls whether the customer login button, prompt banner, and authentication modal are shown on individual public product landing pages (<code class="font-mono text-[#174A78] dark:text-sky-300">/p/PRD...</code>).
                    </p>
                </div>

                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $is_public_product_login_enabled ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20' }}">
                    <span class="h-2 w-2 rounded-full {{ $is_public_product_login_enabled ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    {{ $is_public_product_login_enabled ? 'STATUS: LOGIN VISIBLE' : 'STATUS: LOGIN HIDDEN (DEFAULT)' }}
                </span>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 items-center">
                <div>
                    <label class="relative inline-flex items-center cursor-pointer gap-3">
                        <input type="checkbox" wire:model.live="is_public_product_login_enabled" class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:after:border-gray-600 peer-checked:bg-[#174A78]"></div>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $is_public_product_login_enabled ? 'Unhide / Show Login' : 'Hide Login (Default)' }}
                        </span>
                    </label>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                        @if($is_public_product_login_enabled)
                            Customer login is currently <strong>VISIBLE</strong> on <code class="font-mono text-[#174A78] dark:text-sky-300">/p/xxxx</code>. Registered buyers can log in to auto-fill their contact and delivery details.
                        @else
                            Customer login is currently <strong>HIDDEN</strong> (Default). Buyers will not see any login buttons or prompts on <code class="font-mono">/p/xxxx</code>, allowing a fast, friction-free direct order submission.
                        @endif
                    </p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5 border border-gray-100 dark:border-white/5 space-y-2 text-xs text-gray-600 dark:text-gray-300">
                    <p class="font-bold text-gray-800 dark:text-gray-200">Behavior when Login is Hidden (Default):</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>The <code class="font-mono">Login</code> button in the sticky header on <code class="font-mono">/p/xxxx</code> is hidden.</li>
                        <li>The "Registered Customer? Login" prompt box above the direct order form is hidden.</li>
                        <li>Customer login modal is disabled on the page, keeping the checkout interface clean and straightforward.</li>
                    </ul>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 dark:border-white/10 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#174A78] px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#16436E] focus:outline-none focus:ring-2 focus:ring-[#174A78]/50 cursor-pointer transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    Save Settings
                </button>
            </div>
        </div>
    </form>
</x-filament-panels::page>
