<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'site.name' => ['required', 'string', 'max:60'],
            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,svg',
                'max:2048',
            ],
        ];

        foreach (array_keys(config('settings.defaults', [])) as $key) {
            if ($key === 'site.name') {
                continue;
            }

            $rules[$key] = ['nullable', 'string', 'max:4000'];
        }

        return $rules;
    }
}
