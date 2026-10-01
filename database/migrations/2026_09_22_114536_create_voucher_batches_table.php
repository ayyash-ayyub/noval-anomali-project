<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mikrotik_id')->constrained()->cascadeOnDelete();
            $table->string('batch_code')->unique();
            $table->string('profile');
            $table->unsignedInteger('quantity');
            $table->string('username_prefix');
            $table->enum('username_method', ['sequential', 'random', 'user_equals_password']);
            $table->unsignedInteger('username_length');
            $table->enum('password_method', ['numeric', 'alphanumeric']);
            $table->unsignedInteger('password_length');
            $table->enum('status', ['PENDING', 'PROCESSING', 'COMPLETED', 'PARTIAL', 'FAILED'])->default('PENDING');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Links to Laravel's own job_batches table, used to drive the
            // batch's PROCESSING -> COMPLETED/PARTIAL/FAILED transition
            // once every chunk job has finished.
            $table->string('job_batch_id')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_batches');
    }
};
