<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog module: product families, items (products), barcodes / scale PLU,
 * and per-location / per-channel prices with an append-only price log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->nullable()->constrained('families')->nullOnDelete();
            $table->string('name');
            $table->string('kind', 20)->default('product'); // product|semi|raw|mix|pack
            $table->string('unit', 20)->default('كرتونة');
            $table->string('scale_plu', 32)->nullable();
            $table->json('also_families')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('item_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('location_id', 64);
            $table->string('channel', 10)->default('direct'); // direct|app
            $table->decimal('price', 12, 2);
            $table->timestamps();

            $table->unique(['item_id', 'location_id', 'channel']);
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();
        });

        Schema::create('price_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('old_price', 12, 2)->nullable();
            $table->decimal('new_price', 12, 2);
            $table->char('by', 36)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_log');
        Schema::dropIfExists('prices');
        Schema::dropIfExists('item_barcodes');
        Schema::dropIfExists('items');
        Schema::dropIfExists('families');
    }
};