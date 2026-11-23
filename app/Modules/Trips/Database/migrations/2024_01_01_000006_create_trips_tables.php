<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trips & Loading: trucks carry plastic crates to branches.
 * Loading is recorded in trip_boxes; on departure one transit transfer is
 * created per destination branch (out of the factory).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crates', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->boolean('active')->default(true);
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no')->unique();
            $table->string('truck');
            $table->string('driver')->nullable();
            $table->string('status', 20)->default('loading'); // loading|out|done
            $table->char('created_by', 36)->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_crates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->string('crate_id', 32);
            $table->string('to_loc', 64);
            $table->boolean('closed')->default(false);
            $table->timestamps();

            $table->unique(['trip_id', 'crate_id']);
            $table->foreign('crate_id')->references('id')->on('crates')->cascadeOnDelete();
            $table->foreign('to_loc')->references('id')->on('locations')->cascadeOnDelete();
        });

        Schema::create('trip_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->string('crate_id', 32);
            $table->unsignedBigInteger('box_sn');
            $table->boolean('removed')->default(false);
            $table->timestamp('at')->nullable();
            $table->char('by', 36)->nullable();

            $table->foreign('crate_id')->references('id')->on('crates')->cascadeOnDelete();
            $table->foreign('box_sn')->references('sn')->on('boxes')->cascadeOnDelete();
            $table->index(['trip_id', 'removed']);
        });

        // Seed the standard crate fleet CR-001..CR-040.
        $rows = [];
        for ($i = 1; $i <= 40; $i++) {
            $rows[] = ['id' => sprintf('CR-%03d', $i), 'active' => true];
        }
        DB::table('crates')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_boxes');
        Schema::dropIfExists('trip_crates');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('crates');
    }
};