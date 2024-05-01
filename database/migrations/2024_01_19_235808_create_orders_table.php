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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_id');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->datetime('recorded_at')->nullable();
            $table->unsignedBigInteger('made_by')->nullable();
            $table->datetime('made_at')->nullable();
            $table->unsignedBigInteger('served_by')->nullable();
            $table->datetime('served_at')->nullable();
            $table->string('table', 36)->nullable();
            $table->timestamps();

            $table->foreign('guest_id')->references('id')->on('guests');
            $table->foreign('recorded_by')->references('id')->on('employees');
            $table->foreign('made_by')->references('id')->on('employees');
            $table->foreign('served_by')->references('id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
