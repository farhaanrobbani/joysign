<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_assignments', function (Blueprint $table): void {
            $table->unsignedSmallInteger('position')->default(0)->after('status');
        });

        Schema::table('document_signatures', function (Blueprint $table): void {
            $table->unsignedSmallInteger('position')->default(0)->after('verification_code');
        });

        $positionByDocument = [];
        $nextPosition = [];

        DB::table('document_assignments')
            ->orderBy('document_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->select('id', 'document_id')
            ->chunk(500, function ($rows) use (&$positionByDocument, &$nextPosition): void {
                foreach ($rows as $row) {
                    $index = $nextPosition[$row->document_id] ?? 0;
                    $positionByDocument[$row->id] = $index;
                    $nextPosition[$row->document_id] = $index + 1;
                }
            });

        foreach ($positionByDocument as $assignmentId => $position) {
            DB::table('document_assignments')->where('id', $assignmentId)->update(['position' => $position]);
        }

        $assignmentPosition = [];

        DB::table('document_assignments')
            ->select('document_id', 'signer_id', 'position')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (object $row) use (&$assignmentPosition): void {
                $key = $row->document_id.'-'.$row->signer_id;

                if (! array_key_exists($key, $assignmentPosition)) {
                    $assignmentPosition[$key] = (int) $row->position;
                }
            });

        DB::table('document_signatures')
            ->orderBy('id')
            ->select('id', 'document_id', 'user_id')
            ->chunk(500, function ($rows) use ($assignmentPosition): void {
                foreach ($rows as $row) {
                    $key = $row->document_id.'-'.$row->user_id;
                    $position = $assignmentPosition[$key] ?? 0;

                    DB::table('document_signatures')->where('id', $row->id)->update(['position' => $position]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('document_signatures', function (Blueprint $table): void {
            $table->dropColumn('position');
        });

        Schema::table('document_assignments', function (Blueprint $table): void {
            $table->dropColumn('position');
        });
    }
};
