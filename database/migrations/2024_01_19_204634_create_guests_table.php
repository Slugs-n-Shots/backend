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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 32);
            $table->string('middle_name', 32)->nullable();
            $table->string('last_name', 32);
            $table->string('email', 64)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('table', 36)->nullable();
            $table->boolean('reservee')->nullable();
            $table->string('picture', 64)->nullable();
            $table->boolean('active')->default(true);
            $table->json('data')->default('{}');
            // $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        $guest = \App\Models\Guest::create([
            'first_name' => 'Shots',
            'middle_name' => 'Guest',
            'last_name' => 'Slugs',
            'email' => 'slugs.n.shots+guest@gmail.com',
            'password' => 'slUGz-R0CKS!',
            'active' => true,
        ]);
        $guest->email_verified_at = \Date::now();

        $guest->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
