<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_signatures', function (Blueprint $table): void {
            $table->string('signer_name')->nullable()->after('user_id');
        });

        // Backfill nama historis penandatangan dari users.name (agar tetap tampil
        // meski akun dihapus/dianonimkan nanti). Subquery korelasi agar kompatibel
        // MariaDB & SQLite.
        DB::statement(
            'UPDATE document_signatures SET signer_name = (SELECT u.name FROM users u WHERE u.id = document_signatures.user_id) WHERE signer_name IS NULL'
        );
    }

    public function down(): void
    {
        Schema::table('document_signatures', function (Blueprint $table): void {
            $table->dropColumn('signer_name');
        });
    }
};
