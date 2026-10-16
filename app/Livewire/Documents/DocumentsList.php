<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Models\Document;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentsList extends Component
{
    use WithPagination;

    public function render()
    {
        $user = auth()->user();

        $documents = Document::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->company_id !== null) {
                    $query->orWhere('company_id', $user->company_id);
                }

                $query->orWhereHas('assignments', fn ($q) => $q->where('signer_id', $user->id));
            })
            ->with(['assignments' => fn ($q) => $q->where('signer_id', $user->id)])
            ->latest()
            ->paginate(10);

        return view('livewire.documents.documents-list', compact('documents'));
    }
}
