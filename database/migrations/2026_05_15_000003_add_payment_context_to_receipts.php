<?php

use App\Models\PaymentAttempt;
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
        Schema::table('receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('paid_for')->nullable()->change();
            $table->foreignIdFor(TableSession::class)->nullable()->after('table')->constrained('table_sessions');
            $table->foreignIdFor(PaymentAttempt::class)->nullable()->after('table_session_id')->constrained('payment_attempts');
            $table->uuid('access_guid')->nullable()->after('payment_attempt_id');

            $table->index('table_session_id');
            $table->index('payment_attempt_id');
            $table->unique('access_guid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $usesSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        if ($usesSqlite) {
            Schema::disableForeignKeyConstraints();
        }

        Schema::table('receipts', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['table_session_id']);
                $table->dropForeign(['payment_attempt_id']);
            }

            $table->dropIndex(['table_session_id']);
            $table->dropIndex(['payment_attempt_id']);
            $table->dropUnique(['access_guid']);
            $table->dropColumn(['table_session_id', 'payment_attempt_id', 'access_guid']);
        });

        if ($usesSqlite) {
            Schema::enableForeignKeyConstraints();
        }
    }
};
