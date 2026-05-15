<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $usesSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        if ($usesSqlite) {
            Schema::disableForeignKeyConstraints();
        }

        Schema::table('orders', function (Blueprint $table) use ($usesSqlite) {
            if (! $usesSqlite) {
                $table->dropForeign(['guest_id']);
            }

            $table->unsignedBigInteger('guest_id')->nullable()->change();

            if (! $usesSqlite) {
                $table->foreign('guest_id')->references('id')->on('guests')->nullOnDelete();
            }
        });

        if ($usesSqlite) {
            Schema::enableForeignKeyConstraints();
        }
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

        if (! $usesSqlite) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['guest_id']);
            });
        }

        if (! DB::table('orders')->whereNull('guest_id')->exists()) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('guest_id')->nullable(false)->change();
            });
        }

        if (! $usesSqlite) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('guest_id')->references('id')->on('guests');
            });
        }

        if ($usesSqlite) {
            Schema::enableForeignKeyConstraints();
        }
    }
};
