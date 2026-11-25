{{-- $internal = true muestra precios unitarios, margen y mano de obra (uso del taller). --}}
@php($order = $quote->workOrder)
@php($biz = config('carpinteria.business'))
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Presupuesto {{ $order->code }} v{{ $quote->version }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #292524; }
        h1 { font-size: 18px; color: #6f4520; margin: 0 0 4px; }
        .muted { color: #78716c; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; color: #78716c; border-bottom: 1px solid #d6d3d1; padding: 6px 4px; }
        td { padding: 6px 4px; border-bottom: 1px solid #f5f5f4; vertical-align: top; }
        .r { text-align: right; }
        .totals td { border: 0; padding: 3px 4px; }
        .total td { font-size: 14px; font-weight: bold; border-top: 1px solid #292524; }
        .header td { border: 0; padding: 0; }
        .notes { margin-top: 20px; white-space: pre-line; }
        .internal { background: #fef3c7; padding: 4px 8px; margin-bottom: 8px; }
    </style>
</head>
<body>
    @if ($internal)<div class="internal">Uso interno: incluye precios unitarios, mano de obra y margen.</div>@endif
    <table class="header">
        <tr>
            <td>
                <h1>{{ $biz['name'] }}</h1>
                <div class="muted">
                    {{ $biz['address'] }}<br>
                    {{ collect([$biz['phone'], $biz['email']])->filter()->join(' · ') }}<br>
                    @if ($biz['tax_id'])CUIT {{ $biz['tax_id'] }}@endif
                </div>
            </td>
            <td class="r">
                <strong>Presupuesto</strong><br>
                {{ $order->code }} · versión {{ $quote->version }}<br>
                Fecha: {{ ($quote->sent_at ?? $quote->updated_at)->format('d/m/Y') }}<br>
                @if ($quote->valid_until)Válido hasta: {{ $quote->valid_until->format('d/m/Y') }}@endif
            </td>
        </tr>
    </table>

    <p style="margin-top: 16px;">
        <strong>Cliente:</strong> {{ $order->client->name }}<br>
        <strong>Lugar:</strong> {{ $order->install_address ?: $order->client->locationLabel() }}<br>
        <strong>Trabajo:</strong> {{ $order->title }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Descripción</th><th class="r">Cantidad</th>
                @if ($internal)<th class="r">Precio unit.</th><th class="r">Subtotal</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach ($quote->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="r">{{ qty($item->qty) }} {{ $item->unit }}</td>
                    @if ($internal)<td class="r">{{ money($item->unit_price) }}</td><td class="r">{{ money($item->line_total) }}</td>@endif
                </tr>
            @endforeach
            @if ((float) $quote->labor_cost > 0)
                <tr>
                    <td>Mano de obra, fabricación e instalación</td><td class="r">—</td>
                    @if ($internal)<td class="r"></td><td class="r">{{ money($quote->labor_cost) }}</td>@endif
                </tr>
            @endif
        </tbody>
    </table>

    <table class="totals" style="width: 50%; margin-left: 50%;">
        @if ($internal)
            <tr><td>Materiales + mano de obra</td><td class="r">{{ money($quote->itemsTotal() + (float) $quote->labor_cost) }}</td></tr>
            <tr><td>Margen {{ qty($quote->margin_pct) }}%</td><td class="r">{{ money((float) $quote->subtotal - $quote->itemsTotal() - (float) $quote->labor_cost) }}</td></tr>
        @endif
        <tr><td>Subtotal</td><td class="r">{{ money($quote->subtotal) }}</td></tr>
        <tr><td>IVA {{ qty($quote->tax_pct) }}%</td><td class="r">{{ money($quote->taxAmount()) }}</td></tr>
        <tr class="total"><td>Total</td><td class="r">{{ money($quote->total) }}</td></tr>
    </table>

    @if ($quote->notes)<div class="notes"><strong>Condiciones</strong><br>{{ $quote->notes }}</div>@endif
</body>
</html>
