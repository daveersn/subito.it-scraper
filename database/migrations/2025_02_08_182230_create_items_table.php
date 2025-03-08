<?php

use App\Models\TrackedSearch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->index();
            $table->foreignIdFor(TrackedSearch::class)->constrained();
            $table->string('title');
            $table->string('town');
            $table->dateTime('uploadedDateTime');
            $table->string('link');
            $table->char('status', 1)->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
