<?php

namespace App\Http\Controllers\Pos;

use App\Enums\SalePaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\SaleRequest;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\Selling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    /**
     * Listado de ventas con lo cobrado por cada medio de pago en el período.
     * Sin filtros, muestra las de hoy.
     */
    public function index(Request $request): View
    {
        $filters = $request->hasAny(['from', 'to', 'method'])
            ? $request->only(['from', 'to', 'method'])
            : ['from' => today()->toDateString(), 'to' => today()->toDateString()];

        $sales = Sale::query()
            ->with('payments')
            ->withSum('items as units', 'quantity')
            ->filter($filters)
            ->latest()
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        // Lo cobrado por medio de pago (el efectivo ya sin el vuelto).
        $byMethod = SalePayment::query()
            ->whereHas('sale', fn ($query) => $query->filter($filters))
            ->selectRaw('method, sum(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        return view('pos.sales.index', [
            'sales' => $sales,
            'filters' => $filters,
            'methods' => SalePaymentMethod::cases(),
            'byMethod' => $byMethod,
            'stats' => [
                'count' => Sale::filter($filters)->count(),
                'total' => (float) Sale::filter($filters)->sum('total'),
            ],
        ]);
    }

    /**
     * Cobra la venta armada en el POS. Responde JSON para que la pantalla no se recargue.
     */
    public function store(SaleRequest $request, Selling $selling): JsonResponse
    {
        $sale = $selling->register(
            array_values($request->validated('items')),
            $request->payments(),
            (float) $request->validated('discount'),
            $request->validated('notes'),
        );

        $sale->load(['payments', 'items.product']);

        return response()->json([
            'number' => $sale->number,
            'total' => (float) $sale->total,
            'change' => $sale->change,
            'url' => route('pos.sales.show', $sale),
            'ticket_url' => route('pos.sales.ticket', $sale),
            // Stock actualizado para que la pantalla quede al día sin recargar.
            'stocks' => $sale->items->mapWithKeys(fn ($item) => [$item->product_id => $item->product->stock]),
        ], 201);
    }

    public function show(Sale $sale): View
    {
        $sale->load(['items.product', 'payments']);

        return view('pos.sales.show', ['sale' => $sale]);
    }

    /**
     * Ticket para imprimir (comprobante no fiscal, impresora de 80 mm o A4).
     */
    public function ticket(Sale $sale): View
    {
        $sale->load(['items.product', 'payments']);

        return view('pos.sales.ticket', ['sale' => $sale]);
    }
}
