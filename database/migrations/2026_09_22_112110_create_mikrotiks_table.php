<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotiks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host');
            $table->unsignedInteger('port');
            $table->string('username');
            $table->text('password_encrypted');
            $table->enum('api_type', ['api', 'rest'])->default('api');
            $table->boolean('ssl_enabled')->default(false);
            $table->enum('status', ['ONLINE', 'DEGRADED', 'OFFLINE', 'UNKNOWN'])->default('UNKNOWN');
            $table->text('description')->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->timestamp('last_online_at')->nullable();
            $table->timestamp('last_offline_at')->nullable();
            $table->unsignedInteger('last_response_time')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['host', 'port']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotiks');
    }
};
