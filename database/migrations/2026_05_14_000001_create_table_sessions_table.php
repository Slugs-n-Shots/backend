<?php

use App\Models\Guest;
use App\Models\Table;
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
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Table::class)->constrained('tables');
            $table->foreignIdFor(Guest::class, 'owner_guest_id')->constrained('guests');
            $table->date('business_date');
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->string('status', 16)->default('open');
            $table->timestamps();

            $table->index(['table_id', 'status']);
            $table->index(['owner_guest_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_sessions');
    }
};
