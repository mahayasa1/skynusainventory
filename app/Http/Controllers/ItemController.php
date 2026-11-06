<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    /**
     * Display a filtered, sortable list of items.
     */
    public function index(Request $request): Response
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
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate(10)
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
                'description' => $item->description,
                'status' => $item->status(),
                'status_label' => $item->statusLabel(),
                'created_at' => $item->created_at?->toDateString(),
            ]);

        return Inertia::render('Items/Index', [
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
            ],
        ]);
    }

    /**
     * Store a newly created item.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['code' => strtoupper((string) $request->input('code'))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('items', 'code')],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in([Item::TYPE_TOOL, Item::TYPE_INGREDIENT])],
            'unit' => ['required', 'string', 'max:20'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'initial_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $initialStock = (int) ($data['initial_stock'] ?? 0);
        unset($data['initial_stock']);

        $data['stock'] = $initialStock;

        DB::transaction(function () use ($data, $initialStock, $request): void {
            $item = Item::query()->create($data);

            if ($initialStock > 0) {
                StockMovement::query()->create([
                    'item_id' => $item->id,
                    'user_id' => $request->user()?->id,
                    'type' => StockMovement::TYPE_IN,
                    'quantity' => $initialStock,
                    'note' => 'Initial stock',
                    'moved_at' => now(),
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item added successfully.']);

        return back();
    }

    /**
     * Update the specified item.
     */
    public function update(Request $request, Item $item): RedirectResponse
    {
        $request->merge(['code' => strtoupper((string) $request->input('code'))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('items', 'code')->ignore($item->id)],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in([Item::TYPE_TOOL, Item::TYPE_INGREDIENT])],
            'unit' => ['required', 'string', 'max:20'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['code'] = strtoupper($data['code']);

        $item->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item updated successfully.']);

        return back();
    }

    /**
     * Remove the specified item.
     */
    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item deleted successfully.']);

        return back();
    }
}
