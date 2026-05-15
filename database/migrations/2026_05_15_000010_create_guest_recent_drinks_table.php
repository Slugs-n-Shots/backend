<?php

use App\Models\Drink;
use App\Models\Guest;
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
        Schema::create('guest_recent_drinks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Guest::class)->constrained('guests')->cascadeOnDelete();
            $table->foreignIdFor(Drink::class)->constrained('drinks')->cascadeOnDelete();
            $table->timestamp('last_ordered_at')->nullable();
            $table->unsignedInteger('order_count')->default(0);
            $table->timestamps();

            $table->unique(['guest_id', 'drink_id']);
            $table->index(['guest_id', 'last_ordered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_recent_drinks');
    }
};
