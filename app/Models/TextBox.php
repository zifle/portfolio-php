<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TextBoxFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $description
 * @property int $col_size
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AlbumItem|null $pivot
 * @property-read Collection<int, Album> $albums
 * @property-read int|null $albums_count
 * @property-read mixed $order
 *
 * @method static \Database\Factories\TextBoxFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox whereColSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class TextBox extends Model
{
    /** @use HasFactory<TextBoxFactory> */
    use HasFactory;

    public function albums(): MorphToMany
    {
        return $this->morphToMany(Album::class, 'album_item')
            ->using(AlbumItem::class);
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->pivot?->order
        );
    }
}
