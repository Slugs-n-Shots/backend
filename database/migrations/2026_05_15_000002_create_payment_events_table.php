<?php

use App\Models\Employee;
use App\Models\Guest;
use App\Models\OrderDetail;
use App\Models\PaymentAttempt;
use App\Models\Receipt;
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
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PaymentAttempt::class)->constrained('payment_attempts');
            $table->string('event_type', 64);
            $table->foreignIdFor(Guest::class, 'actor_guest_id')->nullable()->constrained('guests');
            $table->foreignIdFor(Employee::class, 'actor_employee_id')->nullable()->constrained('employees');
            $table->foreignIdFor(OrderDetail::class)->nullable()->constrained('order_details');
            $table->foreignIdFor(Receipt::class)->nullable()->constrained('receipts');
            $table->text('audit_xml')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['payment_attempt_id', 'created_at']);
            $table->index('event_type');
            $table->index('actor_guest_id');
            $table->index('actor_employee_id');
            $table->index('receipt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
