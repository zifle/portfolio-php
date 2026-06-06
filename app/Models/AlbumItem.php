<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Support\Str;

/**
 * @property int $album_id
 * @property string $album_item_type
 * @property int $album_item_id
 * @property int $order
 * @property-read mixed $type
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem whereAlbumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem whereAlbumItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem whereAlbumItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem whereOrder($value)
 *
 * @mixin \Eloquent
 */
#[Appends(['type'])]
class AlbumItem extends MorphPivot
{
    public $timestamps = false;

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = explode('\\', $this->album_item_type);

                return Str::slug(array_pop($parts));
            }
        );
    }

    public function toArray(): array
    {
        $arr = parent::toArray();

        if (Auth::guest()) {
            unset($arr['album_item_type']);
            unset($arr['album_item_id']);
            unset($arr['order']);
            unset($arr['album_id']);
        }

        return $arr;
    }
}
