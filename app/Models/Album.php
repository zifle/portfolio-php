<?php

namespace App\Models;

use Auth;
use Carbon\CarbonImmutable;
use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use CyrildeWit\EloquentViewable\View;
use Database\Factories\AlbumFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int $order
 * @property string|null $description
 * @property int|null $category_id
 * @property int|null $location_id
 * @property string|null $date_start
 * @property string|null $date_end
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Category|null $category
 * @property-read AlbumItem|null $pivot
 * @property-read Collection<int, Image> $images
 * @property-read int|null $images_count
 * @property-read mixed $items
 * @property-read Location|null $location
 * @property-read mixed $meta_image
 * @property-read mixed $published
 * @property-read mixed $short_description
 * @property-read mixed $tags
 * @property-read Collection<int, TextBox> $text_boxes
 * @property-read int|null $text_boxes_count
 * @property-read Collection<int, View> $views
 * @property-read int|null $views_count
 *
 * @method static \Database\Factories\AlbumFactory factory($count = null, $state = [])
 * @method static Builder<static>|Album isArchived()
 * @method static Builder<static>|Album isPublished()
 * @method static Builder<static>|Album newModelQuery()
 * @method static Builder<static>|Album newQuery()
 * @method static Builder<static>|Album orderByUniqueViews(string $direction = 'desc', $period = null, ?string $collection = null, string $as = 'unique_views_count')
 * @method static Builder<static>|Album orderByViews(string $direction = 'desc', ?\CyrildeWit\EloquentViewable\Support\Period $period = null, ?string $collection = null, bool $unique = false, string $as = 'views_count')
 * @method static Builder<static>|Album query()
 * @method static Builder<static>|Album whereArchivedAt($value)
 * @method static Builder<static>|Album whereCategoryId($value)
 * @method static Builder<static>|Album whereCreatedAt($value)
 * @method static Builder<static>|Album whereDateEnd($value)
 * @method static Builder<static>|Album whereDateStart($value)
 * @method static Builder<static>|Album whereDescription($value)
 * @method static Builder<static>|Album whereId($value)
 * @method static Builder<static>|Album whereLocationId($value)
 * @method static Builder<static>|Album whereOrder($value)
 * @method static Builder<static>|Album wherePublishedAt($value)
 * @method static Builder<static>|Album whereSlug($value)
 * @method static Builder<static>|Album whereTitle($value)
 * @method static Builder<static>|Album whereUpdatedAt($value)
 * @method static Builder<static>|Album withViewsCount(?\CyrildeWit\EloquentViewable\Support\Period $period = null, ?string $collection = null, bool $unique = false, string $as = 'views_count')
 *
 * @mixin \Eloquent
 */
#[Appends(['published'])]
class Album extends Model implements Viewable
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory, InteractsWithViews;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function images(): MorphToMany
    {
        return $this->morphedByMany(Image::class, 'album_item')
            ->using(AlbumItem::class)
            ->withPivot(['order']);
    }

    public function text_boxes(): MorphToMany
    {
        return $this->morphedByMany(TextBox::class, 'album_item')
            ->using(AlbumItem::class)
            ->withPivot(['order']);
    }

    protected static function booted(): void
    {
        static::saving(function (Album $album) {
            $album->slug = \Str::slug($album->title);
        });
    }

    #[Scope]
    protected function isPublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    #[Scope]
    protected function isArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    protected function published(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->published_at !== null,
        );
    }

    protected function items(): Attribute
    {
        return Attribute::make(
            get: function () {
                $images = $this->images->append(['order', 'srcset']);
                $texts = $this->text_boxes->append(['order']);

                return collect()
                    ->merge($images)
                    ->merge($texts)
                    ->sortBy('order')
                    ->values(); // Drop the keys
            }
        );
    }

    protected function tags(): Attribute
    {
        return Attribute::make(
            get: function () {
                $tags = [];
                if ($this->category) {
                    $tags[] = $this->category->name;
                }
                if ($this->location) {
                    $tags[] = $this->location->name;
                }
                if ($this->date_start && $this->date_end) {
                    if ($this->date_start == $this->date_end) {
                        $tags[] = 'One-day';
                    } else {
                        $tags[] = 'Multiple days';
                    }
                }

                $images = $this->images->load(['camera', 'lens']);
                $images->pluck('camera')->unique()->filter()
                    ->merge($images->pluck('lens')->unique()->filter())
                    ->each(function (Camera|Lens $camera) use (&$tags) {
                        $tags[] = $camera->brand.' '.$camera->model;
                    });

                return $tags;
            }
        );
    }

    protected function shortDescription(): Attribute
    {
        return Attribute::make(
            get: function () {
                return explode(PHP_EOL.PHP_EOL, $this->description)[0];
            }
        );
    }

    protected function metaImage(): Attribute
    {
        return Attribute::make(
            get: function () {
                foreach ($this->items as $item) {
                    if ($item instanceof Image) {
                        $minWidth = 1200; // Facebook recommends at least 1200px width

                        foreach ($item->paths as $width => $path) {
                            if ($width >= $minWidth) {
                                return $path;
                            }
                        }

                        break;
                    }
                }

                return '';
            }
        );
    }

    public function toArray(): array
    {
        $arr = parent::toArray();
        unset($arr['images']);
        unset($arr['text_boxes']);

        if (Auth::guest()) {
            unset($arr['id']);
            unset($arr['created_at']);
            unset($arr['updated_at']);
            unset($arr['published_at']);
            unset($arr['published']);
        }

        return $arr;
    }
}
