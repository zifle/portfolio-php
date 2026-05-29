<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ImageFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $path
 * @property string $date_taken
 * @property string $available_res
 * @property int $max_width
 * @property int $max_height
 * @property string|null $description
 * @property int|null $camera_id
 * @property int|null $lens_id
 * @property int|null $focal_length
 * @property int|null $focal_length_35
 * @property string|null $exposure_time
 * @property numeric|null $exposure_compensation
 * @property numeric|null $aperture
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, \App\Models\Album> $albums
 * @property-read int|null $albums_count
 * @property-read \App\Models\Camera|null $camera
 * @property-read \App\Models\Lens|null $lens
 * @method static \Database\Factories\ImageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereAperture($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereAvailableRes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereCameraId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereDateTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereExposureCompensation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereExposureTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereFocalLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereFocalLength35($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereLensId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereMaxHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereMaxWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Image extends Model
{
    /** @use HasFactory<ImageFactory> */
    use HasFactory;

    public function albums(): MorphToMany
    {
        return $this->morphToMany(Album::class, 'album_item');
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function lens(): BelongsTo
    {
        return $this->belongsTo(Lens::class);
    }

    public static function getImagePath(string $filename, ?string $subFolder = null): string
    {
        $disk = Storage::disk(config('filesystems.uploads_disk'));

        // Strip the file extension from the filename, so we can append size suffix
        $filename = Str::lower($filename);
        $split = explode('.', $filename);
        array_pop($split);
        $filename = implode('.', $split);

        if ($subFolder === null) {
            $subFolder = now()->format('Ymd');
        }
        if ($subFolder && ! Str::endsWith($subFolder, '/')) {
            $subFolder .= '/';
        }

        $disk->makeDirectory($subFolder);
        return $subFolder.$filename.'_{0}w.jpg';
    }
}
