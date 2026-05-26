<?php

use App\Models\Category;
use App\Models\Location;
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
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->tinyInteger('order')->default(0);
            $table->text('description');
            $table->foreignIdFor(Category::class)->constrained()
                ->cascadeOnUpdate()->nullOnDelete();
            $table->foreignIdFor(Location::class)->constrained()
                ->cascadeOnUpdate()->nullOnDelete();
            $table->date('date_start')->nullable()->default(null);
            $table->date('date_end')->nullable()->default(null);
            $table->dateTime('published_at')->nullable()->default(null);
            $table->dateTime('archived_at')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};
