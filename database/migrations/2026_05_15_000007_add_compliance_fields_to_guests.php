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
            $table->boolean('is_over_18')->default(false)->after('active');
            $table->timestamp('age_verified_at')->nullable()->after('is_over_18');
            $table->date('birth_date')->nullable()->after('age_verified_at');
            $table->string('phone', 32)->nullable()->after('birth_date');
            $table->string('address')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn([
                'is_over_18',
                'age_verified_at',
                'birth_date',
                'phone',
                'address',
            ]);
        });

        Schema::enableForeignKeyConstraints();
    }
};
