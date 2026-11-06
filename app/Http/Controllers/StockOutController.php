<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockOutController extends Controller
{
    /**
     * Display stock-out history and the stock-out form.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
            'sort' => $request->string('sort')->toString() ?: 'moved_at',
            'direction' => $request->string('direction')->toString() ?: 'desc',
        ];

        $query = StockMovement::query()
            ->with(['item:id,name,code,unit', 'user:id,name'])
            ->ofType(StockMovement::TYPE_OUT)
            ->searchItem($filters['search'])
            ->betweenDates($filters['from'], $filters['to']);

        $summary = [
            'total_quantity' => (int) (clone $query)->sum('quantity'),
            'transactions' => (clone $query)->count(),
            'items_issued' => (int) (clone $query)->distinct()->count('item_id'),
        ];

        $movements = $query
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate(12)
            ->withQueryString()
            ->through(fn (StockMovement $movement): array => [
                'id' => $movement->id,
                'quantity' => $movement->quantity,
                'note' => $movement->note,
                'moved_at' => $movement->moved_at->toDateTimeString(),
                'item' => $movement->item?->only(['id', 'name', 'code', 'unit']),
                'user' => $movement->user?->only(['id', 'name']),
            ]);

        return Inertia::render('Stock/Out', [
            'movements' => $movements,
            'items' => Item::query()->orderBy('name')->get(['id', 'name', 'code', 'unit', 'stock']),
            'filters' => $filters,
            'summary' => $summary,
            'defaultDate' => Carbon::today()->toDateString(),
        ]);
    }

    /**
     * Record outgoing stock for an item.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', Rule::exists('items', 'id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'moved_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $item = Item::query()->lockForUpdate()->findOrFail($data['item_id']);

            if ($item->stock < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$item->stock} {$item->unit} of {$item->name} available in stock.",
                ]);
            }

            $item->update(['stock' => $item->stock - $data['quantity']]);

            StockMovement::query()->create([
                'item_id' => $item->id,
                'user_id' => $request->user()?->id,
                'type' => StockMovement::TYPE_OUT,
                'quantity' => $data['quantity'],
                'note' => $data['note'] ?? null,
                'moved_at' => Carbon::parse($data['moved_at']),
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Stock out recorded successfully.']);

        return back();
    }
}
