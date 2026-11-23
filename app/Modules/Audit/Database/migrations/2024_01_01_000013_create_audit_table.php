<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit trail for sensitive actions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->timestamp('at')->nullable();
            $table->char('by', 36)->nullable();
            $table->string('action');
            $table->string('table_name')->nullable();
            $table->string('record_id')->nullable();
            $table->json('old')->nullable();
            $table->json('new')->nullable();
            $table->index(['table_name', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};