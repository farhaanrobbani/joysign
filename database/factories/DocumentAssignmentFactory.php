<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssignmentStatus;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAssignment>
 */
class DocumentAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'signer_id' => User::factory(),
            'assigned_by' => null,
            'status' => AssignmentStatus::Pending,
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssignmentStatus::Signed,
            'signed_at' => now(),
        ]);
    }
}
