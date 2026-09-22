<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'mikrotik_id',
        'username',
        'password',
        'profile',
        'status',
        'router_user_id',
        'limit_uptime',
        'activated_at',
        'expired_at',
        'disabled_at',
        'last_synced_at',
        'sync_error',
    ];

    protected function casts(): array
    {
        return [
            'status' => VoucherStatus::class,
            'activated_at' => 'datetime',
            'expired_at' => 'datetime',
            'disabled_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'batch_id');
    }

    public function mikrotik(): BelongsTo
    {
        return $this->belongsTo(Mikrotik::class);
    }
}
