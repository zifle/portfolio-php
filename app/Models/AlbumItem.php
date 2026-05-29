<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * @property int $album_id
 * @property string $album_item_type
 * @property int $album_item_id
 * @property int $order
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem whereAlbumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem whereAlbumItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem whereAlbumItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AlbumItem whereOrder($value)
 * @mixin \Eloquent
 */
class AlbumItem extends MorphPivot
{
    public $timestamps = false;
}
