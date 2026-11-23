<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Organization & Branches module — physical/logical locations:
 * factory, sections, warehouses, dark stores, shops, partner, donation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->string('name');
            $table->string('type', 20)->index(); // factory|section|warehouse|sub|dark|shop|partner|donation
            $table->string('parent_id', 64)->nullable();
            $table->string('city', 120)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->integer('sort')->default(100);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('locations')->nullOnDelete();
        });

        // Base tree so later migrations can reference 'factory'/'main' etc.
        DB::table('locations')->insert([
            ['id' => 'factory', 'name' => 'المصنع', 'type' => 'factory', 'parent_id' => null, 'city' => 'إسكندرية', 'sort' => 1, 'active' => true],
            ['id' => 'main', 'name' => 'المخزن الرئيسي', 'type' => 'warehouse', 'parent_id' => 'factory', 'city' => 'إسكندرية', 'sort' => 2, 'active' => true],
            ['id' => 'roushdy', 'name' => 'رشدي', 'type' => 'dark', 'parent_id' => null, 'city' => 'إسكندرية', 'sort' => 10, 'active' => true],
            ['id' => 'agamy', 'name' => 'العجمي', 'type' => 'dark', 'parent_id' => null, 'city' => 'إسكندرية', 'sort' => 11, 'active' => true],
            ['id' => 'masr-gedida', 'name' => 'مصر الجديدة', 'type' => 'dark', 'parent_id' => null, 'city' => 'القاهرة', 'sort' => 12, 'active' => true],
            ['id' => 'maadi', 'name' => 'المعادي', 'type' => 'dark', 'parent_id' => null, 'city' => 'القاهرة', 'sort' => 13, 'active' => true],
            ['id' => 'donate', 'name' => 'مخزن التبرعات', 'type' => 'donation', 'parent_id' => 'factory', 'city' => 'إسكندرية', 'sort' => 90, 'active' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};