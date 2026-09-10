<?php

namespace App\Http\Requests;

use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacher = $this->route('teacher');
        $teacherId = $teacher instanceof Teacher ? $teacher->id : null;

        return [
            'short_code' => [
                'required', 'string', 'max:20',
                Rule::unique('teachers', 'short_code')
                    ->where('user_id', $this->user()->id)
                    ->ignore($teacherId),
            ],
            'full_name' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'flair_id' => [
                'nullable',
                Rule::exists('flairs', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
