@php
    $supportedLocales = config('app.supported_locales', []);
    $currentLocale = app()->getLocale();
@endphp

<div class="pios-language-switcher" aria-label="{{ __('pios.language.switcher_label') }}">
    @foreach ($supportedLocales as $locale => $label)
        <x-filament::button
            tag="a"
            :href="route('locale.switch', ['locale' => $locale])"
            size="xs"
            :color="$currentLocale === $locale ? 'primary' : 'gray'"
            :outlined="$currentLocale !== $locale"
            icon="heroicon-m-language"
        >
            {{ $label }}
        </x-filament::button>
    @endforeach
</div>
