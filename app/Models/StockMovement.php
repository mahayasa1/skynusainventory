<?php

namespace App\Models;

use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $item_id
 * @property int|null $user_id
 * @property string $type
 * @property int $quantity
 * @property string|null $note
 * @property Carbon $moved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['item_id', 'user_id', 'type', 'quantity', 'note', 'moved_at'])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'moved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<StockMovement>  $query
     */
    public function scopeOfType(Builder $query, ?string $type): void
    {
        if (! in_array($type, [self::TYPE_IN, self::TYPE_OUT], true)) {
            return;
        }

        $query->where('type', $type);
    }

    /**
     * @param  Builder<StockMovement>  $query
     */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): void
    {
        if ($from) {
            $query->whereDate('moved_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('moved_at', '<=', $to);
        }
    }

    /**
     * @param  Builder<StockMovement>  $query
     */
    public function scopeSearchItem(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->whereHas('item', function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<StockMovement>  $query
     */
    public function scopeSortBy(Builder $query, ?string $sort, ?string $direction): void
    {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        if ($sort === 'item') {
            $query->orderBy(
                Item::select('name')->whereColumn('items.id', 'stock_movements.item_id'),
                $direction,
            );
        } elseif ($sort === 'quantity') {
            $query->orderBy('quantity', $direction);
        } else {
            $query->orderBy('moved_at', $direction)->orderBy('id', $direction);
        }
    }
}
