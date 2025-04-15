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
        Schema::create('servers', function (Blueprint $table) {
            $table->string('id')->primary(); // Server ID (e.g., de-01)
            $table->string('name'); // Server Name (e.g., DE#1)
            $table->tinyInteger('status')->default(0); // 0=offline, 1=online, 2=maintenance, 3=error
            $table->integer('load')->default(0); // Load percentage
            $table->string('entry_country', 2); // Entry Country code
            $table->string('exit_country', 2); // Exit Country code
            $table->string('domain'); // Server domain
            $table->integer('features')->default(0); // Bitwise features
            $table->tinyInteger('tier')->default(1); // 1=Free, 2=Plus, 3=Pro
            $table->string('city')->nullable(); // Server city
            $table->decimal('lat', 10, 7)->nullable(); // Latitude
            $table->decimal('long', 10, 7)->nullable(); // Longitude
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
