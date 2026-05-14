<?php

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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 16)->default('open')->after('table');
            $table->foreignIdFor(TableSession::class)->nullable()->after('status')->constrained('table_sessions');

            $table->index('status');
            $table->index('table_session_id');
            $table->index(['guest_id', 'status']);
            $table->index(['table_session_id', 'status']);
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

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['table_session_id']);
            }

            $table->dropIndex(['status']);
            $table->dropIndex(['table_session_id']);
            $table->dropIndex(['guest_id', 'status']);
            $table->dropIndex(['table_session_id', 'status']);
            $table->dropColumn(['status', 'table_session_id']);
        });

        if ($usesSqlite) {
            Schema::enableForeignKeyConstraints();
        }
    }
};
