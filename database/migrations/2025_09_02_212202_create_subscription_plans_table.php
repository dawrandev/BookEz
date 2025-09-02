<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Стандарт", "Премиум", "VIP"
            $table->unsignedBigInteger('price'); // oylik narx (UZS)
            $table->json('features')->nullable(); // plan imkoniyatlari
            $table->boolean('is_active')->default(true); // plan faolmi
            $table->boolean('is_default')->default(false); // standart planmi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
