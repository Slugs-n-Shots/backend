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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('serno', 16)->unique();
            $table->unsignedBigInteger('guest_id');
            $table->datetime('issued_at');
            $table->unsignedBigInteger('paid_for');
            $table->datetime('paid_at');
            $table->string('payment_method', 16); // cash or card
            $table->string('table', 36)->nullable();
            $table->timestamps();

            $table->foreign('paid_for')->references('id')->on('employees');
            $table->foreign('guest_id')->references('id')->on('guests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
