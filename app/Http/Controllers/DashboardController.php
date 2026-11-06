<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the inventory dashboard.
     */
    public function __invoke(Request $request): Response
    {
        $totalStock = (int) Item::query()->sum('stock');
        $totalItems = Item::query()->count();
        $outOfStockItems = Item::query()
            ->where('stock', '<=', 0)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type', 'unit', 'stock', 'minimum_stock']);

        $lowStockItems = Item::query()
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock')
            ->get(['id', 'code', 'name', 'type', 'unit', 'stock', 'minimum_stock']);

        $trend = collect(range(6, 0))->map(function (int $daysAgo): array {
            $date = Carbon::today()->subDays($daysAgo);

            return [
                'date' => $date->toDateString(),
                'label' => $date->format('D'),
                'in' => (int) StockMovement::query()
                    ->where('type', StockMovement::TYPE_IN)
                    ->whereDate('moved_at', $date)
                    ->sum('quantity'),
                'out' => (int) StockMovement::query()
                    ->where('type', StockMovement::TYPE_OUT)
                    ->whereDate('moved_at', $date)
                    ->sum('quantity'),
            ];
        })->all();

        $recentMovements = StockMovement::query()
            ->with(['item:id,name,code,unit', 'user:id,name'])
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (StockMovement $movement): array => [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'note' => $movement->note,
                'moved_at' => $movement->moved_at->toDateTimeString(),
                'item' => $movement->item?->only(['id', 'name', 'code', 'unit']),
                'user' => $movement->user?->only(['id', 'name']),
            ])
            ->all();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_stock' => $totalStock,
                'total_items' => $totalItems,
                'out_of_stock' => $outOfStockItems->count(),
                'low_stock' => $lowStockItems->count(),
                'total_users' => User::query()->count(),
                'admin_users' => User::query()->where('role', User::ROLE_ADMIN)->count(),
                'movements_this_month' => StockMovement::query()
                    ->whereMonth('moved_at', Carbon::now()->month)
                    ->whereYear('moved_at', Carbon::now()->year)
                    ->count(),
            ],
            'trend' => $trend,
            'recentMovements' => $recentMovements,
            'outOfStockItems' => $outOfStockItems,
            'lowStockItems' => $lowStockItems,
        ]);
    }
}
