<?php

use App\Models\Guest;
use App\Models\TableSession;
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
        Schema::create('table_members', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TableSession::class)->constrained('table_sessions');
            $table->foreignIdFor(Guest::class)->constrained('guests');
            $table->string('role', 16)->default('member');
            $table->string('status', 16)->default('pending');
            $table->boolean('can_order')->default(true);
            $table->foreignIdFor(Guest::class, 'approved_by_guest_id')->nullable()->constrained('guests');
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('removed_at')->nullable();
            $table->timestamps();

            $table->unique(['table_session_id', 'guest_id']);
            $table->index(['table_session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_members');
    }
};
