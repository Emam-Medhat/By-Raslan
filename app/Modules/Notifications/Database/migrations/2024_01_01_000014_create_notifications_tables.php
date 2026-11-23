<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications: Laravel database notifications (in-app) + push tokens
 * for the customer mobile app (Expo push).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 36)->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('token')->unique();
            $table->string('platform', 20)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
        Schema::dropIfExists('notifications');
    }
};