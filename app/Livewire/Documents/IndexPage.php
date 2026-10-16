<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Rules\IsPdfFile;
use App\Services\Document\DocumentAssignmentService;
use App\Services\Document\DocumentUploadService;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Daftar dokumen + form unggah (full-page Livewire). Unghan PDF menjadi aksi
 * store(); halaman detail (show) beserta alur tanda tangan tetap Blade/controller.
 */
#[Layout('layouts.app')]
class IndexPage extends Component
{
    use WithFileUploads, WithPagination;

    public string $title = '';

    public $document = null;

    /** @var array<int, string> ID penandatangan perusahaan yang dipilih (opsional). */
    public array $signers = [];

    public function store(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'document' => [
                'required',
                'file',
                File::types(['application/pdf'])->max((int) config('documents.max_upload_mb', 20) * 1024),
                new IsPdfFile,
            ],
            'signers' => ['sometimes', 'array'],
            'signers.*' => ['string'],
        ]);

        $user = auth()->user();
        $signerIds = $this->signers;
        $isCompanyUpload = $user->isCompanyOperator()
            && is_array($signerIds)
            && $signerIds !== [];

        $document = app(DocumentUploadService::class)->upload(
            $user,
            $this->document,
            $this->title,
            $isCompanyUpload ? $user->company : null,
        );

        if ($isCompanyUpload) {
            app(DocumentAssignmentService::class)->assignSigners($document, $user, $signerIds);
        }

        session()->flash('status', $isCompanyUpload
            ? 'Dokumen perusahaan diunggah dan ditugaskan ke penandatangan.'
            : 'Dokumen berhasil diunggah.');

        $this->redirect(route('documents.show', $document));
    }

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
            ->latest()
            ->paginate(10);

        $companySigners = $user->isCompanyOperator()
            ? $user->company->signers()->orderBy('name')->get()
            : collect();

        $certUrl = route('certificates.show');
        $header = new HtmlString(
            '<div class="flex items-center justify-between">'
            .'<h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">'.__('Dokumen Saya').'</h2>'
            .'<a href="'.$certUrl.'" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 underline">'.__('Sertifikat Digital').'</a>'
            .'</div>'
        );

        return view('livewire.documents.index-page', compact('documents', 'companySigners'))
            ->layout('layouts.app', ['header' => $header]);
    }
}
