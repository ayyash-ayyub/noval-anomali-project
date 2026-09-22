<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('voucher_batches')->cascadeOnDelete();
            $table->foreignId('mikrotik_id')->constrained()->cascadeOnDelete();
            $table->string('username');
            $table->string('password');
            $table->string('profile');
            $table->enum('status', ['PENDING', 'SYNCED', 'ACTIVE', 'USED', 'EXPIRED', 'DISABLED', 'FAILED'])->default('PENDING');
            // The router's internal .id for this hotspot user (e.g. "*3A"),
            // captured once the create call succeeds — needed to target
            // update/disable/delete calls without a name lookup.
            $table->string('router_user_id')->nullable();
            $table->string('limit_uptime')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();

            // Prevents duplicate hotspot users on the same router — the
            // core idempotency guarantee required by spec section 7.
            $table->unique(['mikrotik_id', 'username']);

            $table->index('batch_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
