<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIOS BizGrowth Engine - Presentation Slide</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/pios_logo_main.png') }}">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0F172A;
            color: #F8FAFC;
            padding: 24px 16px;
        }
        .deck {
            max-width: 860px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-action {
            background: #0D9488;
            color: #FFFFFF;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.88rem;
        }
        .slide-card {
            background: linear-gradient(135deg, #0D3B36 0%, #0A2E2A 100%);
            border: 1px solid rgba(231, 196, 106, 0.35);
            border-radius: 18px;
            padding: 36px 32px;
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.35);
        }
        .slide-num {
            color: #E7C46A;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }
        .slide-card h2 {
            margin: 0 0 12px;
            font-size: 1.5rem;
            color: #FFFFFF;
        }
        .slide-card p, .slide-card li {
            color: #CCFBF1;
            font-size: 0.96rem;
            line-height: 1.65;
        }
        @media print {
            body { background: #FFFFFF; color: #000000; padding: 0; }
            .toolbar { display: none; }
            .slide-card { break-inside: avoid; page-break-after: always; border: 1px solid #CBD5E1; color: #0F172A; background: #FFFFFF; box-shadow: none; }
            .slide-card h2 { color: #0F172A; }
            .slide-card p, .slide-card li { color: #334155; }
        }
    </style>
</head>
<body>
    <div class="deck">
        <div class="toolbar">
            <a href="{{ route('feedback.show') }}" class="btn-action" style="background: #334155;">← Kembali ke Borang Feedback</a>
            <button type="button" onclick="window.print()" class="btn-action">🖨️ Cetak / Simpan PDF</button>
        </div>

        <section class="slide-card">
            <div class="slide-num">Slide 1 &bull; Pengenalan</div>
            <h2>PIOS BizGrowth Engine (BGE)</h2>
            <p>
                Platform automasi perniagaan dan pemasaran bersepadu yang direka khas untuk membantu usahawan dan rakan niaga meningkatkan jualan, mengurus pelanggan, serta menggandakan keuntungan secara sistematik.
            </p>
        </section>

        <section class="slide-card">
            <div class="slide-num">Slide 2 &bull; Cabaran Utama</div>
            <h2>Mengapa Ramai Usahawan Sukar Berkembang?</h2>
            <ul>
                <li>Pangkalan data pelanggan dan prospek tidak tersusun.</li>
                <li>Susulan (follow-up) dan promosi dilakukan secara manual dan memakan masa.</li>
                <li>Tiada alatan automasi WhatsApp untuk menghantar promosi berkala secara konsisten.</li>
            </ul>
        </section>

        <section class="slide-card">
            <div class="slide-num">Slide 3 &bull; Penyelesaian BGE</div>
            <h2>Alatan Automatik Untuk Pertumbuhan Pendapatan</h2>
            <ul>
                <li><strong>Pengurusan Prospek &amp; Pelanggan:</strong> Rekod prospek, pelanggan, dan VIP dalam satu sistem berpusat.</li>
                <li><strong>Katalog &amp; Pautan Produk Pantas:</strong> Kongsi pautan produk terus ke WhatsApp untuk tempahan segera.</li>
                <li><strong>WhatsApp Promotion &amp; Scheduled Blast:</strong> Tetapkan kempen promosi serta tarikh &amp; masa untuk penghantaran automatik.</li>
            </ul>
        </section>
    </div>
</body>
</html>

