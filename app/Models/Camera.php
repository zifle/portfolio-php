<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $brand
 * @property string $model
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Image> $images
 * @property-read int|null $images_count
 * @method static \Database\Factories\CameraFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereModel($value)
 * @mixin \Eloquent
 */
class Camera extends Model
{
    /** @use HasFactory<\Database\Factories\CameraFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['brand', 'model'];

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }
}
