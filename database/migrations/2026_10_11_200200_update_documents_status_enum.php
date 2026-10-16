<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menambahkan nilai enum 'partially_signed' pada kolom documents.status.
     *
     * Skema segar (termasuk SQLite untuk test) sudah benar dari migration
     * pembuatan tabel. Migration ini hanya diperlukan untuk MySQL yang sudah
     * berjalan dengan daftar enum lama.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE documents MODIFY status ENUM('draft','partially_signed','signed','revoked') NOT NULL DEFAULT 'draft'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE documents MODIFY status ENUM('draft','signed','revoked') NOT NULL DEFAULT 'draft'"
        );
    }
};
