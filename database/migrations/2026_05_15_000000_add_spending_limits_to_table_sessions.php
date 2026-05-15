<?php

use App\Models\Employee;
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
            $table->integer('owner_spending_limit')->nullable()->after('status');
            $table->integer('staff_spending_limit_override')->nullable()->after('owner_spending_limit');
            $table->foreignIdFor(Employee::class, 'staff_spending_limit_override_set_by')
                ->nullable()
                ->after('staff_spending_limit_override')
                ->constrained('employees');
            $table->dateTime('staff_spending_limit_override_set_at')
                ->nullable()
                ->after('staff_spending_limit_override_set_by');
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
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['staff_spending_limit_override_set_by']);
            }

            $table->dropColumn([
                'owner_spending_limit',
                'staff_spending_limit_override',
                'staff_spending_limit_override_set_by',
                'staff_spending_limit_override_set_at',
            ]);
        });

        if ($usesSqlite) {
            Schema::enableForeignKeyConstraints();
        }
    }
};
