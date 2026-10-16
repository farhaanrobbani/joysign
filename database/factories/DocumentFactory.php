<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'original_path' => 'drafts/'.Str::uuid().'.pdf',
            'signed_path' => null,
            'original_hash' => hash('sha256', Str::random(64)),
            'signed_hash' => null,
            'status' => DocumentStatus::Draft,
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'signed_path' => 'signed/'.Str::uuid().'.pdf',
            'signed_hash' => hash('sha256', Str::random(64)),
            'status' => DocumentStatus::Signed,
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Revoked,
            'revoked_at' => now(),
        ]);
    }
}
