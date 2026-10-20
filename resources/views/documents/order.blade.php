@php
    use App\Enums\OrderStatus;

    $isReceipt = ($variant ?? 'invoice') === 'receipt';
    $docTitle = $isReceipt ? __('pios.order.document.receipt') : __('pios.order.document.invoice');

    $sellerName = $seller?->company_name ?: $seller?->applicant_name;
    $sellerAddress = collect([
        $seller?->address, $seller?->address_line_2, $seller?->address_line_3,
        trim(collect([$seller?->poskod, $seller?->bandar])->filter()->implode(' ')),
        $seller?->negeri,
    ])->filter()->implode(', ');

    $customerAddress = collect([
        $customer?->address, $customer?->address_line_2, $customer?->address_line_3,
        trim(collect([$customer?->poskod, $customer?->bandar])->filter()->implode(' ')),
        $customer?->negeri,
    ])->filter()->implode(', ');

    $dateLabel = $isReceipt ? __('pios.order.document.paid_on') : __('pios.order.document.date');
    $dateValue = $isReceipt ? $order->paid_at : $order->ordered_at;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $docTitle }} — {{ $order->order_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color: #1e2733; margin: 0; background: #f3f5f8; }
        .sheet { max-width: 760px; margin: 24px auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; border-bottom: 2px solid #134c49; padding-bottom: 16px; }
        .doc-label { font-size: 26px; font-weight: 800; letter-spacing: .04em; color: #134c49; text-transform: uppercase; }
        .paid-stamp { display: inline-block; margin-top: 6px; border: 2px solid #059669; color: #059669; border-radius: 6px; padding: 2px 10px; font-weight: 800; letter-spacing: .08em; font-size: 13px; }
        .muted { color: #64748b; font-size: 13px; }
        .seller { text-align: right; }
        .seller strong { font-size: 16px; }
        .parties { display: flex; justify-content: space-between; gap: 24px; margin-top: 24px; }
        .parties h4 { margin: 0 0 4px; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
        .meta { text-align: right; }
        .meta div { margin-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { text-align: left; padding: 10px 8px; font-size: 14px; }
        thead th { border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        tbody td { border-bottom: 1px solid #eef2f6; }
        td.num, th.num { text-align: right; }
        .totals { margin-top: 16px; margin-left: auto; width: 260px; }
        .totals .row { display: flex; justify-content: space-between; padding: 6px 8px; }
        .totals .grand { border-top: 2px solid #134c49; font-weight: 800; font-size: 16px; color: #134c49; }
        .tracking { margin-top: 20px; font-size: 13px; }
        .foot { margin-top: 28px; text-align: center; color: #94a3b8; font-size: 12px; }
        .print-bar { max-width: 760px; margin: 0 auto; text-align: right; }
        .print-btn { background: #134c49; color: #fff; border: 0; border-radius: 8px; padding: 8px 16px; font-size: 14px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .sheet { box-shadow: none; margin: 0; border-radius: 0; max-width: none; }
            .print-bar { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-bar"><button class="print-btn" onclick="window.print()">{{ __('pios.order.document.print') }}</button></div>
    <div class="sheet">
        <div class="top">
            <div>
                <div class="doc-label">{{ $docTitle }}</div>
                @if($isReceipt)
                    <span class="paid-stamp">{{ __('pios.order.document.paid_stamp') }}</span>
                @endif
                <div class="muted" style="margin-top:8px;">{{ __('pios.order.field.order_no') }}: <strong>{{ $order->order_no }}</strong></div>
            </div>
            <div class="seller">
                <strong>{{ $sellerName ?: config('app.name') }}</strong>
                @if($seller?->business_owner_no)<div class="muted">{{ $seller->business_owner_no }}</div>@endif
                @if($sellerAddress)<div class="muted">{{ $sellerAddress }}</div>@endif
                @if($seller?->hp_no)<div class="muted">{{ $seller->hp_no }}</div>@endif
            </div>
        </div>

        <div class="parties">
            <div>
                <h4>{{ __('pios.order.document.bill_to') }}</h4>
                <div><strong>{{ $customer?->name ?: '—' }}</strong></div>
                @if($customer?->hp_no)<div class="muted">{{ $customer->hp_no }}</div>@endif
                @if($customerAddress)<div class="muted">{{ $customerAddress }}</div>@endif
            </div>
            <div class="meta">
                <div class="muted">{{ $dateLabel }}: {{ optional($dateValue)->format('d M Y, H:i') ?? '—' }}</div>
                <div class="muted">{{ __('pios.order.field.status') }}: {{ $order->status->label() }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>{{ __('pios.order.field.product') }}</th>
                    <th class="num">{{ __('pios.order.field.quantity') }}</th>
                    <th class="num">{{ __('pios.order.field.unit_price') }}</th>
                    <th class="num">{{ __('pios.order.field.line_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">RM {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="num">RM {{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row"><span class="muted">{{ __('pios.order.document.subtotal') }}</span><span>RM {{ number_format((float) $order->subtotal, 2) }}</span></div>
            <div class="row grand"><span>{{ __('pios.order.field.total') }}</span><span>RM {{ number_format((float) $order->total, 2) }}</span></div>
        </div>

        @if($order->tracking_number)
            <div class="tracking">{{ __('pios.order.field.tracking_number') }}: <strong>{{ $order->tracking_number }}</strong></div>
        @endif

        <div class="foot">{{ __('pios.order.document.footer', ['app' => config('app.name')]) }}</div>
    </div>
</body>
</html>
