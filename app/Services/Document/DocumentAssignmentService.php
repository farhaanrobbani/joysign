<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Enums\AssignmentStatus;
use App\Exceptions\SigningException;
use App\Models\Document;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * Penugasan penandatangan (co-sign paralel): operator menugaskan dokumen
 * perusahaan ke satu atau banyak penandatangan. Setiap penandatangan yang
 * ditugaskan akan menandatangani secara independen; dokumen dianggap signed
 * setelah semua assignment selesai.
 */
final class DocumentAssignmentService
{
    public function __construct(private AuditService $audit) {}

    /**
     * @param  list<string>  $signerIds
     */
    public function assignSigners(Document $document, User $operator, array $signerIds): void
    {
        $signerIds = array_values(array_unique($signerIds));

        if ($signerIds === []) {
            throw SigningException::notAssignedToSign();
        }

        $signers = User::query()
            ->where('company_id', $operator->company_id)
            ->where('role', 'signer')
            ->whereIn('id', $signerIds)
            ->get();

        if ($signers->count() !== count($signerIds)) {
            throw SigningException::notAssignedToSign();
        }

        DB::transaction(function () use ($document, $operator, $signers): void {
            foreach ($signers as $signer) {
                $document->assignments()->create([
                    'signer_id' => $signer->id,
                    'assigned_by' => $operator->id,
                    'status' => AssignmentStatus::Pending,
                ]);
            }

            $this->audit->log($document, $operator, 'document.assigned', [
                'signer_ids' => $signers->pluck('id')->all(),
            ]);
        });
    }
}
