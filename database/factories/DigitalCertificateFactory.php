<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DigitalCertificate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DigitalCertificate>
 */
class DigitalCertificateFactory extends Factory
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
            'public_key' => implode(PHP_EOL, [
                '-----BEGIN PUBLIC KEY-----',
                Str::random(256),
                '-----END PUBLIC KEY-----',
            ]),
            'encrypted_private_key' => implode(PHP_EOL, [
                '-----BEGIN ENCRYPTED PRIVATE KEY-----',
                Str::random(512),
                '-----END ENCRYPTED PRIVATE KEY-----',
            ]),
            'key_algorithm' => 'RSA-2048',
            'is_active' => true,
            'expires_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
