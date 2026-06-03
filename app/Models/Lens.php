<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $brand
 * @property string $model
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Image> $images
 * @property-read int|null $images_count
 * @method static \Database\Factories\LensFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens whereModel($value)
 * @mixin \Eloquent
 */
#[Appends(['str'])]
class Lens extends Model
{
    /** @use HasFactory<\Database\Factories\LensFactory> */
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
