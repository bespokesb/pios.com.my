<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questionnaire &amp; Feedback - PIOS BizGrowth Engine</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/pios_logo_main.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(155deg, #0D3B36 0%, #0A2E2A 55%, #071F1D 100%);
            color: #0F172A;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 14px;
        }

        .feedback-wrapper {
            width: 100%;
            max-width: 540px;
        }

        .brand-mini-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            color: #FFFFFF;
        }

        .brand-mini-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-mini-left img {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            object-fit: cover;
        }

        .brand-mini-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            line-height: 1.1;
        }

        .brand-mini-sub {
            font-size: 0.72rem;
            color: #E7C46A;
            letter-spacing: 0.04em;
        }

        .back-login-link {
            color: #A7F3D0;
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 600;
        }

        .back-login-link:hover {
            text-decoration: underline;
        }

        .feedback-card {
            background: #FFFFFF;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .card-header-bar {
            background: #F1F5F9;
            border-bottom: 1px solid #CBD5E1;
            padding: 16px 20px;
            text-align: center;
        }

        .card-header-bar h1 {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: #0F172A;
        }

        .info-section {
            background: #E0F2FE;
            padding: 18px 20px;
            border-bottom: 1px solid #BAE6FD;
        }

        .field-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .field-row:last-child {
            margin-bottom: 0;
        }

        @media (max-width: 480px) {
            .field-row {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }

        .field-label {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0F172A;
        }

        .req-star {
            color: #DC2626;
            font-weight: 800;
            margin-left: 2px;
        }

        .text-input {
            width: 100%;
            padding: 9px 12px;
            border: 1.5px solid #94A3B8;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #0F172A;
            background: #FFFFFF;
            outline: none;
            transition: border-color 0.2s;
        }

        .text-input:focus {
            border-color: #0D9488;
        }

        .questions-section {
            background: #F1F5F9;
            padding: 18px 20px;
            border-bottom: 1px solid #CBD5E1;
        }

        .question-block {
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px dashed #CBD5E1;
        }

        .question-block:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .question-text {
            font-size: 0.9rem;
            font-weight: 600;
            color: #0F172A;
            line-height: 1.45;
            margin-bottom: 10px;
        }

        .yes-no-row {
            display: flex;
            justify-content: center;
            gap: 28px;
        }

        .yn-option {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            font-size: 0.86rem;
            font-weight: 700;
            color: #1E293B;
            user-select: none;
        }

        .yn-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .yn-box {
            width: 28px;
            height: 28px;
            border: 2px solid #0F172A;
            border-radius: 6px;
            background: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }

        .yn-option input[type="radio"]:checked + .yn-box {
            background: #0D9488;
            border-color: #0D9488;
            color: #FFFFFF;
        }

        .yn-option input[type="radio"]:checked + .yn-box::after {
            content: '✓';
            font-size: 1rem;
            font-weight: 800;
            color: #FFFFFF;
        }

        .remarks-section {
            background: #E0F2FE;
            padding: 18px 20px 22px;
        }

        .remarks-grid {
            display: grid;
            grid-template-columns: 110px 1fr;
            gap: 12px;
            align-items: start;
        }

        @media (max-width: 480px) {
            .remarks-grid {
                grid-template-columns: 1fr;
                gap: 6px;
            }
        }

        .remarks-label {
            font-size: 0.86rem;
            font-weight: 700;
            color: #0F172A;
            line-height: 1.4;
        }

        .remarks-textarea {
            width: 100%;
            min-height: 82px;
            padding: 9px 12px;
            border: 1.5px solid #94A3B8;
            border-radius: 8px;
            font-size: 0.88rem;
            font-family: inherit;
            color: #0F172A;
            background: #FFFFFF;
            resize: vertical;
            outline: none;
        }

        .remarks-textarea:focus {
            border-color: #0D9488;
        }

        .action-buttons-row {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 16px;
        }

        .btn-slide {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 104px;
            padding: 10px 18px;
            border-radius: 8px;
            background: #38BDF8;
            color: #FFFFFF;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-slide:hover {
            background: #0284C7;
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 112px;
            padding: 10px 20px;
            border-radius: 8px;
            background: #0D9488;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-submit:hover {
            background: #0F766E;
        }

        .alert-success {
            background: #ECFDF5;
            border: 1px solid #6EE7B7;
            color: #065F46;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 14px;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 14px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="feedback-wrapper">
        <div class="brand-mini-header">
            <div class="brand-mini-left">
                <img src="{{ asset('pwa-icon-192x192.png') }}" alt="PIOS">
                <div>
                    <div class="brand-mini-title">pios</div>
                    <div class="brand-mini-sub">BIZGROWTH ENGINE</div>
                </div>
            </div>
            <a href="{{ route('login') }}" class="back-login-link">← Log Masuk</a>
        </div>

        @if (session('success'))
            <div class="alert-success">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-error">
                <ul style="margin: 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('feedback.store') }}" method="POST" class="feedback-card">
            @csrf

            <div class="card-header-bar">
                <h1>Questionnaire &amp; Feedback</h1>
            </div>

            <div class="info-section">
                <div class="field-row">
                    <label class="field-label" for="full_name">Full Name<span class="req-star">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="text-input" value="{{ old('full_name') }}" required placeholder="Nama penuh anda">
                </div>

                <div class="field-row">
                    <label class="field-label" for="hp_no">HP No<span class="req-star">*</span></label>
                    <input type="text" id="hp_no" name="hp_no" class="text-input" value="{{ old('hp_no') }}" required placeholder="Contoh: 0123456789">
                </div>

                <div class="field-row">
                    <label class="field-label" for="partner_id_input">Partner ID</label>
                    <div>
                        <input type="text" id="partner_id_input" name="partner_id_input" class="text-input" value="{{ old('partner_id_input') }}" placeholder="Contoh: PTN000001 (jika ada)">
                    </div>
                </div>
            </div>

            <div class="questions-section">
                <div class="question-block">
                    <div class="question-text">
                        1. Adakah anda mahukan pertumbuhan PENDAPATAN/KEUNTUNGAN dalam bisnes/pekerjaan anda ?
                    </div>
                    <div class="yes-no-row">
                        <label class="yn-option">
                            <span>Yes</span>
                            <input type="radio" name="q1_income_growth" value="yes" {{ old('q1_income_growth') === 'yes' ? 'checked' : '' }} required>
                            <span class="yn-box"></span>
                        </label>
                        <label class="yn-option">
                            <span>No</span>
                            <input type="radio" name="q1_income_growth" value="no" {{ old('q1_income_growth') === 'no' ? 'checked' : '' }} required>
                            <span class="yn-box"></span>
                        </label>
                    </div>
                </div>

                <div class="question-block">
                    <div class="question-text">
                        2. Adakah anda sedang mencari / memikirkan cara terbaik untuk mencapainya ?
                    </div>
                    <div class="yes-no-row">
                        <label class="yn-option">
                            <span>Yes</span>
                            <input type="radio" name="q2_seeking_way" value="yes" {{ old('q2_seeking_way') === 'yes' ? 'checked' : '' }} required>
                            <span class="yn-box"></span>
                        </label>
                        <label class="yn-option">
                            <span>No</span>
                            <input type="radio" name="q2_seeking_way" value="no" {{ old('q2_seeking_way') === 'no' ? 'checked' : '' }} required>
                            <span class="yn-box"></span>
                        </label>
                    </div>
                </div>

                <div class="question-block">
                    <div class="question-text">
                        3. Adakah anda berminat menggunakan ALATAN yang secara BERKESAN &amp; AUTOMATIK boleh tingkatkan pendapatan atau keuntungan anda ?
                    </div>
                    <div class="yes-no-row">
                        <label class="yn-option">
                            <span>Yes</span>
                            <input type="radio" name="q3_interested_tools" value="yes" {{ old('q3_interested_tools') === 'yes' ? 'checked' : '' }} required>
                            <span class="yn-box"></span>
                        </label>
                        <label class="yn-option">
                            <span>No</span>
                            <input type="radio" name="q3_interested_tools" value="no" {{ old('q3_interested_tools') === 'no' ? 'checked' : '' }} required>
                            <span class="yn-box"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="remarks-section">
                <div class="remarks-grid">
                    <label class="remarks-label" for="remarks">
                        Feedback<br>Remarks<br>Comments
                    </label>
                    <textarea id="remarks" name="remarks" class="remarks-textarea" placeholder="Sebarang komen atau maklum balas tambahan...">{{ old('remarks') }}</textarea>
                </div>

                <div class="action-buttons-row">
                    <a href="{{ route('feedback.slide') }}" target="_blank" rel="noopener noreferrer" class="btn-slide">
                        Slide
                    </a>
                    <button type="submit" class="btn-submit">
                        Submit
                    </button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>

