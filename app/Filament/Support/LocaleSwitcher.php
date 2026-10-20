<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class LocaleSwitcher
{
    /**
     * @return array<Action>
     */
    public static function userMenuItems(): array
    {
        return collect(config('app.supported_locales', []))
            ->map(fn (string $label, string $locale): Action => Action::make("locale_{$locale}")
                ->label($label)
                ->icon(Heroicon::Language)
                ->color(fn (): string => app()->getLocale() === $locale ? 'primary' : 'gray')
                ->url(fn (): string => route('locale.switch', ['locale' => $locale]))
                ->sort(20 + array_search($locale, array_keys(config('app.supported_locales', [])), true)))
            ->values()
            ->all();
    }
}
