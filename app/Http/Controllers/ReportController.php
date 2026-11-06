<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Stock in / stock out movement report.
     */
    public function movements(Request $request): Response
    {
        $filters = [
            'type' => $request->string('type')->toString(),
            'item_id' => $request->string('item_id')->toString(),
            'search' => $request->string('search')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'moved_at',
            'direction' => $request->string('direction')->toString() ?: 'desc',
        ];

        $query = StockMovement::query()
            ->with(['item:id,name,code,unit', 'user:id,name'])
            ->ofType($filters['type'])
            ->searchItem($filters['search'])
            ->betweenDates($filters['from'], $filters['to']);

        if ($filters['item_id'] !== '') {
            $query->where('item_id', $filters['item_id']);
        }

        $summary = [
            'total_in' => (int) (clone $query)->where('type', StockMovement::TYPE_IN)->sum('quantity'),
            'total_out' => (int) (clone $query)->where('type', StockMovement::TYPE_OUT)->sum('quantity'),
            'transactions' => (clone $query)->count(),
        ];
        $summary['net'] = $summary['total_in'] - $summary['total_out'];

        $movements = $query
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (StockMovement $movement): array => [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'note' => $movement->note,
                'moved_at' => $movement->moved_at->toDateTimeString(),
                'item' => $movement->item?->only(['id', 'name', 'code', 'unit']),
                'user' => $movement->user?->only(['id', 'name']),
            ]);

        return Inertia::render('Reports/Movements', [
            'movements' => $movements,
            'items' => Item::query()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }

    /**
     * Current inventory report.
     */
    public function inventory(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'type' => $request->string('type')->toString(),
            'status' => $request->string('status')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'name',
            'direction' => $request->string('direction')->toString() ?: 'asc',
        ];

        $items = Item::query()
            ->search($filters['search'])
            ->ofType($filters['type'])
            ->withStatus($filters['status'])
            ->withSum(['stockMovements as total_in' => fn ($query) => $query->where('type', StockMovement::TYPE_IN)], 'quantity')
            ->withSum(['stockMovements as total_out' => fn ($query) => $query->where('type', StockMovement::TYPE_OUT)], 'quantity')
            ->withMax('stockMovements as last_movement_at', 'moved_at')
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Item $item): array => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'type' => $item->type,
                'unit' => $item->unit,
                'stock' => $item->stock,
                'minimum_stock' => $item->minimum_stock,
                'location' => $item->location,
                'status' => $item->status(),
                'status_label' => $item->statusLabel(),
                'total_in' => (int) ($item->total_in ?? 0),
                'total_out' => (int) ($item->total_out ?? 0),
                'last_movement_at' => $item->last_movement_at,
            ]);

        return Inertia::render('Reports/Inventory', [
            'items' => $items,
            'filters' => $filters,
            'summary' => [
                'total_items' => Item::query()->count(),
                'total_stock' => (int) Item::query()->sum('stock'),
                'out_of_stock' => Item::query()->where('stock', '<=', 0)->count(),
                'low_stock' => Item::query()
                    ->where('stock', '>', 0)
                    ->whereColumn('stock', '<=', 'minimum_stock')
                    ->count(),
                'total_in' => (int) StockMovement::query()->where('type', StockMovement::TYPE_IN)->sum('quantity'),
                'total_out' => (int) StockMovement::query()->where('type', StockMovement::TYPE_OUT)->sum('quantity'),
            ],
        ]);
    }
}
