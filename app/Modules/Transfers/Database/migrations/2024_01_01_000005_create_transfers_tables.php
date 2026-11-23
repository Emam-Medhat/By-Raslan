<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transfers module: scan-based box moves between locations.
 * A transfer = two ledger lines with the same ref (out from source,
 * in at destination) so nothing is double-counted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('no')->unique();
            $table->string('from_loc', 64);
            $table->string('to_loc', 64);
            $table->string('status', 20)->default('transit'); // transit|received
            $table->text('reason')->nullable();
            $table->text('note')->nullable();
            $table->char('sent_by', 36)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->char('recv_by', 36)->nullable();
            $table->timestamp('recv_at')->nullable();
            $table->unsignedBigInteger('trip_id')->nullable();
            $table->timestamps();

            $table->foreign('from_loc')->references('id')->on('locations')->cascadeOnDelete();
            $table->foreign('to_loc')->references('id')->on('locations')->cascadeOnDelete();
        });

        Schema::create('transfer_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('transfers')->cascadeOnDelete();
            $table->unsignedBigInteger('box_sn');
            $table->string('state', 20)->default('sent'); // sent|received|missing|found|lost
            $table->timestamps();

            $table->unique(['transfer_id', 'box_sn']);
            $table->foreign('box_sn')->references('sn')->on('boxes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_boxes');
        Schema::dropIfExists('transfers');
    }
};