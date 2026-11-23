<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quality assurance: per-item specification versions (checks / certs /
 * temp_max / min_shelf_days) and the checks + documents captured at receiving.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->text('spec')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('item_spec_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot'); // {checks:[{t}], certs:[{n}], temp_max, min_shelf_days}
            $table->char('approved_by', 36)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['item_id', 'version']);
        });

        Schema::create('receipt_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('check_text');
            $table->boolean('ok')->default(true);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('receipt_docs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('doc_name');
            $table->string('photo')->nullable();
            $table->boolean('missing')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_docs');
        Schema::dropIfExists('receipt_checks');
        Schema::dropIfExists('item_spec_versions');
        Schema::dropIfExists('item_specs');
    }
};