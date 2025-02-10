<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('browser_sockets', function (Blueprint $table) {
            $table->id();
            $table->string('uri');
            $table->boolean('is_currently_active');
            $table->json('params');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_sockets');
    }
};
