<?php

namespace App\Models;

use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property numeric|null $lat
 * @property numeric|null $lng
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Album> $albums
 * @property-read int|null $albums_count
 *
 * @method static \Database\Factories\LocationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereName($value)
 *
 * @mixin \Eloquent
 */
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    public $timestamps = false;

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public static function getNearby(array $coords): Collection
    {
        if (count($coords) == 0) {
            return collect();
        }

        $maxDistanceKm = 10;
        $earthRadiusKm = 6371;
        [$lat, $lng] = static::getMeanGpsPosition($coords);

        return static::addSelect(DB::raw("( $earthRadiusKm * acos( cos( radians($lat) ) * cos( radians( locations.lat ) )
            * cos( radians(locations.lng) - radians($lng)) + sin(radians($lat))
            * sin( radians(locations.lat)))) AS distance"))
            ->where('distance', '<=', $maxDistanceKm)
            ->orderBy('distance')
            ->get();
    }

    public static function getMeanGpsPosition(array $coords): array
    {
        $lat = [];
        $lng = [];
        foreach ($coords as $coord) {
            $lat[] = $coord[0];
            $lng[] = $coord[1];
        }

        $meanLat = array_sum($lat) / count($lat);
        $meanLng = array_sum($lng) / count($lng);

        return [$meanLat, $meanLng];
    }
}
