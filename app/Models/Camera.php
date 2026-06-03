<?php

namespace App\Models;

use Database\Factories\CameraFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $brand
 * @property string $model
 * @property-read Collection<int, Image> $images
 * @property-read int|null $images_count
 *
 * @method static \Database\Factories\CameraFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereModel($value)
 *
 * @mixin \Eloquent
 */
#[Appends(['str'])]
class Camera extends Model
{
    /** @use HasFactory<CameraFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['brand', 'model'];

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    protected function str(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->brand.' '.$this->model
        );
    }
}
