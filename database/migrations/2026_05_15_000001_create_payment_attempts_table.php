<?php

use App\Models\Employee;
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
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Guest::class)->nullable()->constrained('guests');
            $table->foreignIdFor(Employee::class)->nullable()->constrained('employees');
            $table->foreignIdFor(TableSession::class)->nullable()->constrained('table_sessions');
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->string('status', 16)->default('pending');
            $table->string('payment_method', 32);
            $table->integer('amount')->default(0);
            $table->string('currency', 3)->default('HUF');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();

            $table->index(['guest_id', 'status']);
            $table->index(['employee_id', 'status']);
            $table->index(['table_session_id', 'status']);
            $table->index('receipt_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
