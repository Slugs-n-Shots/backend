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
        Schema::create('drink_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drink_id');
            $table->decimal('quantity', 8, 2);
            $table->string('unit_en')->nullable();
            $table->string('unit_hu')->nullable();
            $table->decimal('unit_price', 8, 2);
            $table->boolean('active')->default(true);
            $table->unique(['drink_id', 'quantity', 'unit_en']);
            $table->timestamps();

            $table->foreign('drink_id')->references('id')->on('drinks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drink_units');
    }
};
