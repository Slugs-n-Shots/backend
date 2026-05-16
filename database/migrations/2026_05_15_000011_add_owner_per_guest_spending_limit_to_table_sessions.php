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
        Schema::table('table_sessions', function (Blueprint $table) {
            $table->integer('owner_per_guest_spending_limit')
                ->nullable()
                ->after('owner_spending_limit');
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

        Schema::table('table_sessions', function (Blueprint $table) {
            $table->dropColumn('owner_per_guest_spending_limit');
        });

        if ($usesSqlite) {
            Schema::enableForeignKeyConstraints();
        }
    }
};
