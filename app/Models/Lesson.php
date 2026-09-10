<?php

namespace App\Models;

use App\Enums\WeekParity;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recurring weekly slot in the user's timetable.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $teacher_id
 * @property int $day_of_week 1 = Monday ... 5 = Friday
 * @property int $period 0 ... 8
 * @property string $subject_code
 * @property string|null $subject_name
 * @property string|null $room
 * @property string|null $group_label
 * @property WeekParity $week_parity
 */
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    protected $fillable = [
        'teacher_id', 'day_of_week', 'period', 'subject_code',
        'subject_name', 'room', 'group_label', 'week_parity',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'period' => 'integer',
            'week_parity' => WeekParity::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Teacher, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
