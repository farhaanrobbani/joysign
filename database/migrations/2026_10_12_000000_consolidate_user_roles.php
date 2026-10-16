<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Ubah kolom role dari enum menjadi string(20) agar dapat menampung
        //    nilai 'operator'. Validasi nilai dijaga oleh cast PHP UserRole.
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('signer')->change();
        });

        // 2) Backfill: pindahkan peran perusahaan (company_role) ke role tunggal.
        //    operator perusahaan -> role=operator, signer perusahaan -> role=signer.
        //    Admin (super-admin) TIDAK boleh ditimpa menjadi operator/signer.
        //    User tanpa company_role (admin & perorangan) tidak berubah.
        DB::table('users')
            ->whereNotNull('company_role')
            ->where('role', '!=', 'admin')
            ->update(['role' => DB::raw('company_role')]);

        // 3) Hapus kolom company_role (konsolidasi ke satu field role).
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('company_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('company_role', 20)->nullable()->after('company_id');
        });

        // operator -> kembalikan jadi company_role=operator, role=signer.
        DB::table('users')
            ->where('role', 'operator')
            ->update(['company_role' => 'operator', 'role' => 'signer']);

        // signer yang punya perusahaan -> company_role=signer.
        DB::table('users')
            ->whereNotNull('company_id')
            ->where('role', 'signer')
            ->update(['company_role' => 'signer']);

        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['admin', 'signer'])->default('signer')->change();
        });
    }
};
