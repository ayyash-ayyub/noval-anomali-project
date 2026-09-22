<?php

namespace App\Models;

use App\Enums\MikrotikApiType;
use App\Enums\MikrotikStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mikrotik extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password_encrypted',
        'api_type',
        'ssl_enabled',
        'description',
    ];

    /**
     * Never allow the encrypted credential to leak into arrays/JSON
     * (API responses, logs, exception dumps).
     */
    protected $hidden = [
        'password_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'password_encrypted' => 'encrypted',
            'api_type' => MikrotikApiType::class,
            'ssl_enabled' => 'boolean',
            'status' => MikrotikStatus::class,
            'last_check_at' => 'datetime',
            'last_online_at' => 'datetime',
            'last_offline_at' => 'datetime',
            'last_response_time' => 'integer',
        ];
    }

    /**
     * Decrypted plaintext password, for internal Service Layer use only.
     * Never pass this to a view, log line, or array/JSON output.
     */
    public function plainPassword(): string
    {
        return $this->password_encrypted;
    }

    public function voucherBatches(): HasMany
    {
        return $this->hasMany(VoucherBatch::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }
}
