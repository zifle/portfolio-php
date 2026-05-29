<?php

use App\Models\Camera;
use App\Models\Lens;
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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('path')->index();
            $table->dateTime('date_taken')->index();
            $table->json('available_res')->default('[]');
            $table->unsignedSmallInteger('max_width');
            $table->unsignedSmallInteger('max_height');
            $table->text('description')
                ->nullable()->default(null);

            $table->foreignIdFor(Camera::class)
                ->nullable()->default(null)
                ->constrained()
                ->cascadeOnUpdate()->nullOnDelete();
            $table->foreignIdFor(Lens::class)
                ->nullable()->default(null)
                ->constrained()
                ->cascadeOnUpdate()->nullOnDelete();

            $table->unsignedSmallInteger('focal_length')
                ->nullable()->default(null);
            $table->unsignedSmallInteger('focal_length_35')
                ->nullable()->default(null);
            $table->string('exposure_time', 10)
                ->nullable()->default(null);
            $table->decimal('exposure_compensation', 4, 2)
                ->nullable()->default(null);
            $table->decimal('aperture', 4, 1)
                ->nullable()->default(null);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
