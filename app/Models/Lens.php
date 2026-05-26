<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $brand
 * @property string $model
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Database\Factories\LensFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lens whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Lens extends Model
{
    /** @use HasFactory<\Database\Factories\LensFactory> */
    use HasFactory;
}
