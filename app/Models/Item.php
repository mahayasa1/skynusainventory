<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string $unit
 * @property int $stock
 * @property int $minimum_stock
 * @property string|null $location
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'code',
    'name',
    'type',
    'unit',
    'stock',
    'minimum_stock',
    'location',
    'description',
])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    public const TYPE_TOOL = 'tool';

    public const TYPE_INGREDIENT = 'ingredient';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'minimum_stock' => 'integer',
        ];
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->minimum_stock;
    }

    public function status(): string
    {
        if ($this->isOutOfStock()) {
            return 'out';
        }

        if ($this->isLowStock()) {
            return 'low';
        }

        return 'available';
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'out' => 'Out of stock',
            'low' => 'Low stock',
            default => 'Available',
        };
    }

    /**
     * @param  Builder<Item>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<Item>  $query
     */
    public function scopeOfType(Builder $query, ?string $type): void
    {
        if (! in_array($type, [self::TYPE_TOOL, self::TYPE_INGREDIENT], true)) {
            return;
        }

        $query->where('type', $type);
    }

    /**
     * @param  Builder<Item>  $query
     */
    public function scopeWithStatus(Builder $query, ?string $status): void
    {
        match ($status) {
            'out' => $query->where('stock', '<=', 0),
            'low' => $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'minimum_stock'),
            'available' => $query->where(function (Builder $query) {
                $query->where('stock', '>', 0)
                    ->whereColumn('stock', '>', 'minimum_stock');
            }),
            default => null,
        };
    }

    /**
     * @param  Builder<Item>  $query
     */
    public function scopeSortBy(Builder $query, ?string $sort, ?string $direction): void
    {
        $allowed = ['name', 'code', 'stock', 'minimum_stock', 'type', 'created_at'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'name';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $direction);
    }
}
