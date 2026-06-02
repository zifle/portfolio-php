<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $description
 * @property int $col_size
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\AlbumItem|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Album> $albums
 * @property-read int|null $albums_count
 * @property-read mixed $order
 * @method static \Database\Factories\TextBoxFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox whereColSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TextBox whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TextBox extends Model
{
    /** @use HasFactory<\Database\Factories\TextBoxFactory> */
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
