<?php

namespace App\Models;

use Database\Factories\FlairFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $label
 * @property string $color
 * @property int $sentiment
 * @property int $position
 */
class Flair extends Model
{
    /** @use HasFactory<FlairFactory> */
    use HasFactory;

    protected $fillable = ['label', 'color', 'sentiment', 'position'];

    protected function casts(): array
    {
        return [
            'sentiment' => 'integer',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Teacher, $this> */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }
}
