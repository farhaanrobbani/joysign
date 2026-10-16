<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page_number' => ['required', 'integer', 'min:1'],
            'coord_x' => ['required', 'numeric', 'min:0'],
            'coord_y' => ['required', 'numeric', 'min:0'],
            'width' => ['required', 'numeric', 'min:1'],
            'height' => ['required', 'numeric', 'min:1'],
            'passphrase' => ['required', 'string', 'min:8', 'max:1024'],
        ];
    }
}
