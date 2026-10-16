<?php

use App\Enums\AssignmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Backfill: setiap dokumen lama (sebelum mesin assignment) diberi satu
     * self-assignment ke pemiliknya, sehingga alur penandatanganan seragam.
     * Dokumen yang sudah punya tanda tangan ditandai signed; selain itu pending.
     */
    public function up(): void
    {
        $documents = DB::table('documents')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('document_assignments')
                    ->whereColumn('document_assignments.document_id', 'documents.id');
            })
            ->get(['id', 'user_id', 'status']);

        foreach ($documents as $document) {
            $hasSignature = DB::table('document_signatures')
                ->where('document_id', $document->id)
                ->exists();

            $signedAt = DB::table('document_signatures')
                ->where('document_id', $document->id)
                ->value('signed_at');

            DB::table('document_assignments')->insert([
                'id' => (string) Str::ulid(),
                'document_id' => $document->id,
                'signer_id' => $document->user_id,
                'assigned_by' => $document->user_id,
                'status' => $hasSignature ? AssignmentStatus::Signed->value : AssignmentStatus::Pending->value,
                'signed_at' => $signedAt,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Backfill tidak dibalikkan (data assignment buatan sendiri).
    }
};
