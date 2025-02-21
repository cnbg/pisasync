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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('citizen_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('school_id')->nullable();
            $table->integer('grade')->nullable();
            $table->string('class_name')->nullable();
            $table->boolean('created')->default(false);
            $table->boolean('pdpa')->default(false);
            $table->json('create_error')->nullable();
            $table->json('pdpa_error')->nullable();
            $table->string('token',500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
