<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\IsPdfFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreDocumentRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'document' => [
                'required',
                'file',
                File::types(['application/pdf'])->max((int) config('documents.max_upload_mb', 20) * 1024),
                new IsPdfFile,
            ],
            'signers' => ['sometimes', 'array'],
            'signers.*' => ['string'],
        ];
    }
}
