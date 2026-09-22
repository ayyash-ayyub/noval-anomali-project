<?php

namespace App\Models;

use App\Enums\PasswordGenerationMethod;
use App\Enums\UsernameGenerationMethod;
use App\Enums\VoucherBatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'mikrotik_id',
        'batch_code',
        'profile',
        'quantity',
        'username_prefix',
        'username_method',
        'username_length',
        'password_method',
        'password_length',
        'created_by',
        'status',
        'job_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'username_length' => 'integer',
            'password_length' => 'integer',
            'username_method' => UsernameGenerationMethod::class,
            'password_method' => PasswordGenerationMethod::class,
            'status' => VoucherBatchStatus::class,
        ];
    }

    public function mikrotik(): BelongsTo
    {
        return $this->belongsTo(Mikrotik::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'batch_id');
    }

    /**
     * @return array{total: int, success: int, failed: int, pending: int, percent: int}
     */
    public function progress(): array
    {
        $total = $this->quantity;
        $success = $this->vouchers()->where('status', '!=', 'PENDING')->where('status', '!=', 'FAILED')->count();
        $failed = $this->vouchers()->where('status', 'FAILED')->count();
        $pending = $this->vouchers()->where('status', 'PENDING')->count();

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'pending' => $pending,
            'percent' => $total > 0 ? (int) round((($total - $pending) / $total) * 100) : 0,
        ];
    }
}
