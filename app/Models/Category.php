<?php

namespace App\Models;

use Auth;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $order
 * @property-read Collection<int, Album> $albums
 * @property-read int|null $albums_count
 *
 * @method static \Database\Factories\CategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereOrder($value)
 *
 * @mixin \Eloquent
 */
class Category extends Model implements Viewable
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, InteractsWithViews;

    public $timestamps = false;

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function toArray(): array
    {
        $arr = parent::toArray();

        if (Auth::guest()) {
            unset($arr['id']);
            unset($arr['order']);
        }

        return $arr;
    }
}
