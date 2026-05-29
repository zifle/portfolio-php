<?php

use App\Models\Album;
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
        Schema::create('album_items', function (Blueprint $table) {
            $table->foreignIdFor(Album::class)
                ->constrained()
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->morphs('album_item');
            $table->unsignedInteger('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('album_items');
    }
};
