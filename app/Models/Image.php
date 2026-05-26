<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $path
 * @property string $available_res
 * @property int $max_width
 * @property int $max_height
 * @property string|null $description
 * @property int $camera_id
 * @property int $lens_id
 * @property string|null $date_taken
 * @property int|null $focal_length
 * @property int|null $focal_length_35
 * @property string|null $exposure_time
 * @property numeric|null $exposure_compensation
 * @property numeric|null $aperture
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Album> $albums
 * @property-read int|null $albums_count
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
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    public function albums(): MorphToMany
    {
        return $this->morphToMany(Album::class, 'album_item');
    }
}
