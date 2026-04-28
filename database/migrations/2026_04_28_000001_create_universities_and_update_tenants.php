<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('acronym', 20)->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('university_id')
                ->nullable()
                ->after('phone')
                ->constrained('universities')
                ->nullOnDelete();
            $table->dropColumn('university');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['university_id']);
            $table->dropColumn('university_id');
            $table->string('university')->nullable()->after('phone');
        });

        Schema::dropIfExists('universities');
    }
};
