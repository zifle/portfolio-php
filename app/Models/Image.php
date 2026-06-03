<?php

namespace App\Models;

use App\Library\Exif;
use Carbon\CarbonImmutable;
use Database\Factories\ImageFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $path
 * @property CarbonImmutable $date_taken
 * @property array<array-key, mixed> $available_res
 * @property int $max_width
 * @property int $max_height
 * @property string|null $description
 * @property int|null $camera_id
 * @property int|null $lens_id
 * @property int|null $focal_length
 * @property int|null $focal_length_35
 * @property string|null $exposure_time
 * @property numeric|null $exposure_compensation
 * @property numeric|null $aperture
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read \App\Models\AlbumItem|null $pivot
 * @property-read Collection<int, \App\Models\Album> $albums
 * @property-read int|null $albums_count
 * @property-read \App\Models\Camera|null $camera
 * @property-read \App\Models\Lens|null $lens
 * @property-read mixed $order
 * @property-read mixed $paths
 * @method static \Database\Factories\ImageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereAperture($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereAvailableRes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereCameraId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereDateTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereExposureCompensation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereExposureTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereFocalLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereFocalLength35($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereLensId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereMaxHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereMaxWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Image whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Appends(['paths'])]
class Image extends Model
{
    /** @use HasFactory<ImageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'available_res' => 'array',
            'date_taken' => 'datetime',
        ];
    }

    public function albums(): MorphToMany
    {
        return $this->morphToMany(Album::class, 'album_item')
            ->using(AlbumItem::class);
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function lens(): BelongsTo
    {
        return $this->belongsTo(Lens::class);
    }

    protected function paths(): Attribute
    {
        return Attribute::make(
            get: function () {
                $disk = static::getDisk();
                $paths = [];
                foreach ($this->available_res as $w) {
                    $fname = str_replace('{0}', $w, $this->path);
                    $paths[$w] = $disk->url($fname);
                }

                return $paths;
            },
        );
    }

    protected function srcset(): Attribute
    {
        return Attribute::make(
            get: function () {
                $srcset = [];

                foreach ($this->paths as $w => $path) {
                    $srcset[] = "$path {$w}w";
                }

                return $srcset;
            }
        );
    }

    protected function order(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->pivot?->order
        );
    }

    public function setExif(Exif $exif): void
    {
        $camera_brand = Str::trim($exif->get('Make'));
        $camera_model = Str::trim($exif->get('Model'));
        if ($camera_brand && $camera_model) {
            $this->camera()->associate(Camera::firstOrCreate([
                'brand' => $camera_brand,
                'model' => $camera_model,
            ]));
        }

        $lens_brand = Str::trim($exif->get('LensMake'));
        $lens_model = Str::trim($exif->get('LensModel'));
        if ($lens_brand && $lens_model) {
            $this->lens()->associate(Lens::firstOrCreate([
                'brand' => $lens_brand,
                'model' => $lens_model,
            ]));
        }

        $this->date_taken = $exif->dateTaken();

        $this->focal_length = $exif->getFloat('FocalLength');
        $this->focal_length_35 = $exif->getFloat('FocalLengthIn35mmFilm');
        $this->aperture = $exif->getFloat('FNumber');
        $this->exposure_time = $exif->exposureTime();
        $this->exposure_compensation = $exif->getFloat('ExposureBiasValue');
    }

    public static function getImagePath(string $filename, ?string $subFolder = null): string
    {
        $disk = static::getDisk();

        // Strip the file extension from the filename, so we can append size suffix
        $split = explode('.', $filename);
        array_pop($split);
        $filename = implode('.', $split);
        $filename = Str::slug($filename);

        if ($subFolder === null) {
            $subFolder = now()->format('Ymd');
        }
        if ($subFolder && ! Str::endsWith($subFolder, '/')) {
            $subFolder .= '/';
        }

        $disk->makeDirectory($subFolder);

        return $subFolder.$filename.'_{0}w.jpg';
    }

    public static function getDisk(): Filesystem|LocalFilesystemAdapter
    {
        return Storage::disk(config('portfolio.uploads_disk'));
    }
}
