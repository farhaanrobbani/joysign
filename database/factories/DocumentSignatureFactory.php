<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentSignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentSignature>
 */
class DocumentSignatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'user_id' => User::factory(),
            'verification_code' => Str::random(64),
            'page_number' => 1,
            'coord_x' => 72.0,
            'coord_y' => 700.0,
            'width' => 140.0,
            'height' => 50.0,
            'cryptographic_signature' => base64_encode(Str::random(256)),
            'signed_at' => now(),
        ];
    }
}
