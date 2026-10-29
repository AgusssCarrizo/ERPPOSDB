{{-- Ticket de venta para imprimir (impresora térmica de 80 mm o cualquier impresora). No es una factura. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $sale->number }}</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        * { box-sizing: border-box; }
        body { margin: 0 auto; width: 72mm; padding: 4mm 0; font: 12px/1.35 ui-monospace, 'Courier New', monospace; color: #000; background: #fff; }
        h1 { margin: 0; font-size: 16px; letter-spacing: .08em; text-align: center; }
        .center { text-align: center; }
        .muted { color: #444; }
        .rule { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .item { margin: 3px 0; }
        .total { font-size: 16px; font-weight: bold; }
        .actions { margin-top: 16px; text-align: center; }
        .actions button { font: inherit; padding: 6px 14px; cursor: pointer; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <h1>DECO BAZAR</h1>
    <p class="center muted">@@decobazar.db · WhatsApp +54 3388 458046</p>

    <hr class="rule">
    <div class="row"><span>Venta {{ $sale->number }}</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
    <hr class="rule">

    @foreach ($sale->items as $item)
        <div class="item">
            <div>{{ $item->product->name }}</div>
            <div class="row muted">
                <span>{{ $item->quantity }} x {{ money($item->unit_price) }}</span>
                <span>{{ money($item->subtotal) }}</span>
            </div>
        </div>
    @endforeach

    <hr class="rule">
    @if ($sale->discount > 0)
        <div class="row"><span>Subtotal</span><span>{{ money($sale->subtotal) }}</span></div>
        <div class="row"><span>Descuento</span><span>- {{ money($sale->discount) }}</span></div>
    @endif
    <div class="row total"><span>TOTAL</span><span>{{ money($sale->total) }}</span></div>
    <hr class="rule">

    @foreach ($sale->payments as $payment)
        <div class="row"><span>{{ $payment->method->label() }}</span><span>{{ money($payment->amount) }}</span></div>
        @if ($payment->tendered !== null)
            <div class="row muted"><span>Pagó con</span><span>{{ money($payment->tendered) }}</span></div>
            <div class="row muted"><span>Vuelto</span><span>{{ money($payment->change) }}</span></div>
        @endif
    @endforeach

    <hr class="rule">
    <p class="center">¡Gracias por tu compra!</p>
    <p class="center muted">Comprobante no válido como factura</p>

    <div class="actions"><button type="button" onclick="window.print()">Imprimir</button></div>

    <script>
        // Desde el POS se abre con ?imprimir=1 para que salga directo el diálogo de impresión.
        if (new URLSearchParams(location.search).has('imprimir')) {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>
