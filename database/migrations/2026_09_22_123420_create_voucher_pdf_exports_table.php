<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_pdf_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->nullable()->constrained('voucher_batches')->nullOnDelete();
            $table->json('voucher_ids');
            $table->unsignedInteger('voucher_count');
            $table->enum('paper_size', ['a4', 'a5', 'thermal', 'card'])->default('a4');
            $table->enum('status', ['PENDING', 'PROCESSING', 'READY', 'FAILED'])->default('PENDING');
            $table->string('file_path')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_pdf_exports');
    }
};
