<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Official Subscription Receipt — {{ $payment->receipt_no }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1e293b; margin: 0; background: #f1f5f9; padding: 24px 16px; }
        .print-bar { max-width: 760px; margin: 0 auto 16px auto; display: flex; justify-content: space-between; align-items: center; }
        .back-link { color: #0d9488; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .print-btn { background: #0d9488; color: #fff; border: 0; border-radius: 8px; padding: 8px 18px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .print-btn:hover { background: #0f766e; }
        .sheet { max-width: 760px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 14px; box-shadow: 0 4px 16px rgba(0,0,0,.06); position: relative; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; border-bottom: 2px solid #0d9488; padding-bottom: 20px; }
        .brand-col { display: flex; flex-direction: column; gap: 6px; }
        .brand-logo { height: 42px; width: auto; object-fit: contain; }
        .brand-name { font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 4px; }
        .brand-sub { font-size: 12px; color: #64748b; }
        .receipt-badge { text-align: right; }
        .receipt-title { font-size: 24px; font-weight: 800; color: #0d9488; letter-spacing: .02em; margin: 0; text-transform: uppercase; }
        .receipt-no { font-size: 15px; font-weight: 700; color: #334155; margin-top: 4px; }
        .paid-stamp { display: inline-block; margin-top: 8px; border: 2px solid #059669; color: #059669; background: #ecfdf5; border-radius: 6px; padding: 3px 12px; font-weight: 800; letter-spacing: .08em; font-size: 12px; text-transform: uppercase; }
        .grid-info { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-top: 24px; padding: 18px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; }
        .info-block h4 { margin: 0 0 6px 0; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }
        .info-block p { margin: 0 0 3px 0; font-size: 14px; color: #1e293b; }
        .info-block p strong { color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-top: 26px; }
        th, td { text-align: left; padding: 12px 10px; font-size: 14px; }
        thead th { border-bottom: 2px solid #cbd5e1; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; }
        tbody td { border-bottom: 1px solid #f1f5f9; }
        td.num, th.num { text-align: right; }
        .totals-box { margin-top: 20px; margin-left: auto; width: 280px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 10px; font-size: 14px; color: #475569; }
        .grand-total { border-top: 2px solid #0d9488; padding-top: 10px; margin-top: 4px; font-weight: 800; font-size: 18px; color: #0d9488; }
        .verification-box { margin-top: 30px; padding: 16px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #fafafa; font-size: 12px; color: #64748b; line-height: 1.5; }
        .verification-box strong { color: #334155; }
        .footer-note { margin-top: 28px; text-align: center; color: #94a3b8; font-size: 11px; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; margin: 0; border-radius: 0; max-width: none; padding: 24px; }
            .print-bar { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <a href="javascript:history.back()" class="back-link">
            &larr; Back
        </a>
        <button type="button" onclick="window.print()" class="print-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Print Receipt
        </button>
    </div>

    <div class="sheet">
        <header class="header">
            <div class="brand-col">
                <img src="{{ asset('assets/images/desktop_center_logo.png') }}" alt="PIOS BizGrowth Engine" class="brand-logo" onerror="this.src='{{ asset('favicon-192x192.png') }}'">
                <div class="brand-name">Pak Itam Online Supermarket Sdn Bhd</div>
                <div class="brand-sub">Digital Business &amp; Back-Office Platform (PIOS)</div>
            </div>
            <div class="receipt-badge">
                <h1 class="receipt-title">Official Receipt</h1>
                <div class="receipt-no">{{ $payment->receipt_no }}</div>
                <div class="paid-stamp">APPROVED &amp; VERIFIED</div>
            </div>
        </header>

        <section class="grid-info">
            <div class="info-block">
                <h4>Payer Information</h4>
                <p><strong>{{ $payment->payerName() }}</strong></p>
                <p>{{ $payment->payer_role === 'partner' ? 'PIOS Partner' : 'Business Owner' }}</p>
                <p>Reference ID: <strong>{{ $payment->payerIdentifier() }}</strong></p>
                @if($payment->payerHpNo())
                    <p>Phone: {{ $payment->payerHpNo() }}</p>
                @endif
                <p>Email: {{ $payment->user?->email ?: '-' }}</p>
            </div>
            <div class="info-block">
                <h4>Transaction Information</h4>
                <p>Payment No: <strong>{{ $payment->payment_no }}</strong></p>
                <p>Payment Date: {{ $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : $payment->created_at->format('d M Y, h:i A') }}</p>
                <p>Verification Date: <strong>{{ $payment->approved_at ? $payment->approved_at->format('d M Y, h:i A') : '-' }}</strong></p>
                <p>Payment Method: 
                    <strong>
                        @if($payment->payment_method === 'qr_code')
                            QR Payment / DuitNow
                        @elseif($payment->payment_method === 'cash')
                            Cash
                        @else
                            Bank Transfer (Online)
                        @endif
                    </strong>
                </p>
                @if($payment->reference_no)
                    <p>Transaction Reference No: {{ $payment->reference_no }}</p>
                @endif
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Description / Product Plan</th>
                    <th style="width: 120px;">Duration</th>
                    <th class="num" style="width: 140px;">Amount (MYR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        <strong>{{ $product->name }}</strong>
                        @if($product->code === 'bge')
                            <div style="font-size: 12px; color: #0d9488; margin-top: 3px;">&bull; Core BizGrowth Engine (BGE) Subscription</div>
                        @endif
                        @if($subscription && $subscription->expires_at)
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Active Validity Until: <strong>{{ $subscription->expires_at->format('d M Y') }}</strong></div>
                        @endif
                    </td>
                    <td>{{ $payment->period_days }} Days</td>
                    <td class="num">RM {{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals-box">
            <div class="totals-row">
                <span>Subtotal:</span>
                <span>RM {{ number_format((float) $payment->amount, 2) }}</span>
            </div>
            <div class="totals-row">
                <span>Tax / Processing Fee:</span>
                <span>RM 0.00</span>
            </div>
            <div class="totals-row grand-total">
                <span>TOTAL PAID:</span>
                <span>RM {{ number_format((float) $payment->amount, 2) }}</span>
            </div>
        </div>

        <div class="verification-box">
            <div><strong>Transaction Verification:</strong></div>
            <div>Verified By: <strong>{{ $payment->approver?->name ?: 'PIOS Partner / Management' }}</strong> on {{ $payment->approved_at ? $payment->approved_at->format('d/m/Y h:i A') : '-' }}.</div>
            <div style="margin-top: 4px;">This is a computer-generated official receipt issued by PIOS BizGrowth Engine. No physical signature is required.</div>
        </div>

        <div class="footer-note">
            &copy; {{ date('Y') }} Pak Itam Online Supermarket Sdn Bhd (PIOS). All Rights Reserved.
        </div>
    </div>
</body>
</html>
