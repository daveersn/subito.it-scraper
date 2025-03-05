<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracked_searches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('url');
            $table->string('schedule')->nullable();
            $table->dateTime('last_run_at')->nullable();
            $table->dateTime('last_schedule_run_at')->nullable();
            $table->dateTime('next_scheduled_at')->nullable();
            $table->foreignIdFor(User::class);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_searches');
    }
};
