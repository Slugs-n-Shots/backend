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
        Schema::create('price_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drink_unit_id');
            $table->datetime('end');
            $table->decimal('unit_price', 8, 2);
            $table->timestamps();

            $table->unique(['drink_unit_id', 'end']);
            $table->foreign('drink_unit_id')->references('id')->on('drink_units');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_logs');
    }
};
