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
        Schema::table('guests', function (Blueprint $table) {
            $table->timestamp('anonymized_at')->nullable()->after('deleted_at');
            $table->string('anonymization_reason', 64)->nullable()->after('anonymized_at');

            $table->index('anonymized_at');
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

        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex(['anonymized_at']);
            $table->dropColumn(['anonymized_at', 'anonymization_reason']);
        });

        if ($usesSqlite) {
            Schema::enableForeignKeyConstraints();
        }
    }
};
