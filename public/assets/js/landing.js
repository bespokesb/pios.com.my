/**
 * PIOS BizGrowth Engine — Landing Page Interactions & Dynamic Language Translation
 */

document.addEventListener('DOMContentLoaded', () => {
    // ==========================================================================
    // 1. Dynamic Bilingual Dictionary (English <-> Bahasa Melayu)
    // ==========================================================================
    const translations = {
        en: {
            navLogin: 'Login',
            badgeEngine: 'PIOS BizGrowth Engine',
            heroTitle1: 'Automate Growth.',
            heroTitle2: 'Accelerate Success.',
            heroDesc: 'PIOS connects Partners, Business Owners, and customers to sell premium, organic, and exclusive products — with ownership and eligibility built in.',
            checkPremium: 'Premium',
            checkOrganic: 'Organic',
            checkExclusive: 'Exclusive',
            btnHeroLogin: 'Business Owner Login',
            howItWorksTag: 'HOW PIOS WORKS',
            step1Title: 'PIOS Owner',
            step1Desc: 'Appoints Partners, sets fees, and oversees the platform.',
            step2Title: 'Partner',
            step2Desc: 'Registers Business Owners and tracks their performance.',
            step3Title: 'Business Owner',
            step3Desc: 'Registers customers, sells products, and runs promotions.',
            step4Title: 'Customer / VIP',
            step4Desc: 'Buys the premium products they are eligible for.',
            panelPreTag: 'ONE OPERATING PANEL',
            panelMainTitle: 'Built for partner-led selling',
            feat1Title: 'Partner network',
            feat1Desc: 'A clear hierarchy of Partners and Business Owners, with tiers, registration, and performance in one place.',
            feat2Title: 'Run your business',
            feat2Desc: 'Business Owners register customers, select or propose products, run WhatsApp promotions, and track orders.',
            feat3Title: 'Ownership & fees',
            feat3Desc: 'Customer ownership, monthly fees, and reports enforced across every module.',
            sliderPreTag: 'CATALOG SHOWCASE',
            sliderMainTitle: 'Exclusive Products Driving Real Growth',
            sliderDesc: 'High-demand, organic, and premium products verified and distributed across our partner network.',
            sliderHint: 'Hover over products to pause continuous scrolling • Click cards to order in panel',
            orderBtn: 'Order in Panel',
            footerCopy: '© 2026 PIOS. Secure, role-based access for PIOS Owners, Partners, and Business Owners.',
            footerDocs: 'Documentation',
            footerPrivacy: 'Privacy Policy',
            footerTerms: 'Terms of Service'
        },
        ms: {
            navLogin: 'Log Masuk',
            badgeEngine: 'PIOS BizGrowth Engine',
            heroTitle1: 'Automasi Pertumbuhan.',
            heroTitle2: 'Pacu Kejayaan.',
            heroDesc: 'PIOS menghubungkan Rakan Kongsi (Partner), Business Owner, dan pelanggan untuk menjual produk premium, organik, dan eksklusif — dengan pemilikan dan kelayakan terbina dalam.',
            checkPremium: 'Premium',
            checkOrganic: 'Organik',
            checkExclusive: 'Eksklusif',
            btnHeroLogin: 'Log Masuk Business Owner',
            howItWorksTag: 'BAGAIMANA PIOS BERFUNGSI',
            step1Title: 'PIOS Owner',
            step1Desc: 'Melantik Rakan Kongsi, menetapkan yuran, dan menyelia keseluruhan platform.',
            step2Title: 'Partner (Rakan Kongsi)',
            step2Desc: 'Mendaftar Business Owner dan memantau prestasi jualan mereka.',
            step3Title: 'Business Owner',
            step3Desc: 'Mendaftar pelanggan, menjual produk, dan melancarkan promosi WhatsApp.',
            step4Title: 'Pelanggan / VIP',
            step4Desc: 'Membeli produk premium yang layak mereka perolehi dengan tawaran terbaik.',
            panelPreTag: 'SATU PANEL OPERASI',
            panelMainTitle: 'Dibina khusus untuk jualan diterajui rakan kongsi',
            feat1Title: 'Rangkaian Rakan Kongsi',
            feat1Desc: 'Hierarki jelas antara Partner dan Business Owner, dengan sistem tier, pendaftaran, dan prestasi di satu tempat.',
            feat2Title: 'Urus Perniagaan Anda',
            feat2Desc: 'Business Owner mendaftar pelanggan, memilih produk, menyiarkan promosi WhatsApp, dan menjejak pesanan.',
            feat3Title: 'Pemilikan & Yuran',
            feat3Desc: 'Pemilikan pelanggan eksklusif, yuran bulanan telus, dan laporan terperinci dikuatkuasakan di setiap modul.',
            sliderPreTag: 'PAMERAN KATALOG',
            sliderMainTitle: 'Produk Eksklusif Pemacu Pertumbuhan Sebenar',
            sliderDesc: 'Produk berkualiti tinggi, organik, dan premium yang disahkan serta diedarkan di seluruh rangkaian PIOS.',
            sliderHint: 'Letakkan kursor pada produk untuk menjeda pergerakan • Klik kad untuk membuat pesanan di panel',
            orderBtn: 'Pesan di Panel',
            footerCopy: '© 2026 PIOS. Akses selamat berasaskan peranan untuk PIOS Owner, Partner, dan Business Owner.',
            footerDocs: 'Dokumentasi',
            footerPrivacy: 'Dasar Privasi',
            footerTerms: 'Terma Perkhidmatan'
        }
    };

    let currentLang = 'en';

    function applyLanguage(lang) {
        currentLang = lang;
        const dict = translations[lang] || translations.en;

        document.querySelectorAll('[data-i18n]').forEach((el) => {
            const key = el.getAttribute('data-i18n');
            if (dict[key]) {
                el.textContent = dict[key];
            }
        });

        // Update active state on language buttons
        document.querySelectorAll('.lang-btn').forEach((btn) => {
            if (btn.getAttribute('data-lang') === lang) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Update HTML lang attribute
        document.documentElement.setAttribute('lang', lang);

        try {
            localStorage.setItem('pios_lang', lang);
        } catch (e) { }
    }

    // Attach click events to language buttons
    document.querySelectorAll('.lang-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const selectedLang = btn.getAttribute('data-lang');
            if (selectedLang) {
                applyLanguage(selectedLang);
            }
        });
    });

    // Check saved language
    try {
        const savedLang = localStorage.getItem('pios_lang');
        if (savedLang === 'ms' || savedLang === 'en') {
            applyLanguage(savedLang);
        } else {
            applyLanguage('en');
        }
    } catch (e) {
        applyLanguage('en');
    }

    // ==========================================================================
    // 2. Header Scroll Effect
    // ==========================================================================
    const header = document.getElementById('landingHeader');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 40) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }, { passive: true });

    // ==========================================================================
    // 3. Sliding Product Marquee / Carousel Controls
    // ==========================================================================
    const marqueeTrack = document.getElementById('marqueeTrack');
    const prevBtn = document.getElementById('sliderPrevBtn');
    const nextBtn = document.getElementById('sliderNextBtn');
    const marqueeWrapper = document.getElementById('marqueeWrapper');

    let isManualOffset = false;
    let manualOffset = 0;

    if (prevBtn && nextBtn && marqueeTrack) {
        prevBtn.addEventListener('click', () => {
            shiftSlider(320);
        });

        nextBtn.addEventListener('click', () => {
            shiftSlider(-320);
        });
    }

    function shiftSlider(distance) {
        if (!marqueeTrack) return;

        // Temporarily pause the CSS animation to allow manual shift
        marqueeTrack.style.animationPlayState = 'paused';
        manualOffset += distance;

        // Wrap around limits
        const maxScroll = marqueeTrack.scrollWidth / 2;
        if (manualOffset > 0) manualOffset = -maxScroll + 320;
        if (Math.abs(manualOffset) >= maxScroll) manualOffset = 0;

        marqueeTrack.style.transition = 'transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1)';
        marqueeTrack.style.transform = `translateX(${manualOffset}px)`;

        // Resume continuous marquee after 4 seconds of inactivity
        clearTimeout(marqueeTrack._resumeTimer);
        marqueeTrack._resumeTimer = setTimeout(() => {
            marqueeTrack.style.transition = '';
            marqueeTrack.style.transform = '';
            marqueeTrack.style.animationPlayState = 'running';
            manualOffset = 0;
        }, 4000);
    }

    // Touch swipe support for mobile
    let startX = 0;
    let isTouching = false;

    if (marqueeWrapper) {
        marqueeWrapper.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isTouching = true;
            if (marqueeTrack) marqueeTrack.style.animationPlayState = 'paused';
        }, { passive: true });

        marqueeWrapper.addEventListener('touchend', (e) => {
            if (!isTouching) return;
            const endX = e.changedTouches[0].clientX;
            const diffX = endX - startX;

            if (Math.abs(diffX) > 40) {
                shiftSlider(diffX > 0 ? 280 : -280);
            } else {
                if (marqueeTrack) marqueeTrack.style.animationPlayState = 'running';
            }
            isTouching = false;
        }, { passive: true });
    }
});
