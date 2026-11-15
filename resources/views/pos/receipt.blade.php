<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk #{{ $order->payment->invoice_number ?? $order->order_number }} — Baknus Dresto</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
            color: #000;
            font-size: 12px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .flex { display: flex; justify-content: space-between; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 15px; text-align: center;">
        <button onclick="window.print()" style="padding: 6px 12px; font-weight: bold; cursor: pointer;">Cetak Ulang</button>
        <button onclick="window.close()" style="padding: 6px 12px; cursor: pointer;">Tutup</button>
    </div>

    <div class="text-center">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width: 50px; height: 50px; border-radius: 12px; margin-bottom: 6px; object-fit: contain;">
        <h2 style="margin: 0; font-size: 16px;">BAKNUS DRESTO</h2>
        <p style="margin: 2px 0;">Restaurant & Cafe</p>
        <p style="margin: 2px 0; font-size: 11px;">Jl. Raya Resto No. 88, Jakarta</p>
        <p style="margin: 2px 0; font-size: 10px;">Telp: (021) 888-9999</p>
    </div>

    <div class="divider"></div>

    <div>
        <div class="flex"><span>Invoice:</span><span>{{ $order->payment->invoice_number ?? '-' }}</span></div>
        <div class="flex"><span>Order ID:</span><span>{{ $order->order_number }}</span></div>
        <div class="flex"><span>Tanggal:</span><span>{{ $order->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="flex"><span>Kasir:</span><span>{{ (str_starts_with($order->order_number, 'MOB-') || is_null($order->user_id)) ? 'Mobile Apps' : ($order->user->name ?? 'Alex Morgan') }}</span></div>
        <div class="flex"><span>Tipe/Meja:</span><span>{{ strtoupper($order->order_type) }} {{ $order->table ? '(' . $order->table->table_number . ')' : '' }}</span></div>
        <div class="flex"><span>Pelanggan:</span><span>{{ $order->customer_name }}</span></div>
    </div>

    <div class="divider"></div>

    <div>
        @foreach($order->items as $item)
            <div>
                <span class="bold">{{ $item->product_name }}</span>
                <div class="flex" style="font-size: 11px;">
                    <span>{{ $item->quantity }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                    <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($item->notes)
                    <div style="font-size: 10px; font-style: italic;">* {{ $item->notes }}</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="divider"></div>

    <div>
        <div class="flex"><span>Sub Total:</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></div>
        @if($order->discount_amount > 0)
            <div class="flex"><span>Discount:</span><span>-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span></div>
        @endif
        <div class="flex"><span>Pajak Restoran ({{ $order->tax_rate }}%):</span><span>Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span></div>
        @if($order->service_charge > 0)
            <div class="flex"><span>Service Charge:</span><span>Rp {{ number_format($order->service_charge, 0, ',', '.') }}</span></div>
        @endif
        @if($order->tip_amount > 0)
            <div class="flex"><span>Tip:</span><span>Rp {{ number_format($order->tip_amount, 0, ',', '.') }}</span></div>
        @endif
        <div class="divider"></div>
        <div class="flex bold" style="font-size: 14px;"><span>TOTAL:</span><span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span></div>
        <div class="flex"><span>Bayar ({{ strtoupper($order->payment->payment_method ?? 'CASH') }}):</span><span>Rp {{ number_format($order->payment->amount_paid ?? $order->total_amount, 0, ',', '.') }}</span></div>
        <div class="flex"><span>Kembalian:</span><span>Rp {{ number_format($order->payment->change_due ?? 0, 0, ',', '.') }}</span></div>
    </div>

    <div class="divider"></div>

    <div class="text-center" style="font-size: 11px; margin-top: 10px;">
        <p style="margin: 2px 0;">Terima Kasih Atas Kunjungan Anda!</p>
        <p style="margin: 2px 0; font-size: 9px;">Password WiFi: baknusresto2026</p>
    </div>
</body>
</html>
