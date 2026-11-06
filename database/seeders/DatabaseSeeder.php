<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@skynusa.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'staff@skynusa.test'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STAFF,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'warehouse@skynusa.test'],
            [
                'name' => 'Warehouse Staff',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STAFF,
                'email_verified_at' => now(),
            ],
        );

        $items = [
            ['TOL-001', 'Claw Hammer', Item::TYPE_TOOL, 'pcs', 8, 3, 'Warehouse A'],
            ['TOL-002', 'Screwdriver Set', Item::TYPE_TOOL, 'set', 2, 3, 'Warehouse A'],
            ['TOL-003', 'Cordless Drill', Item::TYPE_TOOL, 'pcs', 0, 2, 'Warehouse A'],
            ['TOL-004', 'Measuring Tape', Item::TYPE_TOOL, 'pcs', 15, 5, 'Warehouse A'],
            ['TOL-005', 'Safety Gloves', Item::TYPE_TOOL, 'pairs', 40, 10, 'Warehouse B'],
            ['TOL-006', 'Wrench Set', Item::TYPE_TOOL, 'set', 1, 2, 'Warehouse B'],
            ['TOL-007', 'Wheelbarrow', Item::TYPE_TOOL, 'pcs', 0, 1, 'Yard'],
            ['TOL-008', 'Step Ladder', Item::TYPE_TOOL, 'pcs', 4, 2, 'Warehouse B'],
            ['ING-001', 'All-Purpose Flour', Item::TYPE_INGREDIENT, 'kg', 120, 30, 'Kitchen Store'],
            ['ING-002', 'Granulated Sugar', Item::TYPE_INGREDIENT, 'kg', 18, 25, 'Kitchen Store'],
            ['ING-003', 'Cooking Oil', Item::TYPE_INGREDIENT, 'liter', 60, 15, 'Kitchen Store'],
            ['ING-004', 'Fresh Eggs', Item::TYPE_INGREDIENT, 'tray', 0, 10, 'Cold Room'],
            ['ING-005', 'Sea Salt', Item::TYPE_INGREDIENT, 'kg', 35, 10, 'Kitchen Store'],
            ['ING-006', 'Butter', Item::TYPE_INGREDIENT, 'kg', 6, 8, 'Cold Room'],
            ['ING-007', 'Cocoa Powder', Item::TYPE_INGREDIENT, 'kg', 22, 8, 'Kitchen Store'],
            ['ING-008', 'Vanilla Extract', Item::TYPE_INGREDIENT, 'bottle', 9, 4, 'Kitchen Store'],
        ];

        $seededItems = collect($items)->map(function (array $row) use ($admin) {
            [$code, $name, $type, $unit, $stock, $minimumStock, $location] = $row;

            return Item::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'unit' => $unit,
                    'stock' => $stock,
                    'minimum_stock' => $minimumStock,
                    'location' => $location,
                    'description' => null,
                ],
            );
        });

        if (StockMovement::query()->exists()) {
            return;
        }

        $seededItems->each(function (Item $item, int $index) use ($admin): void {
            $outTotal = $item->stock === 0 ? 30 : min($item->stock, random_int(3, 15));
            $initialIn = $item->stock + $outTotal;

            StockMovement::query()->create([
                'item_id' => $item->id,
                'user_id' => $admin->id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $initialIn,
                'note' => 'Initial stock received',
                'moved_at' => Carbon::now()->subDays(25 - ($index % 5))->setTime(9, 30),
            ]);

            $remaining = $outTotal;

            while ($remaining > 0) {
                $quantity = $remaining > 5 ? random_int(1, min(5, $remaining)) : $remaining;

                StockMovement::query()->create([
                    'item_id' => $item->id,
                    'user_id' => $admin->id,
                    'type' => StockMovement::TYPE_OUT,
                    'quantity' => $quantity,
                    'note' => fake()->randomElement([
                        'Issued to production',
                        'Used for daily operation',
                        'Damaged / written off',
                        'Issued to staff',
                    ]),
                    'moved_at' => Carbon::now()
                        ->subDays(random_int(1, 20))
                        ->setTime(random_int(8, 17), random_int(0, 59)),
                ]);

                $remaining -= $quantity;
            }
        });
    }
}
