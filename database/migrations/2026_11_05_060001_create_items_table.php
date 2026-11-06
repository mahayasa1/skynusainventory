<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->default('tool')->index();
            $table->string('unit')->default('pcs');
            $table->integer('stock')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['type', 'stock']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
