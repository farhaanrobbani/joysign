<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('document_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('signer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['document_id', 'status']);
            $table->index(['signer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_assignments');
    }
};
