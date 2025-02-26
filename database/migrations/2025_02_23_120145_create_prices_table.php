<?php

use App\Models\Item;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->integer('value');
            $table->foreignIdFor(Item::class)->constrained('items');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
