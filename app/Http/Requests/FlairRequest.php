<?php

namespace App\Http\Requests;

use App\Models\Flair;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FlairRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $flair = $this->route('flair');
        $flairId = $flair instanceof Flair ? $flair->id : null;

        return [
            'label' => [
                'required', 'string', 'max:40',
                Rule::unique('flairs', 'label')
                    ->where('user_id', $this->user()->id)
                    ->ignore($flairId),
            ],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sentiment' => ['required', 'integer', 'between:-1,1'],
        ];
    }
}
