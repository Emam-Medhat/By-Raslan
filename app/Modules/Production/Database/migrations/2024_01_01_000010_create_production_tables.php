<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Production & Packaging: per-item room routes, production orders that walk
 * the rooms step by step, and packing finished goods into boxes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('ver')->default(1);
            $table->integer('step');
            $table->string('section_id', 64);

            $table->unique(['item_id', 'ver', 'step']);
            $table->foreign('section_id')->references('id')->on('locations')->cascadeOnDelete();
        });

        Schema::create('prod_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no')->unique();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('qty', 12, 3);
            $table->date('due');
            $table->string('status', 12)->default('planned'); // planned|running|done|cancelled
            $table->text('note')->nullable();
            $table->char('created_by', 36)->nullable();
            $table->timestamps();
        });

        Schema::create('prod_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('prod_orders')->cascadeOnDelete();
            $table->integer('step');
            $table->string('section_id', 64);
            $table->string('status', 10)->default('waiting'); // waiting|running|done
            $table->decimal('in_weight', 12, 3)->nullable();
            $table->decimal('out_weight', 12, 3)->nullable();
            $table->decimal('waste', 12, 3)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->char('started_by', 36)->nullable();
            $table->char('ended_by', 36)->nullable();

            $table->unique(['order_id', 'step']);
            $table->foreign('section_id')->references('id')->on('locations')->cascadeOnDelete();
        });

        DB::table('locations')->insert(
            collect([
                ['f-poultry', 'قسم الدواجن', 3],
                ['f-meat', 'قسم اللحوم', 4],
                ['f-marin', 'التتبيل', 5],
                ['f-hot', 'المطبوخ', 6],
                ['f-fill', 'الحشو والتشكيل', 7],
                ['f-wrap', 'التغليف', 8],
            ])->map(fn ($r) => ['id' => $r[0], 'name' => $r[1], 'type' => 'section', 'parent_id' => 'factory', 'city' => 'إسكندرية', 'sort' => $r[2], 'active' => true])->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('prod_steps');
        Schema::dropIfExists('prod_orders');
        Schema::dropIfExists('item_routes');
    }
};