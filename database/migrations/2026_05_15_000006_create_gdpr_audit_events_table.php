<?php

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
        Schema::create('gdpr_audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 64);
            $table->foreignIdFor(Guest::class)->constrained('guests');
            $table->foreignIdFor(Guest::class, 'actor_guest_id')->nullable()->constrained('guests');
            $table->string('status', 32);
            $table->string('masked_email', 128)->nullable();
            $table->unsignedSmallInteger('blocking_reason_count')->default(0);
            $table->json('blocking_reason_codes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['guest_id', 'created_at']);
            $table->index('actor_guest_id');
            $table->index('event_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gdpr_audit_events');
    }
};
