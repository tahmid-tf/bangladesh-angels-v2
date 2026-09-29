<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique('deal_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_portfolios');
    }
};
