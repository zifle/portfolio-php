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
 * @method static \Database\Factories\CameraFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Camera whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Camera extends Model
{
    /** @use HasFactory<\Database\Factories\CameraFactory> */
    use HasFactory;
}
