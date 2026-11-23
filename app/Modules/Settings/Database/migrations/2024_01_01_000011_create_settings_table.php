<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value settings (online ordering, scale behaviour, delivery fees...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            ['key' => 'online.open', 'value' => 'true'],
            ['key' => 'online.min_order', 'value' => '300'],
            ['key' => 'online.delivery_fee', 'value' => json_encode(['roushdy' => 30, 'agamy' => 30, 'masr-gedida' => 40, 'maadi' => 40])],
            ['key' => 'online.weight_note', 'value' => 'السعر النهائي بيتحسب بالوزن الفعلي للعلبة'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};