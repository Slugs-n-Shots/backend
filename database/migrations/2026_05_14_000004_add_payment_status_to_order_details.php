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
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('payment_status', 16)->default('pending')->after('receipt_id');

            $table->index('payment_status');
            $table->index(['order_id', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['order_id', 'payment_status']);
            $table->dropColumn('payment_status');
        });
    }
};
