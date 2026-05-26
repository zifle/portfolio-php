<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AlbumFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int $order
 * @property string $description
 * @property int $category_id
 * @property int $location_id
 * @property string|null $date_start
 * @property string|null $date_end
 * @property string|null $published_at
 * @property string|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, \App\Models\Image> $images
 * @property-read int|null $images_count
 * @property-read Collection<int, \App\Models\TextBox> $text_boxes
 * @property-read int|null $text_boxes_count
 * @method static \Database\Factories\AlbumFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereDateEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereDateStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Album whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Album extends Model
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory;

    public function images(): MorphToMany
    {
        return $this->morphToMany(Image::class, 'album_item');
    }

    public function text_boxes(): MorphToMany
    {
        return $this->morphToMany(TextBox::class, 'album_item');
    }
}
