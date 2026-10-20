<x-business-layout title="PIOS - Products" headerTitle="Products" bodyClass="products-body" mainClass="prod-main" mobileHeaderClass="mobile-prod-header">

    <style>
        /* Unconditionally force New Product button to match Customer "+ Add Customer" theme and size */
        #openNewProductBtnDesktop,
        .btn-new-product-desktop {
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            background: #0D9488 !important;
            background-color: #0D9488 !important;
            background-image: none !important;
            color: #FFFFFF !important;
            font-family: var(--font-sans, 'Inter', sans-serif) !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            padding: 12px 22px !important;
            border-radius: 12px !important;
            border: none !important;
            cursor: pointer !important;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.3) !important;
            white-space: nowrap !important;
            transition: all 0.2s ease !important;
            height: auto !important;
            min-height: unset !important;
            box-sizing: border-box !important;
        }
        #openNewProductBtnDesktop:hover,
        .btn-new-product-desktop:hover {
            background: #0F766E !important;
            background-color: #0F766E !important;
            background-image: none !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.4) !important;
        }
        #openNewProductBtnDesktop:active,
        .btn-new-product-desktop:active {
            transform: scale(0.98) !important;
            box-shadow: 0 2px 6px rgba(13, 148, 136, 0.25) !important;
        }
        @media screen and (max-width: 991px) {
            #openNewProductBtnDesktop,
            .btn-new-product-desktop {
                display: none !important;
            }
        }
        .modal-price-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .modal-stock-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        @media screen and (max-width: 600px) {
            .modal-price-grid {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }
            .modal-stock-grid {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }
        }

        /* ==========================================================================
           Product Modals & Actions - Smooth Scrolling (Desktop & Mobile)
           ========================================================================== */
        .modal-overlay {
            display: none;
            position: fixed !important;
            inset: 0 !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100% !important;
            height: 100dvh !important;
            height: 100vh !important;
            background: rgba(15, 23, 42, 0.65) !important;
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;
            z-index: 99999 !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior: contain !important;
            touch-action: pan-y !important;
            box-sizing: border-box !important;
        }

        .modal-overlay.active {
            display: flex !important;
        }

        .modal-card {
            background: #FFFFFF !important;
            border-radius: 16px !important;
            width: 100% !important;
            max-width: 640px !important;
            height: auto !important;
            max-height: calc(100dvh - 40px) !important;
            max-height: calc(100vh - 40px) !important;
            display: flex !important;
            flex-direction: column !important;
            margin: auto !important;
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.22) !important;
            overflow: hidden !important;
            position: relative !important;
            box-sizing: border-box !important;
            animation: modalPop 0.2s ease-out !important;
        }

        .modal-card form {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            height: 100% !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .modal-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 18px 24px !important;
            border-bottom: 1px solid #E2E8F0 !important;
            background: #FFFFFF !important;
            flex-shrink: 0 !important;
            z-index: 10 !important;
            box-sizing: border-box !important;
        }

        .modal-title {
            font-family: 'Poppins', sans-serif !important;
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            color: #0F172A !important;
            margin: 0 !important;
        }

        .modal-close-btn {
            background: transparent !important;
            border: none !important;
            font-size: 1.6rem !important;
            color: #64748B !important;
            cursor: pointer !important;
            line-height: 1 !important;
            padding: 4px 8px !important;
            border-radius: 6px !important;
            transition: all 0.15s ease !important;
        }

        .modal-close-btn:hover {
            color: #0F172A !important;
            background: #F1F5F9 !important;
        }

        .modal-body {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior: contain !important;
            touch-action: pan-y !important;
            padding: 22px 24px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 18px !important;
            box-sizing: border-box !important;
        }

        .modal-body::-webkit-scrollbar {
            width: 7px;
        }
        .modal-body::-webkit-scrollbar-track {
            background: #F1F5F9;
            border-radius: 4px;
        }
        .modal-body::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 4px;
        }
        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }

        .modal-footer {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 12px !important;
            padding: 16px 24px !important;
            border-top: 1px solid #E2E8F0 !important;
            background: #FFFFFF !important;
            flex-shrink: 0 !important;
            z-index: 10 !important;
            box-sizing: border-box !important;
        }

        .btn-modal-cancel {
            padding: 10px 20px !important;
            border-radius: 12px !important;
            border: 1px solid #CBD5E1 !important;
            background: #FFFFFF !important;
            color: #475569 !important;
            font-weight: 600 !important;
            font-size: 0.92rem !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        .btn-modal-cancel:hover {
            background: #F8FAFC !important;
            color: #0F172A !important;
            border-color: #94A3B8 !important;
        }

        .btn-modal-submit {
            padding: 10px 24px !important;
            border-radius: 12px !important;
            border: none !important;
            background: #0D9488 !important;
            color: #FFFFFF !important;
            font-weight: 600 !important;
            font-size: 0.92rem !important;
            cursor: pointer !important;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.3) !important;
            transition: all 0.2s ease !important;
        }

        .btn-modal-submit:hover {
            background: #0F766E !important;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.4) !important;
            transform: translateY(-1px) !important;
        }

        .btn-modal-danger {
            padding: 10px 22px !important;
            border-radius: 12px !important;
            border: none !important;
            background: #DC2626 !important;
            color: #FFFFFF !important;
            font-weight: 600 !important;
            font-size: 0.92rem !important;
            cursor: pointer !important;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3) !important;
            transition: all 0.2s ease !important;
        }

        .btn-modal-danger:hover {
            background: #B91C1C !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4) !important;
            transform: translateY(-1px) !important;
        }

        /* 2x2 Action Button Grid for Product Cards */
        .prod-card-actions {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 8px !important;
            padding: 12px 14px 14px !important;
            border-top: 1px solid #F1F5F9 !important;
            box-sizing: border-box !important;
        }

        .btn-prod-action {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 5px !important;
            height: 34px !important;
            padding: 0 8px !important;
            border-radius: 8px !important;
            font-size: 0.78rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            text-decoration: none !important;
            transition: all 0.15s ease !important;
            border: none !important;
            box-sizing: border-box !important;
            white-space: nowrap !important;
        }

        .btn-edit {
            background-color: #0D9488 !important;
            color: #FFFFFF !important;
        }
        .btn-edit:hover {
            background-color: #0F766E !important;
            color: #FFFFFF !important;
        }

        .btn-delete {
            background-color: #FEF2F2 !important;
            border: 1px solid #FECACA !important;
            color: #DC2626 !important;
        }
        .btn-delete:hover {
            background-color: #FEE2E2 !important;
            border-color: #FCA5A5 !important;
            color: #B91C1C !important;
        }

        @media screen and (max-width: 640px) {
            .modal-overlay {
                padding: 0 !important;
                align-items: flex-end !important;
            }
            .modal-card {
                max-width: 100% !important;
                width: 100% !important;
                max-height: 94dvh !important;
                max-height: 94vh !important;
                border-radius: 20px 20px 0 0 !important;
                margin: 0 !important;
            }
            .modal-header {
                padding: 16px 20px !important;
            }
            .modal-body {
                padding: 16px 16px 20px !important;
                gap: 14px !important;
            }
            .modal-footer {
                padding: 12px 16px max(14px, env(safe-area-inset-bottom)) !important;
            }
        }
    </style>

    <!-- Desktop Header Bar -->
    <div class="cust-topbar prod-topbar">
        <div class="topbar-left">
            <h1 class="cust-heading prod-heading">Products</h1>
            <p class="cust-subheading prod-subheading">Manage your product catalog, pricing, inventory and promotions</p>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="topbarDate">{{ now()->format('D, M d, Y') }}</span>
            <div class="system-status-badge">
                <span class="status-dot"></span>
                <span>Business Owner</span>
            </div>
            <button class="theme-toggle-btn" id="desktopThemeToggleBtn" onclick="window.togglePiosTheme(event)" aria-label="Toggle Dark / Light Theme" title="Toggle Dark / Light Theme">
                <svg class="theme-icon-moon moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <svg class="theme-icon-sun sun-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </button>
            <button class="notif-bell-btn" aria-label="Notifications" title="Notifications">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notif-badge-dot"></span>
            </button>
        </div>
    </div>

    <!-- Feedback Messages -->
    @if (session('success'))
        <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between">
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Product Limit / Usage Quota Card -->
    <div class="prod-limit-card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: {{ $productCount >= $productLimit ? '#FEE2E2' : '#CCFBF1' }}; display: flex; align-items: center; justify-content: center; color: {{ $productCount >= $productLimit ? '#DC2626' : '#0D9488' }}; flex-shrink: 0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 22 9.5 18 21 6 21 2 9.5 12 2" />
                    </svg>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span style="font-size: 0.95rem; font-weight: 700; color: #1E293B;">Product Limit: {{ $productCount }} / {{ $productLimit }} Products</span>
                        @if ($productCount >= $productLimit)
                            <span style="font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA;">Limit Reached (20/20)</span>
                        @elseif ($productCount >= 16)
                            <span style="font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: #FEF3C7; color: #D97706; border: 1px solid #FDE68A;">{{ $remainingSlots }} Slots Remaining</span>
                        @else
                            <span style="font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: #CCFBF1; color: #0F766E; border: 1px solid #99F6E4;">{{ $remainingSlots }} Slots Available</span>
                        @endif
                    </div>
                    <p style="margin: 3px 0 0; font-size: 0.82rem; color: #64748B;">
                        @if ($productCount >= $productLimit)
                            Maximum limit of {{ $productLimit }} products per Business Owner reached. You cannot add new products.
                        @else
                            Each Business Owner is permitted up to {{ $productLimit }} products in their active catalog.
                        @endif
                    </p>
                </div>
            </div>
            <div style="text-align: right; min-width: 130px;">
                <div style="font-size: 1.25rem; font-weight: 800; color: {{ $productCount >= $productLimit ? '#DC2626' : '#0F766E' }};">
                    {{ $productCount }} <span style="font-size: 0.9rem; font-weight: 500; color: #94A3B8;">/ {{ $productLimit }}</span>
                </div>
                <span style="font-size: 0.76rem; font-weight: 600; color: #64748B;">Products Used</span>
            </div>
        </div>

        <!-- Progress Bar -->
        @php
            $percentage = min(100, round(($productCount / $productLimit) * 100));
            $barColor = $productCount >= $productLimit ? '#EF4444' : ($productCount >= 16 ? '#F59E0B' : '#0D9488');
        @endphp
        <div style="width: 100%; height: 8px; background: #F1F5F9; border-radius: 9999px; overflow: hidden; position: relative;">
            <div style="width: {{ $percentage }}%; height: 100%; background: {{ $barColor }}; border-radius: 9999px; transition: width 0.3s ease;"></div>
        </div>
    </div>

    <!-- Search and Action Bar -->
    <div class="cust-action-bar prod-action-bar">
        <form method="GET" action="{{ route('business.products.index') }}" class="cust-search-wrapper prod-search-box" style="width: 100%;">
            <svg class="cust-search-icon search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="search" id="productSearchInput" class="cust-search-input prod-search-input" placeholder="Search products..." value="{{ $search }}" autocomplete="off">
        </form>
        @if ($canAddProduct)
            <button type="button" class="btn-add-customer btn-new-product-desktop" id="openNewProductBtnDesktop" aria-label="New Product" style="background: #0D9488 !important; background-color: #0D9488 !important; background-image: none !important; color: #FFFFFF !important; padding: 12px 22px !important; border-radius: 12px !important; font-size: 0.95rem !important; font-weight: 600 !important; border: none !important; box-shadow: 0 2px 8px rgba(13, 148, 136, 0.3) !important;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>New Product</span>
            </button>
        @else
            <button type="button" class="btn-add-customer btn-new-product-desktop" id="openNewProductBtnDesktop" aria-label="New Product (Limit Reached)" disabled title="Maximum limit of {{ $productLimit }} products reached" style="background: #94A3B8 !important; background-color: #94A3B8 !important; background-image: none !important; color: #FFFFFF !important; padding: 12px 22px !important; border-radius: 12px !important; font-size: 0.95rem !important; font-weight: 600 !important; border: none !important; cursor: not-allowed !important; opacity: 0.75 !important; box-shadow: none !important;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Limit Reached ({{ $productCount }}/{{ $productLimit }})</span>
            </button>
        @endif
    </div>

    <!-- Category Filter Tabs -->
    <div class="prod-categories-bar" style="display: flex; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px;">
        <a href="{{ route('business.products.index', ['search' => $search]) }}" style="padding: 6px 14px; border-radius: 8px; text-decoration: none; font-size: 0.88rem; font-weight: 600; {{ $category === 'all' ? 'background: #0D9488; color: #fff;' : 'background: #fff; color: #475569; border: 1px solid #E2E8F0;' }}">
            All Products
        </a>
        @foreach ($productTypes as $pt)
            <a href="{{ route('business.products.index', ['category' => $pt->name, 'search' => $search]) }}" style="padding: 6px 14px; border-radius: 8px; text-decoration: none; font-size: 0.88rem; font-weight: 600; {{ $category === $pt->name ? 'background: #0D9488; color: #fff;' : 'background: #fff; color: #475569; border: 1px solid #E2E8F0;' }}">
                {{ $pt->name }}
            </a>
        @endforeach
    </div>

    <!-- Products Grid (4 cols desktop, 2 cols mobile) -->
    <div class="prod-grid" id="productGrid">
        @forelse ($products as $index => $prod)
            @php
                $imgUrl = $prod->firstShareableImageUrl() ?? asset('assets/images/products/prod_' . (($index % 6) + 1) . '.jpg');
                $categoryName = $prod->productType?->name ?? 'General Product';
                $waShareUrl = $prod->shareWhatsAppUrl();
                $prodJson = [
                    'id' => $prod->id,
                    'product_no' => $prod->product_no,
                    'name' => $prod->name,
                    'description' => $prod->description ?? '',
                    'selling_price' => (float) $prod->selling_price,
                    'cost' => (float) ($prod->cost ?? 0),
                    'stock_quantity' => (int) ($prod->stock_quantity ?? 0),
                    'coverage_area' => $prod->coverage_area ?? '',
                    'status' => $prod->status?->value ?? 'active',
                    'has_video' => $prod->hasVideo(),
                    'video_url' => $prod->videoUrl(),
                    'update_url' => route('business.products.update', $prod),
                    'delete_url' => route('business.products.destroy', $prod),
                ];
            @endphp
            <article class="prod-card" data-name="{{ $prod->name }}" data-category="{{ $categoryName }}" data-price="{{ $prod->selling_price }}">
                <div class="prod-card-thumb">
                    <img src="{{ $imgUrl }}" alt="{{ $prod->name }}" class="prod-img" onerror="this.src='{{ asset('assets/images/header_logo.png') }}'">
                    <span class="badge-active">{{ $prod->status?->value ?? 'Active' }}</span>
                </div>
                <div class="prod-card-body">
                    <h3 class="prod-title">{{ $prod->name }}</h3>
                    <div class="prod-category-tag">{{ $categoryName }}</div>
                    <div class="prod-price">RM {{ number_format((float)$prod->selling_price, 2) }}</div>
                    <p class="prod-desc">{{ \Illuminate\Support\Str::limit($prod->description ?? 'Premium quality guaranteed.', 80) }}</p>
                    <div class="prod-inventory">
                        <span class="stock-info">Stock: <strong class="stock-qty">{{ $prod->stock_quantity ?? 0 }}</strong></span>
                        @if ($prod->product_no)
                            <span class="sold-info">#{{ $prod->product_no }}</span>
                        @endif
                    </div>
                </div>
                <div class="prod-card-actions">
                    <a href="{{ $prod->publicUrl() }}" target="_blank" class="btn-prod-action btn-share text-decoration-none" title="View Public Page">
                        <svg class="action-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <span class="btn-text">View</span>
                    </a>
                    <a href="{{ $waShareUrl }}" target="_blank" class="btn-prod-action btn-blast text-decoration-none" title="Blast to WhatsApp">
                        <svg class="action-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.201.3-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.787-1.677-2.088-.175-.3-.019-.463.132-.613.136-.135.301-.351.452-.527.15-.175.201-.3.301-.501.101-.2.05-.376-.025-.526-.075-.15-.677-1.632-.928-2.235-.245-.587-.493-.508-.677-.517-.175-.01-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.3-1.054 1.03-1.054 2.511 0 1.482 1.079 2.911 1.23 3.112.15.2 2.124 3.243 5.145 4.548.719.311 1.28.497 1.718.636.722.23 1.379.197 1.9-.12.57-.348 1.782-.728 2.033-1.431.25-.703.25-1.306.175-1.431-.075-.125-.276-.2-.577-.35zM12 2a10 10 0 0 0-8.59 15.11L2 22l4.98-1.31A10 10 0 1 0 12 2z"></path>
                        </svg>
                        <span class="btn-text">Blast</span>
                    </a>
                    <button type="button" class="btn-prod-action btn-edit btn-edit-product" data-product="{{ json_encode($prodJson) }}" title="Edit Product">
                        <svg class="action-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <span class="btn-text">Edit</span>
                    </button>
                    <button type="button" class="btn-prod-action btn-delete btn-delete-product" data-id="{{ $prod->id }}" data-name="{{ $prod->name }}" data-url="{{ route('business.products.destroy', $prod) }}" title="Delete Product">
                        <svg class="action-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                        <span class="btn-text">Delete</span>
                    </button>
                </div>
            </article>
        @empty
            <div style="grid-column: 1 / -1; background: #FFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 48px; text-align: center; color: #64748B;">
                <p style="font-size: 1.1rem; font-weight: 600; margin-bottom: 8px;">No products available.</p>
                <p style="font-size: 0.9rem;">Submit a proposal to list your custom products in the catalog.</p>
            </div>
        @endforelse
    </div>

    <!-- Propose Product Modal -->
    <div class="modal-overlay" id="proposeModal">
        <div class="modal-card">
            <!-- Modal Header (Fixed at top) -->
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h3 class="modal-title">New Product</h3>
                    <span style="font-size: 0.76rem; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: {{ $productCount >= $productLimit ? '#FEE2E2' : '#CCFBF1' }}; color: {{ $productCount >= $productLimit ? '#DC2626' : '#0F766E' }};">
                        {{ $productCount }} / {{ $productLimit }} Used
                    </span>
                </div>
                <button type="button" class="modal-close-btn" id="closeProposeBtn" aria-label="Close modal">&times;</button>
            </div>

            <form action="{{ route('business.products.propose') }}" method="POST" enctype="multipart/form-data" id="proposeProductForm">
                @csrf

                <!-- Scrollable Body Content -->
                <div class="modal-body" id="proposeModalBody">
                    @if ($productCount >= $productLimit)
                        <div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; color: #991B1B; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" style="flex-shrink: 0;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <div>
                                <strong style="display: block;">Catalog Limit Reached ({{ $productCount }}/{{ $productLimit }})</strong>
                                <span style="font-size: 0.8rem; color: #B91C1C;">You have reached the maximum allowed limit of {{ $productLimit }} products. Please remove or manage existing products to add new ones.</span>
                            </div>
                        </div>
                    @endif

                    <!-- Product Name -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Product Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Organic Honey 500g" style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>

                    <!-- Price Row: Cost Price & Retail / Customer Price -->
                    <div class="modal-price-grid">
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Cost Price (RM) *</label>
                            <input type="number" step="0.01" min="0" name="cost_price" id="costPriceInput" required placeholder="0.00" style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                            <input type="hidden" name="partner_price" id="partnerPriceFallback" value="0.00">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Retail / Customer Price (RM) *</label>
                            <input type="number" step="0.01" min="0" name="customer_price" required placeholder="0.00" style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                    </div>

                    <!-- Photo Upload (Images: Max 4) -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem; font-weight: 700; color: #1E293B; margin: 0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                Gambar Produk (Pilihan - Maks 4 fail)
                            </label>
                            <span id="mediaCountBadge" style="font-size: 0.76rem; font-weight: 600; color: #0D9488; background: #CCFBF1; padding: 2px 8px; border-radius: 12px;">0 / 4 gambar</span>
                        </div>

                        <div style="background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 8px; padding: 8px 12px; margin-bottom: 12px; font-size: 0.78rem; color: #475569; line-height: 1.45;">
                            <div>
                                <strong>Spesifikasi Gambar:</strong> JPG, PNG, WEBP &bull; Maks 5MB seunit &bull; Had sehingga 4 gambar.
                            </div>
                        </div>

                        <div id="mediaDropzone" style="border: 2px dashed #CBD5E1; border-radius: 10px; background: #FFFFFF; padding: 16px 14px; text-align: center; cursor: pointer; transition: all 0.2s ease;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 6px; display: block;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p style="margin: 0; font-size: 0.84rem; color: #475569; font-weight: 500;">
                                Tarik &amp; lepas gambar ke sini atau <span style="color: #0D9488; font-weight: 700; text-decoration: underline;">Browse</span>
                            </p>
                            <p style="margin: 3px 0 0; font-size: 0.72rem; color: #94A3B8;">Pratonton gambar akan dipaparkan di bawah</p>
                            <input type="file" id="mediaFileInput" name="media_files[]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="display: none;">
                        </div>

                        <div id="mediaPreviewGrid" style="display: none; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px; margin-top: 12px;"></div>
                        <div id="mediaErrorMessage" style="display: none; margin-top: 8px; font-size: 0.78rem; color: #DC2626; background: #FEF2F2; padding: 6px 10px; border-radius: 6px;"></div>
                    </div>

                    <!-- Video Upload (Optional - Separate Upload) -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem; font-weight: 700; color: #1E293B; margin: 0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                </svg>
                                Video Produk (Pilihan - Asing)
                            </label>
                            <span style="font-size: 0.74rem; font-weight: 600; color: #0D9488; background: #CCFBF1; padding: 2px 8px; border-radius: 10px;">Autoplay di Hadapan</span>
                        </div>

                        <p style="font-size: 0.78rem; color: #64748B; margin: 0 0 10px; line-height: 1.45;">
                            Jika dimuat naik, video akan <strong>berada di kedudukan paling depan</strong> dan auto-play bila pelanggan membuka halaman produk <code>/p/xxx</code>.
                        </p>

                        <div id="proposeVideoDropzone" style="border: 2px dashed #CBD5E1; border-radius: 10px; background: #FFFFFF; padding: 14px; text-align: center; cursor: pointer; transition: all 0.2s ease;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 6px; display: block;">
                                <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                            </svg>
                            <p style="margin: 0; font-size: 0.84rem; color: #475569; font-weight: 500;">
                                Pilih fail video atau <span style="color: #0D9488; font-weight: 700; text-decoration: underline;">Browse Video</span>
                            </p>
                            <p style="margin: 3px 0 0; font-size: 0.72rem; color: #94A3B8;">Format MP4, WEBM, MOV &bull; Maksimum 30MB</p>
                            <input type="file" id="proposeVideoInput" name="video_file" accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime" style="display: none;">
                        </div>

                        <div id="proposeVideoPreviewBox" style="display: none; margin-top: 10px; background: #0F172A; border-radius: 8px; padding: 10px; color: #FFF;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <span id="proposeVideoFileName" style="font-size: 0.78rem; color: #2DD4BF; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 250px;"></span>
                                <button type="button" id="removeProposeVideoBtn" style="background: rgba(239,68,68,0.9); color: #FFF; border: none; padding: 2px 8px; border-radius: 6px; font-size: 0.72rem; cursor: pointer;">Padam Video</button>
                            </div>
                            <video id="proposeVideoPlayer" controls playsinline muted style="width: 100%; max-height: 160px; border-radius: 6px; background: #000;"></video>
                        </div>
                    </div>

                    <!-- Stock Management Function -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem; font-weight: 700; color: #1E293B; margin: 0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                Stock Function
                            </label>
                            <span style="font-size: 0.74rem; font-weight: 600; color: #0F766E; background: #CCFBF1; padding: 2px 8px; border-radius: 10px;">Inventory Control</span>
                        </div>

                        <div class="modal-stock-grid">
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 4px;">In Hand *</label>
                                <input type="number" min="0" name="stock_in_hand" id="stockInHandInput" placeholder="0" value="0" style="width: 100%; padding: 9px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.92rem; font-weight: 600; box-sizing: border-box;">
                                <span style="font-size: 0.72rem; color: #64748B;">Total currently in hand</span>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 4px;">Add Stock</label>
                                <input type="number" min="0" name="stock_added" id="stockAddedInput" placeholder="0" value="0" style="width: 100%; padding: 9px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.92rem; font-weight: 600; box-sizing: border-box;">
                                <span style="font-size: 0.72rem; color: #64748B;">New intake units</span>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 4px;">Date Add</label>
                                <input type="date" name="stock_date_added" id="stockDateAddedInput" value="{{ now()->format('Y-m-d') }}" style="width: 100%; padding: 9px 10px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.88rem; box-sizing: border-box;">
                                <span style="font-size: 0.72rem; color: #64748B;">Stock added date</span>
                            </div>
                        </div>

                        <!-- Dynamic Stock Calculation Summary -->
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #F0FDFA; border: 1px solid #CCFBF1; border-radius: 8px; padding: 8px 12px; margin-top: 10px; font-size: 0.82rem; color: #0F766E;">
                            <span>Total Available Stock: <strong id="totalStockCalc" style="font-size: 0.95rem; color: #0D9488;">0</strong> units</span>
                            <span id="dateCalcNotice" style="font-size: 0.75rem; color: #64748B;">Date: {{ now()->format('d M Y') }}</span>
                        </div>
                    </div>

                    <!-- Coverage Area / Territory (City Dropdown List) -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Coverage Area / Territory</label>
                        <select name="area" id="areaCitySelect" style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; background-color: #FFFFFF; color: #1E293B;">
                            <option value="">Select City / Coverage Territory</option>
                            <option value="Nationwide">Nationwide (All Malaysia)</option>
                            <optgroup label="Selangor">
                                <option value="Petaling Jaya, Selangor">Petaling Jaya</option>
                                <option value="Shah Alam, Selangor">Shah Alam</option>
                                <option value="Subang Jaya, Selangor">Subang Jaya</option>
                                <option value="Klang, Selangor">Klang</option>
                                <option value="Ampang, Selangor">Ampang</option>
                                <option value="Kajang, Selangor">Kajang</option>
                                <option value="Rawang, Selangor">Rawang</option>
                                <option value="Cyberjaya, Selangor">Cyberjaya</option>
                                <option value="Puchong, Selangor">Puchong</option>
                                <option value="Sepang, Selangor">Sepang</option>
                                <option value="Banting, Selangor">Banting</option>
                                <option value="Seri Kembangan, Selangor">Seri Kembangan</option>
                                <option value="Gombak, Selangor">Gombak</option>
                                <option value="Semenyih, Selangor">Semenyih</option>
                                <option value="Kuala Selangor, Selangor">Kuala Selangor</option>
                            </optgroup>
                            <optgroup label="Wilayah Persekutuan Kuala Lumpur">
                                <option value="Kuala Lumpur">Kuala Lumpur (Central)</option>
                                <option value="Cheras, Kuala Lumpur">Cheras</option>
                                <option value="Kepong, Kuala Lumpur">Kepong</option>
                                <option value="Setapak, Kuala Lumpur">Setapak</option>
                                <option value="Wangsa Maju, Kuala Lumpur">Wangsa Maju</option>
                                <option value="Bukit Bintang, Kuala Lumpur">Bukit Bintang</option>
                                <option value="Bangsar, Kuala Lumpur">Bangsar</option>
                                <option value="Sentul, Kuala Lumpur">Sentul</option>
                                <option value="Segambut, Kuala Lumpur">Segambut</option>
                                <option value="Mont Kiara, Kuala Lumpur">Mont Kiara</option>
                            </optgroup>
                            <optgroup label="Wilayah Persekutuan Putrajaya & Labuan">
                                <option value="Putrajaya">Putrajaya</option>
                                <option value="Victoria, Labuan">Victoria, Labuan</option>
                            </optgroup>
                            <optgroup label="Johor">
                                <option value="Johor Bahru, Johor">Johor Bahru</option>
                                <option value="Batu Pahat, Johor">Batu Pahat</option>
                                <option value="Kluang, Johor">Kluang</option>
                                <option value="Kulai, Johor">Kulai</option>
                                <option value="Muar, Johor">Muar</option>
                                <option value="Kota Tinggi, Johor">Kota Tinggi</option>
                                <option value="Segamat, Johor">Segamat</option>
                                <option value="Pontian, Johor">Pontian</option>
                                <option value="Tangkak, Johor">Tangkak</option>
                                <option value="Mersing, Johor">Mersing</option>
                                <option value="Pasir Gudang, Johor">Pasir Gudang</option>
                                <option value="Iskandar Puteri, Johor">Iskandar Puteri</option>
                            </optgroup>
                            <optgroup label="Pulau Pinang">
                                <option value="George Town, Pulau Pinang">George Town</option>
                                <option value="Butterworth, Pulau Pinang">Butterworth</option>
                                <option value="Bukit Mertajam, Pulau Pinang">Bukit Mertajam</option>
                                <option value="Bayan Lepas, Pulau Pinang">Bayan Lepas</option>
                                <option value="Seberang Perai, Pulau Pinang">Seberang Perai</option>
                                <option value="Balik Pulau, Pulau Pinang">Balik Pulau</option>
                                <option value="Nibong Tebal, Pulau Pinang">Nibong Tebal</option>
                                <option value="Kepala Batas, Pulau Pinang">Kepala Batas</option>
                            </optgroup>
                            <optgroup label="Perak">
                                <option value="Ipoh, Perak">Ipoh</option>
                                <option value="Taiping, Perak">Taiping</option>
                                <option value="Teluk Intan, Perak">Teluk Intan</option>
                                <option value="Manjung, Perak">Manjung</option>
                                <option value="Sitiawan, Perak">Sitiawan</option>
                                <option value="Kuala Kangsar, Perak">Kuala Kangsar</option>
                                <option value="Batu Gajah, Perak">Batu Gajah</option>
                                <option value="Kampar, Perak">Kampar</option>
                                <option value="Tapah, Perak">Tapah</option>
                                <option value="Tanjung Malim, Perak">Tanjung Malim</option>
                            </optgroup>
                            <optgroup label="Kedah">
                                <option value="Alor Setar, Kedah">Alor Setar</option>
                                <option value="Sungai Petani, Kedah">Sungai Petani</option>
                                <option value="Kulim, Kedah">Kulim</option>
                                <option value="Langkawi, Kedah">Langkawi</option>
                                <option value="Baling, Kedah">Baling</option>
                                <option value="Kubang Pasu, Kedah">Kubang Pasu</option>
                                <option value="Jitra, Kedah">Jitra</option>
                                <option value="Pendang, Kedah">Pendang</option>
                                <option value="Yan, Kedah">Yan</option>
                                <option value="Sik, Kedah">Sik</option>
                            </optgroup>
                            <optgroup label="Negeri Sembilan">
                                <option value="Seremban, Negeri Sembilan">Seremban</option>
                                <option value="Port Dickson, Negeri Sembilan">Port Dickson</option>
                                <option value="Nilai, Negeri Sembilan">Nilai</option>
                                <option value="Jempol, Negeri Sembilan">Jempol</option>
                                <option value="Tampin, Negeri Sembilan">Tampin</option>
                                <option value="Kuala Pilah, Negeri Sembilan">Kuala Pilah</option>
                                <option value="Rembau, Negeri Sembilan">Rembau</option>
                                <option value="Bahau, Negeri Sembilan">Bahau</option>
                            </optgroup>
                            <optgroup label="Melaka">
                                <option value="Melaka, Melaka">Melaka</option>
                                <option value="Alor Gajah, Melaka">Alor Gajah</option>
                                <option value="Jasin, Melaka">Jasin</option>
                                <option value="Ayer Keroh, Melaka">Ayer Keroh</option>
                                <option value="Batu Berendam, Melaka">Batu Berendam</option>
                                <option value="Masjid Tanah, Melaka">Masjid Tanah</option>
                            </optgroup>
                            <optgroup label="Pahang">
                                <option value="Kuantan, Pahang">Kuantan</option>
                                <option value="Temerloh, Pahang">Temerloh</option>
                                <option value="Bentong, Pahang">Bentong</option>
                                <option value="Cameron Highlands, Pahang">Cameron Highlands</option>
                                <option value="Raub, Pahang">Raub</option>
                                <option value="Jerantut, Pahang">Jerantut</option>
                                <option value="Pekan, Pahang">Pekan</option>
                                <option value="Maran, Pahang">Maran</option>
                                <option value="Rompin, Pahang">Rompin</option>
                                <option value="Bera, Pahang">Bera</option>
                            </optgroup>
                            <optgroup label="Kelantan">
                                <option value="Kota Bharu, Kelantan">Kota Bharu</option>
                                <option value="Pasir Mas, Kelantan">Pasir Mas</option>
                                <option value="Tumpat, Kelantan">Tumpat</option>
                                <option value="Bachok, Kelantan">Bachok</option>
                                <option value="Tanah Merah, Kelantan">Tanah Merah</option>
                                <option value="Pasir Puteh, Kelantan">Pasir Puteh</option>
                                <option value="Kuala Krai, Kelantan">Kuala Krai</option>
                                <option value="Machang, Kelantan">Machang</option>
                                <option value="Gua Musang, Kelantan">Gua Musang</option>
                                <option value="Jeli, Kelantan">Jeli</option>
                            </optgroup>
                            <optgroup label="Terengganu">
                                <option value="Kuala Terengganu, Terengganu">Kuala Terengganu</option>
                                <option value="Kemaman, Terengganu">Kemaman</option>
                                <option value="Dungun, Terengganu">Dungun</option>
                                <option value="Besut, Terengganu">Besut</option>
                                <option value="Marang, Terengganu">Marang</option>
                                <option value="Hulu Terengganu, Terengganu">Hulu Terengganu</option>
                                <option value="Setiu, Terengganu">Setiu</option>
                            </optgroup>
                            <optgroup label="Sabah">
                                <option value="Kota Kinabalu, Sabah">Kota Kinabalu</option>
                                <option value="Sandakan, Sabah">Sandakan</option>
                                <option value="Tawau, Sabah">Tawau</option>
                                <option value="Lahad Datu, Sabah">Lahad Datu</option>
                                <option value="Keningau, Sabah">Keningau</option>
                                <option value="Penampang, Sabah">Penampang</option>
                                <option value="Putatan, Sabah">Putatan</option>
                                <option value="Semporna, Sabah">Semporna</option>
                                <option value="Kudat, Sabah">Kudat</option>
                                <option value="Tuaran, Sabah">Tuaran</option>
                            </optgroup>
                            <optgroup label="Sarawak">
                                <option value="Kuching, Sarawak">Kuching</option>
                                <option value="Miri, Sarawak">Miri</option>
                                <option value="Sibu, Sarawak">Sibu</option>
                                <option value="Bintulu, Sarawak">Bintulu</option>
                                <option value="Samarahan, Sarawak">Samarahan</option>
                                <option value="Sri Aman, Sarawak">Sri Aman</option>
                                <option value="Sarikei, Sarawak">Sarikei</option>
                                <option value="Limbang, Sarawak">Limbang</option>
                                <option value="Kapit, Sarawak">Kapit</option>
                                <option value="Mukah, Sarawak">Mukah</option>
                            </optgroup>
                            <optgroup label="Perlis">
                                <option value="Kangar, Perlis">Kangar</option>
                                <option value="Arau, Perlis">Arau</option>
                                <option value="Padang Besar, Perlis">Padang Besar</option>
                                <option value="Kuala Perlis, Perlis">Kuala Perlis</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Description & Benefits -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Description &amp; Benefits</label>
                        <textarea name="description" rows="3" placeholder="Highlight key features and uniqueness..." style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;"></textarea>
                    </div>
                </div>

                <!-- Modal Footer (Fixed at bottom) -->
                <div class="modal-footer">
                    <button type="button" id="cancelProposeBtn" class="btn-modal-cancel">Cancel</button>
                    @if ($canAddProduct)
                        <button type="submit" id="submitProposeBtn" class="btn-modal-submit">Submit Proposal</button>
                    @else
                        <button type="button" id="submitProposeBtn" class="btn-modal-submit" disabled style="background: #94A3B8 !important; cursor: not-allowed !important; opacity: 0.75 !important;">Limit Reached ({{ $productCount }}/{{ $productLimit }})</button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal-overlay" id="editProductModal">
        <div class="modal-card">
            <!-- Modal Header -->
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h3 class="modal-title">Edit Product</h3>
                    <span id="editProductNoBadge" style="font-size: 0.76rem; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: #CCFBF1; color: #0F766E;">
                        #PRD
                    </span>
                </div>
                <button type="button" class="modal-close-btn" id="closeEditBtn" aria-label="Close modal">&times;</button>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" id="editProductForm">
                @csrf
                @method('PUT')

                <!-- Scrollable Body Content -->
                <div class="modal-body" id="editModalBody">
                    <!-- Product Name -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Product Name *</label>
                        <input type="text" name="name" id="editNameInput" required placeholder="e.g. Organic Honey 500g" style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        <span style="font-size: 0.72rem; color: #64748B;">Betulkan ejaan atau kemaskini nama produk</span>
                    </div>

                    <!-- Price Row: Cost Price & Retail / Customer Price -->
                    <div class="modal-price-grid">
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Cost Price (RM)</label>
                            <input type="number" step="0.01" min="0" name="cost_price" id="editCostPriceInput" placeholder="0.00" style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Retail / Customer Price (RM) *</label>
                            <input type="number" step="0.01" min="0" name="selling_price" id="editSellingPriceInput" required placeholder="0.00" style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                    </div>

                    <!-- Stock Management Function -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem; font-weight: 700; color: #1E293B; margin: 0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                Kawalan Stok
                            </label>
                            <span style="font-size: 0.74rem; font-weight: 600; color: #0F766E; background: #CCFBF1; padding: 2px 8px; border-radius: 10px;">Tambah &amp; Laraskan Stok</span>
                        </div>

                        <div class="modal-stock-grid">
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 4px;">Stok Semasa</label>
                                <input type="number" min="0" name="stock_quantity" id="editStockQuantityInput" placeholder="0" value="0" style="width: 100%; padding: 9px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.92rem; font-weight: 600; box-sizing: border-box;">
                                <span style="font-size: 0.72rem; color: #64748B;">Baki sedia ada</span>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 4px;">+ Tambah Stok</label>
                                <input type="number" min="0" name="stock_added" id="editStockAddedInput" placeholder="0" value="0" style="width: 100%; padding: 9px 12px; border: 1.5px solid #0D9488; background: #F0FDFA; border-radius: 8px; font-size: 0.92rem; font-weight: 700; color: #0F766E; box-sizing: border-box;">
                                <span style="font-size: 0.72rem; color: #0D9488; font-weight: 600;">Kemasukan unit baru</span>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 4px;">Tarikh Tambah</label>
                                <input type="date" name="stock_date_added" id="editStockDateAddedInput" value="{{ now()->format('Y-m-d') }}" style="width: 100%; padding: 9px 10px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.88rem; box-sizing: border-box;">
                                <span style="font-size: 0.72rem; color: #64748B;">Tarikh kemaskini</span>
                            </div>
                        </div>

                        <!-- Dynamic Stock Calculation Summary -->
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #F0FDFA; border: 1px solid #CCFBF1; border-radius: 8px; padding: 8px 12px; margin-top: 10px; font-size: 0.82rem; color: #0F766E;">
                            <span>Jumlah Stok Baharu: <strong id="editTotalStockCalc" style="font-size: 0.95rem; color: #0D9488;">0</strong> unit</span>
                            <span style="font-size: 0.75rem; color: #64748B;">Auto Dikira</span>
                        </div>
                    </div>

                    <!-- Video Upload (Optional - Separate Upload / Replace / Remove) -->
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem; font-weight: 700; color: #1E293B; margin: 0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                </svg>
                                Video Produk (Pilihan - Asing)
                            </label>
                            <span style="font-size: 0.74rem; font-weight: 600; color: #0D9488; background: #CCFBF1; padding: 2px 8px; border-radius: 10px;">Autoplay di Hadapan</span>
                        </div>

                        <p style="font-size: 0.78rem; color: #64748B; margin: 0 0 10px; line-height: 1.45;">
                            Jika dimuat naik, video akan <strong>berada di depan sekali</strong> dan auto-play bila pelanggan melihat produk di <code>/p/xxx</code>.
                        </p>

                        <!-- Current Video Player Preview (if exists) -->
                        <div id="editCurrentVideoBox" style="display: none; background: #0F172A; border-radius: 8px; padding: 10px; margin-bottom: 10px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <span style="color: #2DD4BF; font-size: 0.78rem; font-weight: 600;">Video Sedia Ada Aktif</span>
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: #FCA5A5; cursor: pointer;">
                                    <input type="checkbox" name="remove_video" value="1" id="editRemoveVideoCheck">
                                    Padam video ini
                                </label>
                            </div>
                            <video id="editCurrentVideoPlayer" controls playsinline muted style="width: 100%; max-height: 160px; border-radius: 6px; background: #000;"></video>
                        </div>

                        <div id="editVideoDropzone" style="border: 2px dashed #CBD5E1; border-radius: 10px; background: #FFFFFF; padding: 14px; text-align: center; cursor: pointer; transition: all 0.2s ease;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 6px; display: block;">
                                <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                            </svg>
                            <p style="margin: 0; font-size: 0.84rem; color: #475569; font-weight: 500;">
                                Muat naik video baru atau <span style="color: #0D9488; font-weight: 700; text-decoration: underline;">Browse Video</span>
                            </p>
                            <p style="margin: 3px 0 0; font-size: 0.72rem; color: #94A3B8;">Format MP4, WEBM, MOV &bull; Maksimum 30MB</p>
                            <input type="file" id="editVideoFileInput" name="video_file" accept=".mp4,.webm,.mov,video/mp4,video/webm,video/quicktime" style="display: none;">
                        </div>

                        <div id="editVideoPreviewBox" style="display: none; margin-top: 10px; background: #0F172A; border-radius: 8px; padding: 10px; color: #FFF;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <span id="editVideoFileName" style="font-size: 0.78rem; color: #2DD4BF; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 250px;"></span>
                                <button type="button" id="removeEditVideoBtn" style="background: rgba(239,68,68,0.9); color: #FFF; border: none; padding: 2px 8px; border-radius: 6px; font-size: 0.72rem; cursor: pointer;">Batal Video Baru</button>
                            </div>
                            <video id="editVideoPlayer" controls playsinline muted style="width: 100%; max-height: 160px; border-radius: 6px; background: #000;"></video>
                        </div>
                    </div>

                    <!-- Ganti Gambar Produk (Pilihan) -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Ganti Gambar Produk (Pilihan - Maks 4 fail)</label>
                        <input type="file" name="media_files[]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="width: 100%; padding: 8px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.88rem; box-sizing: border-box; background: #FFFFFF;">
                        <span style="font-size: 0.72rem; color: #64748B;">Biarkan kosong jika ingin mengekalkan gambar sedia ada.</span>
                    </div>

                    <!-- Coverage Area / Territory -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Coverage Area / Territory</label>
                        <select name="coverage_area" id="editAreaSelect" style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; background-color: #FFFFFF; color: #1E293B;">
                            <option value="">Select City / Coverage Territory</option>
                            <option value="Nationwide">Nationwide (All Malaysia)</option>
                            <optgroup label="Selangor">
                                <option value="Petaling Jaya, Selangor">Petaling Jaya</option>
                                <option value="Shah Alam, Selangor">Shah Alam</option>
                                <option value="Subang Jaya, Selangor">Subang Jaya</option>
                                <option value="Klang, Selangor">Klang</option>
                                <option value="Ampang, Selangor">Ampang</option>
                                <option value="Kajang, Selangor">Kajang</option>
                                <option value="Rawang, Selangor">Rawang</option>
                                <option value="Cyberjaya, Selangor">Cyberjaya</option>
                                <option value="Puchong, Selangor">Puchong</option>
                                <option value="Sepang, Selangor">Sepang</option>
                                <option value="Banting, Selangor">Banting</option>
                                <option value="Seri Kembangan, Selangor">Seri Kembangan</option>
                                <option value="Gombak, Selangor">Gombak</option>
                                <option value="Semenyih, Selangor">Semenyih</option>
                                <option value="Kuala Selangor, Selangor">Kuala Selangor</option>
                            </optgroup>
                            <optgroup label="Wilayah Persekutuan Kuala Lumpur">
                                <option value="Kuala Lumpur">Kuala Lumpur (Central)</option>
                                <option value="Cheras, Kuala Lumpur">Cheras</option>
                                <option value="Kepong, Kuala Lumpur">Kepong</option>
                                <option value="Setapak, Kuala Lumpur">Setapak</option>
                                <option value="Wangsa Maju, Kuala Lumpur">Wangsa Maju</option>
                                <option value="Bukit Bintang, Kuala Lumpur">Bukit Bintang</option>
                                <option value="Bangsar, Kuala Lumpur">Bangsar</option>
                                <option value="Sentul, Kuala Lumpur">Sentul</option>
                                <option value="Segambut, Kuala Lumpur">Segambut</option>
                                <option value="Mont Kiara, Kuala Lumpur">Mont Kiara</option>
                            </optgroup>
                            <optgroup label="Wilayah Persekutuan Putrajaya & Labuan">
                                <option value="Putrajaya">Putrajaya</option>
                                <option value="Victoria, Labuan">Victoria, Labuan</option>
                            </optgroup>
                            <optgroup label="Johor">
                                <option value="Johor Bahru, Johor">Johor Bahru</option>
                                <option value="Batu Pahat, Johor">Batu Pahat</option>
                                <option value="Kluang, Johor">Kluang</option>
                                <option value="Kulai, Johor">Kulai</option>
                                <option value="Muar, Johor">Muar</option>
                                <option value="Kota Tinggi, Johor">Kota Tinggi</option>
                                <option value="Segamat, Johor">Segamat</option>
                                <option value="Pontian, Johor">Pontian</option>
                                <option value="Tangkak, Johor">Tangkak</option>
                                <option value="Mersing, Johor">Mersing</option>
                                <option value="Pasir Gudang, Johor">Pasir Gudang</option>
                                <option value="Iskandar Puteri, Johor">Iskandar Puteri</option>
                            </optgroup>
                            <optgroup label="Pulau Pinang">
                                <option value="George Town, Pulau Pinang">George Town</option>
                                <option value="Butterworth, Pulau Pinang">Butterworth</option>
                                <option value="Bukit Mertajam, Pulau Pinang">Bukit Mertajam</option>
                                <option value="Bayan Lepas, Pulau Pinang">Bayan Lepas</option>
                                <option value="Seberang Perai, Pulau Pinang">Seberang Perai</option>
                                <option value="Balik Pulau, Pulau Pinang">Balik Pulau</option>
                                <option value="Nibong Tebal, Pulau Pinang">Nibong Tebal</option>
                                <option value="Kepala Batas, Pulau Pinang">Kepala Batas</option>
                            </optgroup>
                            <optgroup label="Perak">
                                <option value="Ipoh, Perak">Ipoh</option>
                                <option value="Taiping, Perak">Taiping</option>
                                <option value="Teluk Intan, Perak">Teluk Intan</option>
                                <option value="Manjung, Perak">Manjung</option>
                                <option value="Sitiawan, Perak">Sitiawan</option>
                                <option value="Kuala Kangsar, Perak">Kuala Kangsar</option>
                                <option value="Batu Gajah, Perak">Batu Gajah</option>
                                <option value="Kampar, Perak">Kampar</option>
                                <option value="Tapah, Perak">Tapah</option>
                                <option value="Tanjung Malim, Perak">Tanjung Malim</option>
                            </optgroup>
                            <optgroup label="Kedah">
                                <option value="Alor Setar, Kedah">Alor Setar</option>
                                <option value="Sungai Petani, Kedah">Sungai Petani</option>
                                <option value="Kulim, Kedah">Kulim</option>
                                <option value="Langkawi, Kedah">Langkawi</option>
                                <option value="Baling, Kedah">Baling</option>
                                <option value="Kubang Pasu, Kedah">Kubang Pasu</option>
                                <option value="Jitra, Kedah">Jitra</option>
                                <option value="Pendang, Kedah">Pendang</option>
                                <option value="Yan, Kedah">Yan</option>
                                <option value="Sik, Kedah">Sik</option>
                            </optgroup>
                            <optgroup label="Negeri Sembilan">
                                <option value="Seremban, Negeri Sembilan">Seremban</option>
                                <option value="Port Dickson, Negeri Sembilan">Port Dickson</option>
                                <option value="Nilai, Negeri Sembilan">Nilai</option>
                                <option value="Jempol, Negeri Sembilan">Jempol</option>
                                <option value="Tampin, Negeri Sembilan">Tampin</option>
                                <option value="Kuala Pilah, Negeri Sembilan">Kuala Pilah</option>
                                <option value="Rembau, Negeri Sembilan">Rembau</option>
                                <option value="Bahau, Negeri Sembilan">Bahau</option>
                            </optgroup>
                            <optgroup label="Melaka">
                                <option value="Melaka, Melaka">Melaka</option>
                                <option value="Alor Gajah, Melaka">Alor Gajah</option>
                                <option value="Jasin, Melaka">Jasin</option>
                                <option value="Ayer Keroh, Melaka">Ayer Keroh</option>
                                <option value="Batu Berendam, Melaka">Batu Berendam</option>
                                <option value="Masjid Tanah, Melaka">Masjid Tanah</option>
                            </optgroup>
                            <optgroup label="Pahang">
                                <option value="Kuantan, Pahang">Kuantan</option>
                                <option value="Temerloh, Pahang">Temerloh</option>
                                <option value="Bentong, Pahang">Bentong</option>
                                <option value="Cameron Highlands, Pahang">Cameron Highlands</option>
                                <option value="Raub, Pahang">Raub</option>
                                <option value="Jerantut, Pahang">Jerantut</option>
                                <option value="Pekan, Pahang">Pekan</option>
                                <option value="Maran, Pahang">Maran</option>
                                <option value="Rompin, Pahang">Rompin</option>
                                <option value="Bera, Pahang">Bera</option>
                            </optgroup>
                            <optgroup label="Kelantan">
                                <option value="Kota Bharu, Kelantan">Kota Bharu</option>
                                <option value="Pasir Mas, Kelantan">Pasir Mas</option>
                                <option value="Tumpat, Kelantan">Tumpat</option>
                                <option value="Bachok, Kelantan">Bachok</option>
                                <option value="Tanah Merah, Kelantan">Tanah Merah</option>
                                <option value="Pasir Puteh, Kelantan">Pasir Puteh</option>
                                <option value="Kuala Krai, Kelantan">Kuala Krai</option>
                                <option value="Machang, Kelantan">Machang</option>
                                <option value="Gua Musang, Kelantan">Gua Musang</option>
                                <option value="Jeli, Kelantan">Jeli</option>
                            </optgroup>
                            <optgroup label="Terengganu">
                                <option value="Kuala Terengganu, Terengganu">Kuala Terengganu</option>
                                <option value="Kemaman, Terengganu">Kemaman</option>
                                <option value="Dungun, Terengganu">Dungun</option>
                                <option value="Besut, Terengganu">Besut</option>
                                <option value="Marang, Terengganu">Marang</option>
                                <option value="Hulu Terengganu, Terengganu">Hulu Terengganu</option>
                                <option value="Setiu, Terengganu">Setiu</option>
                            </optgroup>
                            <optgroup label="Sabah">
                                <option value="Kota Kinabalu, Sabah">Kota Kinabalu</option>
                                <option value="Sandakan, Sabah">Sandakan</option>
                                <option value="Tawau, Sabah">Tawau</option>
                                <option value="Lahad Datu, Sabah">Lahad Datu</option>
                                <option value="Keningau, Sabah">Keningau</option>
                                <option value="Penampang, Sabah">Penampang</option>
                                <option value="Putatan, Sabah">Putatan</option>
                                <option value="Semporna, Sabah">Semporna</option>
                                <option value="Kudat, Sabah">Kudat</option>
                                <option value="Tuaran, Sabah">Tuaran</option>
                            </optgroup>
                            <optgroup label="Sarawak">
                                <option value="Kuching, Sarawak">Kuching</option>
                                <option value="Miri, Sarawak">Miri</option>
                                <option value="Sibu, Sarawak">Sibu</option>
                                <option value="Bintulu, Sarawak">Bintulu</option>
                                <option value="Samarahan, Sarawak">Samarahan</option>
                                <option value="Sri Aman, Sarawak">Sri Aman</option>
                                <option value="Sarikei, Sarawak">Sarikei</option>
                                <option value="Limbang, Sarawak">Limbang</option>
                                <option value="Kapit, Sarawak">Kapit</option>
                                <option value="Mukah, Sarawak">Mukah</option>
                            </optgroup>
                            <optgroup label="Perlis">
                                <option value="Kangar, Perlis">Kangar</option>
                                <option value="Arau, Perlis">Arau</option>
                                <option value="Padang Besar, Perlis">Padang Besar</option>
                                <option value="Kuala Perlis, Perlis">Kuala Perlis</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Status Produk</label>
                        <select name="status" id="editStatusSelect" style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; background-color: #FFFFFF; color: #1E293B;">
                            <option value="active">Active (Tersedia untuk jualan)</option>
                            <option value="inactive">Inactive (Sembunyikan dari katalog)</option>
                        </select>
                    </div>

                    <!-- Description & Benefits -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Description &amp; Benefits</label>
                        <textarea name="description" id="editDescriptionInput" rows="3" placeholder="Highlight key features and uniqueness..." style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;"></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" id="cancelEditBtn" class="btn-modal-cancel">Cancel</button>
                    <button type="submit" id="submitEditBtn" class="btn-modal-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Product Confirmation Modal -->
    <div class="modal-overlay" id="deleteProductModal">
        <div class="modal-card" style="max-width: 480px !important;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #DC2626 !important; display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Delete Product
                </h3>
                <button type="button" class="modal-close-btn" id="closeDeleteBtn" aria-label="Close modal">&times;</button>
            </div>

            <form action="" method="POST" id="deleteProductForm">
                @csrf
                @method('DELETE')

                <div class="modal-body" style="padding: 20px 24px !important; gap: 14px !important;">
                    <p style="font-size: 0.95rem; color: #1E293B; margin: 0; line-height: 1.5;">
                        Adakah anda pasti ingin memadam produk <strong id="deleteProductName" style="color: #0F172A;"></strong>?
                    </p>
                    <div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 10px; padding: 12px 14px; color: #991B1B; font-size: 0.85rem; line-height: 1.45;">
                        <strong>Perhatian:</strong> Produk ini akan dikeluarkan daripada kedai katalog. Tindakan ini akan <strong>mengosongkan 1 slot daripada kuota 20 produk</strong> anda supaya anda boleh mendaftar produk lain.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="cancelDeleteBtn" class="btn-modal-cancel">Cancel</button>
                    <button type="submit" class="btn-modal-danger">Ya, Padam Produk</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile Sticky CTA Button -->
    <div class="mobile-sticky-footer">
        @if ($canAddProduct)
            <button type="button" class="btn-new-product-mobile btn-mobile-cta" id="mobileNewProductBtn" aria-label="New Product" style="background-color: var(--color-teal, #0D9488); color: #FFFFFF;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>New Product</span>
            </button>
        @else
            <button type="button" class="btn-new-product-mobile btn-mobile-cta" id="mobileNewProductBtn" aria-label="New Product (Limit Reached)" disabled title="Maximum limit of {{ $productLimit }} products reached" style="background-color: #94A3B8 !important; color: #FFFFFF; cursor: not-allowed !important; opacity: 0.75 !important;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Limit Reached ({{ $productCount }}/{{ $productLimit }})</span>
            </button>
        @endif
    </div>

    @push('scripts')
    <script src="{{ asset('assets/js/products.js') }}?v={{ file_exists(public_path('assets/js/products.js')) ? filemtime(public_path('assets/js/products.js')) : time() }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // General Modal Utilities
            function openModalEl(modalEl) {
                if (modalEl) {
                    modalEl.style.display = 'flex';
                    modalEl.classList.add('active');
                    document.body.style.overflow = 'hidden';
                    const card = modalEl.querySelector('.modal-card');
                    if (card) card.scrollTop = 0;
                    const body = modalEl.querySelector('.modal-body');
                    if (body) body.scrollTop = 0;
                }
            }

            function closeModalEl(modalEl) {
                if (modalEl) {
                    modalEl.style.display = 'none';
                    modalEl.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }

            // Close modals when clicking backdrop
            document.querySelectorAll('.modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        closeModalEl(overlay);
                    }
                });
            });

            // Close active modal on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-overlay.active').forEach(m => closeModalEl(m));
                }
            });

            // =========================================================================
            // 1. Propose / New Product Modal
            // =========================================================================
            const proposeModal = document.getElementById('proposeModal');
            const openProposeDesktop = document.getElementById('openNewProductBtnDesktop');
            const openProposeMobile = document.getElementById('mobileNewProductBtn');
            const closeProposeBtn = document.getElementById('closeProposeBtn');
            const cancelProposeBtn = document.getElementById('cancelProposeBtn');
            const canAdd = {{ $canAddProduct ? 'true' : 'false' }};
            const productLimit = {{ (int) $productLimit }};
            const productCount = {{ (int) $productCount }};

            function triggerOpenPropose(e) {
                if (e) e.preventDefault();
                if (!canAdd || productCount >= productLimit) {
                    alert('You have reached the maximum limit of ' + productLimit + ' products per Business Owner. Please remove or manage existing products to add new ones.');
                    return;
                }
                openModalEl(proposeModal);
            }

            if (openProposeDesktop) openProposeDesktop.addEventListener('click', triggerOpenPropose);
            if (openProposeMobile) openProposeMobile.addEventListener('click', triggerOpenPropose);
            if (closeProposeBtn) closeProposeBtn.addEventListener('click', () => closeModalEl(proposeModal));
            if (cancelProposeBtn) cancelProposeBtn.addEventListener('click', () => closeModalEl(proposeModal));

            const proposeForm = document.getElementById('proposeProductForm');
            if (proposeForm) {
                proposeForm.addEventListener('submit', function(e) {
                    if (!canAdd || productCount >= productLimit) {
                        e.preventDefault();
                        alert('You have reached the maximum limit of ' + productLimit + ' products per Business Owner.');
                        return false;
                    }
                });
            }

            // Propose Cost -> Partner Price fallback
            const costInput = document.getElementById('costPriceInput');
            const partnerFallback = document.getElementById('partnerPriceFallback');
            if (costInput && partnerFallback) {
                costInput.addEventListener('input', () => {
                    partnerFallback.value = costInput.value;
                });
            }

            // Propose Stock Calculation
            const stockInHand = document.getElementById('stockInHandInput');
            const stockAdded = document.getElementById('stockAddedInput');
            const totalStockCalc = document.getElementById('totalStockCalc');
            const stockDateAdded = document.getElementById('stockDateAddedInput');
            const dateCalcNotice = document.getElementById('dateCalcNotice');

            function recalculateStock() {
                const inHand = parseInt(stockInHand ? stockInHand.value : 0) || 0;
                const added = parseInt(stockAdded ? stockAdded.value : 0) || 0;
                const total = inHand + added;
                if (totalStockCalc) totalStockCalc.textContent = total;

                if (stockDateAdded && dateCalcNotice && stockDateAdded.value) {
                    const dateObj = new Date(stockDateAdded.value);
                    if (!isNaN(dateObj.getTime())) {
                        dateCalcNotice.textContent = 'Date: ' + dateObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                    }
                }
            }

            if (stockInHand) stockInHand.addEventListener('input', recalculateStock);
            if (stockAdded) stockAdded.addEventListener('input', recalculateStock);
            if (stockDateAdded) stockDateAdded.addEventListener('change', recalculateStock);
            recalculateStock();

            // Propose Photos Upload (Max 4 images <= 5MB)
            const dropzone = document.getElementById('mediaDropzone');
            const fileInput = document.getElementById('mediaFileInput');
            const previewGrid = document.getElementById('mediaPreviewGrid');
            const countBadge = document.getElementById('mediaCountBadge');
            const errorNotice = document.getElementById('mediaErrorMessage');

            let selectedFiles = [];
            const MAX_FILES = 4;
            const MAX_IMG_SIZE = 5 * 1024 * 1024;

            if (dropzone && fileInput) {
                dropzone.addEventListener('click', () => {
                    if (selectedFiles.length >= MAX_FILES) {
                        showMediaError('Maksimum 4 gambar dibenarkan bagi setiap produk.');
                        return;
                    }
                    fileInput.click();
                });

                dropzone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropzone.style.borderColor = '#0D9488';
                    dropzone.style.background = '#F0FDFA';
                });

                dropzone.addEventListener('dragleave', () => {
                    dropzone.style.borderColor = '#CBD5E1';
                    dropzone.style.background = '#FFFFFF';
                });

                dropzone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropzone.style.borderColor = '#CBD5E1';
                    dropzone.style.background = '#FFFFFF';
                    if (e.dataTransfer && e.dataTransfer.files) {
                        handleNewFiles(Array.from(e.dataTransfer.files));
                    }
                });

                fileInput.addEventListener('change', (e) => {
                    if (e.target.files) {
                        handleNewFiles(Array.from(e.target.files));
                    }
                });
            }

            function showMediaError(msg) {
                if (!errorNotice) return;
                errorNotice.textContent = msg;
                errorNotice.style.display = 'block';
                setTimeout(() => {
                    errorNotice.style.display = 'none';
                }, 4500);
            }

            function handleNewFiles(files) {
                if (errorNotice) errorNotice.style.display = 'none';

                for (const file of files) {
                    if (selectedFiles.length >= MAX_FILES) {
                        showMediaError('Maksimum ' + MAX_FILES + ' gambar dibenarkan bagi setiap produk.');
                        break;
                    }

                    if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                        continue;
                    }

                    const isImage = file.type.startsWith('image/') || /\.(jpe?g|png|webp)$/i.test(file.name);
                    if (!isImage) {
                        showMediaError('Format fail "' + file.name + '" tidak disokong. Sila muat naik JPG, PNG, atau WEBP.');
                        continue;
                    }

                    if (file.size > MAX_IMG_SIZE) {
                        showMediaError('Gambar "' + file.name + '" melebihi had saiz 5MB.');
                        continue;
                    }

                    selectedFiles.push(file);
                }

                syncFileInput();
                renderPreviews();
            }

            function syncFileInput() {
                if (!fileInput) return;
                const dt = new DataTransfer();
                selectedFiles.forEach(f => dt.items.add(f));
                fileInput.files = dt.files;
                if (countBadge) countBadge.textContent = selectedFiles.length + ' / ' + MAX_FILES + ' gambar';
            }

            function renderPreviews() {
                if (!previewGrid) return;
                previewGrid.innerHTML = '';

                if (selectedFiles.length === 0) {
                    previewGrid.style.display = 'none';
                    return;
                }

                previewGrid.style.display = 'grid';

                selectedFiles.forEach((file, index) => {
                    const card = document.createElement('div');
                    card.style.cssText = 'position: relative; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; padding: 6px; display: flex; flex-direction: column; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);';

                    const sizeStr = (file.size / (1024 * 1024)).toFixed(1) + ' MB';
                    const url = URL.createObjectURL(file);

                    card.innerHTML = '<img src="' + url + '" style="width: 100%; height: 75px; object-fit: cover; border-radius: 6px;" alt="' + file.name + '">' +
                        '<div style="width: 100%; margin-top: 4px; display: flex; align-items: center; justify-content: space-between;">' +
                            '<span style="font-size: 0.7rem; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 65px;" title="' + file.name + '">' + file.name + '</span>' +
                            '<span style="font-size: 0.65rem; color: #94A3B8;">' + sizeStr + '</span>' +
                        '</div>' +
                        '<button type="button" class="remove-media-btn" data-index="' + index + '" style="position: absolute; top: 4px; right: 4px; background: rgba(239,68,68,0.9); color: #FFF; border: none; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; line-height: 1; padding: 0;" title="Padam gambar">&times;</button>';

                    previewGrid.appendChild(card);
                });

                const removeBtns = previewGrid.querySelectorAll('.remove-media-btn');
                removeBtns.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const idx = parseInt(btn.getAttribute('data-index'), 10);
                        selectedFiles.splice(idx, 1);
                        syncFileInput();
                        renderPreviews();
                    });
                });
            }

            // Propose Video Upload (Separate Optional Video <= 30MB)
            const proposeVideoDropzone = document.getElementById('proposeVideoDropzone');
            const proposeVideoInput = document.getElementById('proposeVideoInput');
            const proposeVideoPreviewBox = document.getElementById('proposeVideoPreviewBox');
            const proposeVideoFileName = document.getElementById('proposeVideoFileName');
            const proposeVideoPlayer = document.getElementById('proposeVideoPlayer');
            const removeProposeVideoBtn = document.getElementById('removeProposeVideoBtn');
            const MAX_VID_SIZE = 30 * 1024 * 1024;

            if (proposeVideoDropzone && proposeVideoInput) {
                proposeVideoDropzone.addEventListener('click', () => {
                    proposeVideoInput.click();
                });

                proposeVideoInput.addEventListener('change', (e) => {
                    const file = e.target.files && e.target.files[0];
                    if (!file) return;

                    const isVideo = file.type.startsWith('video/') || /\.(mp4|webm|mov)$/i.test(file.name);
                    if (!isVideo) {
                        alert('Format video tidak disokong. Sila pilih fail MP4, WEBM, atau MOV.');
                        proposeVideoInput.value = '';
                        return;
                    }

                    if (file.size > MAX_VID_SIZE) {
                        alert('Saiz fail video (' + (file.size / (1024 * 1024)).toFixed(1) + 'MB) melebihi had maksimum 30MB.');
                        proposeVideoInput.value = '';
                        return;
                    }

                    if (proposeVideoPreviewBox && proposeVideoPlayer && proposeVideoFileName) {
                        proposeVideoPlayer.src = URL.createObjectURL(file);
                        proposeVideoFileName.textContent = file.name + ' (' + (file.size / (1024 * 1024)).toFixed(1) + ' MB)';
                        proposeVideoPreviewBox.style.display = 'block';
                    }
                });
            }

            if (removeProposeVideoBtn) {
                removeProposeVideoBtn.addEventListener('click', () => {
                    if (proposeVideoInput) proposeVideoInput.value = '';
                    if (proposeVideoPlayer) proposeVideoPlayer.src = '';
                    if (proposeVideoPreviewBox) proposeVideoPreviewBox.style.display = 'none';
                });
            }

            // =========================================================================
            // 2. Edit Product Modal Wiring
            // =========================================================================
            const editModal = document.getElementById('editProductModal');
            const closeEditBtn = document.getElementById('closeEditBtn');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const editForm = document.getElementById('editProductForm');

            const editStockQtyInput = document.getElementById('editStockQuantityInput');
            const editStockAddedInput = document.getElementById('editStockAddedInput');
            const editTotalStockCalc = document.getElementById('editTotalStockCalc');

            function recalculateEditStock() {
                const cur = parseInt(editStockQtyInput ? editStockQtyInput.value : 0) || 0;
                const add = parseInt(editStockAddedInput ? editStockAddedInput.value : 0) || 0;
                const total = Math.max(0, cur + add);
                if (editTotalStockCalc) editTotalStockCalc.textContent = total;
            }

            if (editStockQtyInput) editStockQtyInput.addEventListener('input', recalculateEditStock);
            if (editStockAddedInput) editStockAddedInput.addEventListener('input', recalculateEditStock);

            // Edit Video Dropzone & Preview
            const editVideoDropzone = document.getElementById('editVideoDropzone');
            const editVideoFileInput = document.getElementById('editVideoFileInput');
            const editVideoPreviewBox = document.getElementById('editVideoPreviewBox');
            const editVideoFileName = document.getElementById('editVideoFileName');
            const editVideoPlayer = document.getElementById('editVideoPlayer');
            const removeEditVideoBtn = document.getElementById('removeEditVideoBtn');

            if (editVideoDropzone && editVideoFileInput) {
                editVideoDropzone.addEventListener('click', () => {
                    editVideoFileInput.click();
                });

                editVideoFileInput.addEventListener('change', (e) => {
                    const file = e.target.files && e.target.files[0];
                    if (!file) return;

                    const isVideo = file.type.startsWith('video/') || /\.(mp4|webm|mov)$/i.test(file.name);
                    if (!isVideo) {
                        alert('Format video tidak disokong. Sila pilih fail MP4, WEBM, atau MOV.');
                        editVideoFileInput.value = '';
                        return;
                    }

                    if (file.size > MAX_VID_SIZE) {
                        alert('Saiz fail video (' + (file.size / (1024 * 1024)).toFixed(1) + 'MB) melebihi had maksimum 30MB.');
                        editVideoFileInput.value = '';
                        return;
                    }

                    if (editVideoPreviewBox && editVideoPlayer && editVideoFileName) {
                        editVideoPlayer.src = URL.createObjectURL(file);
                        editVideoFileName.textContent = file.name + ' (' + (file.size / (1024 * 1024)).toFixed(1) + ' MB)';
                        editVideoPreviewBox.style.display = 'block';

                        // Uncheck remove video if user selected new one
                        const removeCheck = document.getElementById('editRemoveVideoCheck');
                        if (removeCheck) removeCheck.checked = false;
                    }
                });
            }

            if (removeEditVideoBtn) {
                removeEditVideoBtn.addEventListener('click', () => {
                    if (editVideoFileInput) editVideoFileInput.value = '';
                    if (editVideoPlayer) editVideoPlayer.src = '';
                    if (editVideoPreviewBox) editVideoPreviewBox.style.display = 'none';
                });
            }

            // Click Handler for Edit Button on each Product Card
            document.querySelectorAll('.btn-edit-product').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    let data = {};
                    try {
                        data = JSON.parse(this.getAttribute('data-product') || '{}');
                    } catch (err) {
                        console.error('Invalid product data JSON', err);
                        return;
                    }

                    if (!data.id) return;

                    if (editForm) editForm.action = data.update_url;

                    const badge = document.getElementById('editProductNoBadge');
                    if (badge) badge.textContent = '#' + (data.product_no || data.id);

                    const nameInput = document.getElementById('editNameInput');
                    if (nameInput) nameInput.value = data.name || '';

                    const costInput = document.getElementById('editCostPriceInput');
                    if (costInput) costInput.value = (data.cost !== undefined && data.cost > 0) ? Number(data.cost).toFixed(2) : '';

                    const priceInput = document.getElementById('editSellingPriceInput');
                    if (priceInput) priceInput.value = Number(data.selling_price || 0).toFixed(2);

                    if (editStockQtyInput) editStockQtyInput.value = data.stock_quantity || 0;
                    if (editStockAddedInput) editStockAddedInput.value = 0;
                    recalculateEditStock();

                    const areaSelect = document.getElementById('editAreaSelect');
                    if (areaSelect) areaSelect.value = data.coverage_area || '';

                    const statusSelect = document.getElementById('editStatusSelect');
                    if (statusSelect) statusSelect.value = data.status || 'active';

                    const descInput = document.getElementById('editDescriptionInput');
                    if (descInput) descInput.value = data.description || '';

                    // Existing video status preview
                    const currentVideoBox = document.getElementById('editCurrentVideoBox');
                    const currentVideoPlayer = document.getElementById('editCurrentVideoPlayer');
                    const removeVideoCheck = document.getElementById('editRemoveVideoCheck');
                    if (removeVideoCheck) removeVideoCheck.checked = false;

                    if (data.has_video && data.video_url) {
                        if (currentVideoBox && currentVideoPlayer) {
                            currentVideoPlayer.src = data.video_url;
                            currentVideoBox.style.display = 'block';
                        }
                    } else {
                        if (currentVideoBox) currentVideoBox.style.display = 'none';
                        if (currentVideoPlayer) currentVideoPlayer.src = '';
                    }

                    // Reset new video input and preview
                    if (editVideoFileInput) editVideoFileInput.value = '';
                    if (editVideoPlayer) editVideoPlayer.src = '';
                    if (editVideoPreviewBox) editVideoPreviewBox.style.display = 'none';

                    openModalEl(editModal);
                });
            });

            if (closeEditBtn) closeEditBtn.addEventListener('click', () => closeModalEl(editModal));
            if (cancelEditBtn) cancelEditBtn.addEventListener('click', () => closeModalEl(editModal));

            // =========================================================================
            // 3. Delete Product Modal Wiring
            // =========================================================================
            const deleteModal = document.getElementById('deleteProductModal');
            const closeDeleteBtn = document.getElementById('closeDeleteBtn');
            const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
            const deleteForm = document.getElementById('deleteProductForm');
            const deleteProductName = document.getElementById('deleteProductName');

            document.querySelectorAll('.btn-delete-product').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const name = this.getAttribute('data-name');
                    const url = this.getAttribute('data-url');

                    if (deleteForm) deleteForm.action = url;
                    if (deleteProductName) deleteProductName.textContent = '"' + name + '"';

                    openModalEl(deleteModal);
                });
            });

            if (closeDeleteBtn) closeDeleteBtn.addEventListener('click', () => closeModalEl(deleteModal));
            if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', () => closeModalEl(deleteModal));
        });
    </script>
    @endpush

</x-business-layout>
