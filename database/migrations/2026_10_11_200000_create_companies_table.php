<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUlid('company_id')->nullable()->after('nik')->constrained('companies')->nullOnDelete();
            $table->string('company_role', 20)->nullable()->after('company_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignUlid('company_id')->nullable()->after('user_id')->constrained('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('company_role');
        });

        Schema::dropIfExists('companies');
    }
};
