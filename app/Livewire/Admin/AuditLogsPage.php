<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Viewer log audit (full-page Livewire): filter event/user/dokumen lewat query
 * string, pencarian instan via wire:model.live, tanpa mutasi (read-only).
 */
#[Layout('layouts.admin')]
class AuditLogsPage extends Component
{
    use WithPagination;

    #[Url]
    public string $event = '';

    #[Url(as: 'user_id')]
    public string $userId = '';

    #[Url(as: 'document_id')]
    public string $documentId = '';

    public function updatingEvent(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function updatingDocumentId(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['event', 'userId', 'documentId']);
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLog::query()->with('user:id,name,email', 'document:id,title');

        if ($this->event !== '') {
            $query->where('event', $this->event);
        }

        if ($this->userId !== '') {
            $query->where('user_id', $this->userId);
        }

        if ($this->documentId !== '') {
            $query->where('document_id', $this->documentId);
        }

        $logs = $query->latest('created_at')->paginate(25)->withQueryString();

        $events = AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event');
        $logUsers = User::query()->select('id', 'name')->orderBy('name')->get();

        return view('livewire.admin.audit-logs-page', compact('logs', 'events', 'logUsers'))
            ->layout('layouts.admin', [
                'header' => new HtmlString(
                    '<h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">'
                    .__('Log Audit').'</h2>'
                ),
            ]);
    }
}
