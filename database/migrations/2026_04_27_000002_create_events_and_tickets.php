<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Eventos ───────────────────────────────────────────────────────────
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('address')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('banner')->nullable();
            $table->enum('status', ['draft', 'active', 'sold_out', 'finished', 'cancelled'])->default('draft');
            $table->integer('minimum_age')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ── Lotes de ingresso ─────────────────────────────────────────────────
        Schema::create('ticket_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->integer('sold')->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('available_from')->nullable();
            $table->dateTime('available_until')->nullable();
            $table->integer('max_per_order')->default(10);
            $table->timestamps();
        });

        // tickets são criados em migration 000003 (após order_items)
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_batches');
        Schema::dropIfExists('events');
    }
};
