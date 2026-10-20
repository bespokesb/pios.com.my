<x-filament-panels::page.simple heading="" subheading="">
    <div class="pios-login-shell">
        <aside class="pios-login-media" aria-label="PIOS brand">
            <div class="pios-login-side-top">
                <div class="pios-login-logo-tile">
                    <img src="{{ asset('brand/pak-itam-logo.png') }}" alt="Pak ITAM" />
                </div>

                <div>
                    <div class="pios-login-kicker">{{ __($this->authKey().'.kicker') }}</div>
                    <h1>{{ __($this->authKey().'.hero_title') }}</h1>
                    <p>
                        {{ __($this->authKey().'.hero_copy') }}
                    </p>
                </div>
            </div>

            <div class="pios-login-side-footer">
                @foreach (__($this->authKey().'.chips') as $chip)
                    <span>{{ $chip }}</span>
                @endforeach
            </div>
        </aside>

        <section class="pios-login-form-panel" aria-label="PIOS login form">
            <div class="pios-login-form-inner">
                @include('filament.partials.language-switcher')

                <img class="pios-login-wordmark" src="{{ asset('brand/pios-wordmark.png') }}" alt="PIOS" />

                <div class="pios-login-form-heading">
                    <span>{{ __($this->authKey().'.secure_access') }}</span>
                    <h2>{{ $this->getHeading() }}</h2>
                    <p>{{ $this->getSubheading() }}</p>
                </div>

                {{ $this->content }}
            </div>
        </section>
    </div>
</x-filament-panels::page.simple>
