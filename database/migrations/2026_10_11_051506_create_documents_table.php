<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('original_path', 500);
            $table->string('signed_path', 500)->nullable();
            $table->char('original_hash', 64);
            $table->char('signed_hash', 64)->nullable();
            $table->enum('status', ['draft', 'partially_signed', 'signed', 'revoked'])->default('draft');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignUlid('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revoke_reason')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
