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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 32);
            $table->string('middle_name', 32)->nullable();
            $table->string('last_name', 32);
            $table->string('email', 64)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->integer('role_code');
            $table->boolean('active')->default(true);
            // $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        \App\Models\Employee::create([
            'first_name' => 'Slugs',
            'middle_name' => 'Admin',
            'last_name' => 'Shhots',
            'email' => 'zschopper+admin@gmail.com',
            'password' => Config::get('ADMIN_PASSWORD', 'RPS?iou-siztE#R!'),
            'role_code' => \App\Models\Employee::ADMIN,
            'active' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
