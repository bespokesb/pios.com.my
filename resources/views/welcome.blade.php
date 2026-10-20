<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PIOS - BizGrowth Engine | Automate Growth. Accelerate Success.</title>
    <meta name="description"
        content="PIOS connects Partners, Business Owners, and customers to sell premium, organic, and exclusive products — with ownership and eligibility built in.">

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#071716">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PIOS">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <!-- Google Fonts (Poppins & Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Landing Page Modern Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}?v={{ file_exists(public_path('assets/css/landing.css')) ? filemtime(public_path('assets/css/landing.css')) : time() }}">
</head>

<body>

    <!-- Top Navigation Header -->
    <header class="landing-header" id="landingHeader">
        <div class="container">
            <div class="nav-inner">
                <!-- Official PIOS Logo with Rounded Badge -->
                <a href="{{ url('/') }}" class="brand-logo-link" aria-label="PIOS Home" style="display: flex; align-items: center; text-decoration: none;">
                    <div class="brand-logo-badge" style="position: relative; display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 9999px; border: 2px solid #E7C46A; background: #16436E; padding: 2px; box-shadow: 0 2px 8px rgba(0,0,0,0.25); margin-right: 12px; flex-shrink: 0;">
                        <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" style="width: 100%; height: 100%; border-radius: 9999px; object-fit: contain;">
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.35rem; line-height: 1; color: #FFFFFF; letter-spacing: 0.5px;">pios</span>
                        <span style="font-family: 'Inter', sans-serif; font-size: 9px; font-weight: 700; letter-spacing: 1.5px; color: #E7C46A; text-transform: uppercase; margin-top: 3px; line-height: 1;">BizGrowth Engine</span>
                    </div>
                </a>

                <!-- Navigation Controls -->
                <div class="nav-actions">
                    @auth
                        <a href="{{ auth()->user()->getDashboardUrl() }}" class="btn-nav-login" id="navLoginBtn">
                            <span>Dashboard</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    @elseif (session()->has('market_customer_id'))
                        <a href="{{ route('marketplace.index') }}" class="btn-nav-login" id="navLoginBtn">
                            <span>Pasaran</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    @else
                        <!-- Primary Login CTA -->
                        <a href="{{ route('login') }}" class="btn-nav-login" id="navLoginBtn">
                            <span data-i18n="navLogin">Login</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section with "How PIOS Works" Workflow Card -->
    <section class="hero-section" id="hero">
        <div class="container">
            <div class="hero-grid">

                <!-- Hero Left: Value Proposition & Call to Action -->
                <div class="hero-content">
                    <div class="hero-badge-pill">
                        <span class="badge-pulse-dot" aria-hidden="true"></span>
                        <span data-i18n="badgeEngine">PIOS BizGrowth Engine</span>
                    </div>

                    <h1 class="hero-title">
                        <span data-i18n="heroTitle1">Automate Growth.</span>
                        <span class="highlight-text" data-i18n="heroTitle2">Accelerate Success.</span>
                    </h1>

                    <p class="hero-description" data-i18n="heroDesc">
                        PIOS connects Partners, Business Owners, and customers to sell premium, organic, and exclusive
                        products — with ownership and eligibility built in.
                    </p>

                    <!-- Feature Highlights / Checklist -->
                    <div class="hero-checklist">
                        <div class="check-item">
                            <span class="check-icon-circle">✓</span>
                            <span data-i18n="checkPremium">Premium</span>
                        </div>
                        <div class="check-item">
                            <span class="check-icon-circle">✓</span>
                            <span data-i18n="checkOrganic">Organic</span>
                        </div>
                        <div class="check-item">
                            <span class="check-icon-circle">✓</span>
                            <span data-i18n="checkExclusive">Exclusive</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="hero-cta-group">
                        @auth
                            <a href="{{ auth()->user()->getDashboardUrl() }}" class="btn-hero-login" id="heroLoginBtn">
                                <span>{{ auth()->user()->isAdmin() ? 'Go to Owner Dashboard' : (auth()->user()->isPartner() ? 'Go to Partner Dashboard' : (auth()->user()->isBusinessOwner() ? 'Go to Business Dashboard' : 'Go to Marketplace')) }}</span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        @elseif (session()->has('market_customer_id'))
                            <a href="{{ route('marketplace.index') }}" class="btn-hero-login" id="heroLoginBtn">
                                <span>Go to Marketplace</span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-hero-login" id="heroLoginBtn">
                                <span data-i18n="btnHeroLogin">Login to Portal</span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Hero Right: Interactive "HOW PIOS WORKS" Flow Card -->
                <div class="hero-card-wrapper">
                    <div class="how-it-works-card">
                        <div class="card-top-tag" data-i18n="howItWorksTag">
                            HOW PIOS WORKS
                        </div>

                        <div class="steps-list">
                            <!-- Step 1: PIOS Owner -->
                            <div class="step-card-item">
                                <div class="step-number-badge">1</div>
                                <div class="step-info">
                                    <h3 class="step-title" data-i18n="step1Title">PIOS Owner</h3>
                                    <p class="step-desc" data-i18n="step1Desc">Appoints Partners, sets fees, and
                                        oversees the platform.</p>
                                </div>
                            </div>

                            <!-- Down Connector Arrow -->
                            <div class="step-arrow-connector">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>

                            <!-- Step 2: Partner -->
                            <div class="step-card-item">
                                <div class="step-number-badge">2</div>
                                <div class="step-info">
                                    <h3 class="step-title" data-i18n="step2Title">Partner</h3>
                                    <p class="step-desc" data-i18n="step2Desc">Registers Business Owners and tracks
                                        their performance.</p>
                                </div>
                            </div>

                            <!-- Down Connector Arrow -->
                            <div class="step-arrow-connector">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>

                            <!-- Step 3: Business Owner -->
                            <div class="step-card-item">
                                <div class="step-number-badge">3</div>
                                <div class="step-info">
                                    <h3 class="step-title" data-i18n="step3Title">Business Owner</h3>
                                    <p class="step-desc" data-i18n="step3Desc">Registers customers, sells products, and
                                        runs promotions.</p>
                                </div>
                            </div>

                            <!-- Down Connector Arrow -->
                            <div class="step-arrow-connector">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>

                            <!-- Step 4: Customer / VIP -->
                            <div class="step-card-item">
                                <div class="step-number-badge">4</div>
                                <div class="step-info">
                                    <h3 class="step-title" data-i18n="step4Title">Customer / VIP</h3>
                                    <p class="step-desc" data-i18n="step4Desc">Buys the premium products they are
                                        eligible for.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Smooth Upward Curved Section Transition Divider -->
        <div class="hero-curve-divider" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 80" preserveAspectRatio="none">
                <path d="M0,70 Q720,10 1440,70 L1440,80 L0,80 Z" class="shape-fill"></path>
            </svg>
        </div>
    </section>

    <!-- Built for partner-led selling (3 Pillar Cards) -->
    <section class="features-section" id="features">
        <div class="container">

            <div class="section-header-center">
                <span class="section-pre-tag" data-i18n="panelPreTag">ONE OPERATING PANEL</span>
                <h2 class="section-main-title" data-i18n="panelMainTitle">Built for partner-led selling</h2>
            </div>

            <div class="features-grid">

                <!-- Pillar 1: Partner network -->
                <article class="feature-pillar-card">
                    <div class="feature-icon-wrapper">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="feature-card-title" data-i18n="feat1Title">Partner network</h3>
                    <p class="feature-card-desc" data-i18n="feat1Desc">
                        A clear hierarchy of Partners and Business Owners, with tiers, registration, and performance in
                        one place.
                    </p>
                </article>

                <!-- Pillar 2: Run your business -->
                <article class="feature-pillar-card">
                    <div class="feature-icon-wrapper">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    </div>
                    <h3 class="feature-card-title" data-i18n="feat2Title">Run your business</h3>
                    <p class="feature-card-desc" data-i18n="feat2Desc">
                        Business Owners register customers, select or propose products, run WhatsApp promotions, and
                        track orders.
                    </p>
                </article>

                <!-- Pillar 3: Ownership & fees -->
                <article class="feature-pillar-card">
                    <div class="feature-icon-wrapper">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <polyline points="9 12 11 14 15 10"></polyline>
                        </svg>
                    </div>
                    <h3 class="feature-card-title" data-i18n="feat3Title">Ownership & fees</h3>
                    <p class="feature-card-desc" data-i18n="feat3Desc">
                        Customer ownership, monthly fees, and reports enforced across every module.
                    </p>
                </article>

            </div>

        </div>
    </section>

    <!-- Sliding Product Marquee -->
    <section class="sliding-products-section" id="catalog">
        <div class="container">

            <div class="slider-header-row">
                <div class="slider-title-col">
                    <span class="slider-pre-tag" data-i18n="sliderPreTag">CATALOG SHOWCASE</span>
                    <h2 class="slider-main-title" data-i18n="sliderMainTitle">Exclusive Products Driving Real Growth</h2>
                    <p class="slider-desc" data-i18n="sliderDesc">High-demand, organic, and premium products verified and distributed across our partner network.</p>
                </div>

                <!-- Slider Controls -->
                <div class="slider-controls">
                    <button class="slider-btn" id="sliderPrevBtn" aria-label="Previous Slide">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <button class="slider-btn" id="sliderNextBtn" aria-label="Next Slide">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>

        </div>

        <!-- Continuous Sliding Marquee Container -->
        <div class="marquee-wrapper" id="marqueeWrapper">
            <div class="marquee-track" id="marqueeTrack">

                @php
                    $displayProducts = ($products && $products->isNotEmpty()) ? $products : collect([
                        (object)[
                            'name' => 'COSRX Advanced Snail 96 Mucin Power Essence',
                            'productType' => (object)['name' => 'Health Product'],
                            'selling_price' => 108.38,
                            'stock_quantity' => 14,
                            'product_no' => 'PRD000001',
                        ],
                        (object)[
                            'name' => 'Nature\'s Organic Glow Radiance Serum',
                            'productType' => (object)['name' => 'Skincare & Glow'],
                            'selling_price' => 129.00,
                            'stock_quantity' => 25,
                            'product_no' => 'PRD000002',
                        ],
                        (object)[
                            'name' => 'Herbal Renewal Ultra-Hydrating Cream',
                            'productType' => (object)['name' => 'Facial Care'],
                            'selling_price' => 89.50,
                            'stock_quantity' => 18,
                            'product_no' => 'PRD000003',
                        ],
                    ]);
                    // Duplicate collection to ensure smooth infinite loop in CSS marquee
                    $marqueeItems = $displayProducts->concat($displayProducts);
                @endphp

                @foreach ($marqueeItems as $index => $prod)
                    @php
                        $categoryName = is_object($prod->productType) ? $prod->productType->name : ($prod->productType['name'] ?? 'Premium Product');
                        $imgUrl = method_exists($prod, 'firstShareableImageUrl') && $prod->firstShareableImageUrl()
                            ? $prod->firstShareableImageUrl()
                            : asset('assets/images/products/prod_' . (($index % 6) + 1) . '.jpg');
                        $productUrl = (isset($prod->product_no) && filled($prod->product_no))
                            ? route('public.product.show', ['product' => $prod->product_no])
                            : route('login');
                    @endphp
                    <article class="product-slide-card">
                        <div class="prod-thumb-container">
                            <img src="{{ $imgUrl }}" alt="{{ $prod->name }}" class="prod-thumb-img" onerror="this.src='{{ asset('assets/images/header_logo.png') }}'">
                            <span class="prod-badge-floating active">
                                @if (isset($prod->stock_quantity) && $prod->stock_quantity > 0)
                                    In Stock ({{ $prod->stock_quantity }})
                                @else
                                    Verified • Active
                                @endif
                            </span>
                        </div>
                        <div class="prod-body-content">
                            <span class="prod-category">{{ $categoryName }}</span>
                            <h4 class="prod-title">{{ $prod->name }}</h4>
                            <div class="prod-meta-row">
                                <div class="prod-price">RM {{ number_format((float)$prod->selling_price, 2) }}</div>
                                <a href="{{ $productUrl }}" class="prod-action-link">
                                    <span data-i18n="orderBtn">View Product</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach

            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer class="landing-footer" style="padding: 40px 0; border-top: 1px solid #E2E8F0; text-align: center; color: #64748B; font-size: 0.9rem;">
        <div class="container">
            <p>&copy; {{ date('Y') }} PIOS BizGrowth Engine. All rights reserved.</p>
        </div>
    </footer>

    <!-- Landing Scripts -->
    <script src="{{ asset('assets/js/landing.js') }}?v={{ file_exists(public_path('assets/js/landing.js')) ? filemtime(public_path('assets/js/landing.js')) : time() }}"></script>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js')
                    .then(function (reg) { console.log('PIOS PWA registered:', reg.scope); })
                    .catch(function (err) { console.log('PIOS PWA registration failed:', err); });
            });
        }
    </script>
</body>

</html>
