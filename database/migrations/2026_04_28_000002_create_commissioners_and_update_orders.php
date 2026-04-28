<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissioners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('commissioner_id')
                ->nullable()->after('user_id')
                ->constrained('commissioners')->nullOnDelete();
            $table->text('customer_cpf')->nullable()->after('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['commissioner_id']);
            $table->dropColumn(['commissioner_id', 'customer_cpf']);
        });
        Schema::dropIfExists('commissioners');
    }
};