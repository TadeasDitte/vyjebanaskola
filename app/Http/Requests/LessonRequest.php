<?php

namespace App\Http\Requests;

use App\Enums\WeekParity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class LessonRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:1,5'],
            'period' => ['required', 'integer', 'between:0,8'],
            'subject_code' => ['required', 'string', 'max:20'],
            'subject_name' => ['nullable', 'string', 'max:100'],
            'room' => ['nullable', 'string', 'max:30'],
            'group_label' => ['nullable', 'string', 'max:20'],
            'week_parity' => ['required', new Enum(WeekParity::class)],
            'teacher_id' => [
                'nullable',
                Rule::exists('teachers', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
