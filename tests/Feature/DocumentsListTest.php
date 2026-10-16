<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Enums\UserRole;
use App\Livewire\Documents\DocumentsList;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentsListTest extends TestCase
{
    use RefreshDatabase;

    private User $signer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signer = User::factory()->create(['role' => UserRole::Signer]);
    }

    public function test_list_shows_documents_assigned_to_the_user_with_badge(): void
    {
        $document = Document::factory()->create(['title' => 'Kontrak Ditugaskan']);
        DocumentAssignment::factory()->create([
            'document_id' => $document->id,
            'signer_id' => $this->signer->id,
            'status' => AssignmentStatus::Pending,
        ]);

        Livewire::actingAs($this->signer)
            ->test(DocumentsList::class)
            ->assertSee('Kontrak Ditugaskan')
            ->assertSee('Perlu ditandatangani');
    }

    public function test_badge_is_hidden_for_own_documents_and_completed_assignments(): void
    {
        Document::factory()->create([
            'user_id' => $this->signer->id,
            'title' => 'Dokumen Pribadi',
        ]);

        $signed = Document::factory()->create(['title' => 'Sudah Ditandatangani']);
        DocumentAssignment::factory()->signed()->create([
            'document_id' => $signed->id,
            'signer_id' => $this->signer->id,
        ]);

        $html = Livewire::actingAs($this->signer)
            ->test(DocumentsList::class)
            ->html();

        $this->assertStringContainsString('Dokumen Pribadi', $html);
        $this->assertStringContainsString('Sudah Ditandatangani', $html);
        $this->assertStringNotContainsString('Perlu ditandatangani', $html);
    }

    public function test_list_hides_documents_unrelated_to_the_user(): void
    {
        Document::factory()->create(['title' => 'Dokumen Orang Lain']);

        Livewire::actingAs($this->signer)
            ->test(DocumentsList::class)
            ->assertDontSee('Dokumen Orang Lain');
    }

    public function test_list_polls_to_surface_new_assignments_without_refresh(): void
    {
        Livewire::actingAs($this->signer)
            ->test(DocumentsList::class)
            ->assertSeeHtml('wire:poll.5s.visible');
    }
}
