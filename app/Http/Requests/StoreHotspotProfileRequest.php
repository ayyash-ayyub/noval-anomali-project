<?php

namespace App\Http\Requests;

use App\Models\Mikrotik;
use Illuminate\Foundation\Http\FormRequest;

class StoreHotspotProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The ability doesn't vary per-router (Admin-only, same as
        // MikrotikPolicy::create()), so it's checked against the class
        // rather than resolving a specific Mikrotik before validation runs.
        return $this->user()->can('createHotspotProfile', Mikrotik::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mikrotik_id' => ['required', 'integer', 'exists:mikrotiks,id'],
            'name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'address_pool' => ['nullable', 'string', 'max:100'],
            'shared_users' => ['required', 'integer', 'min:1', 'max:9999'],
            'rate_limit' => ['nullable', 'string', 'max:50', 'regex:/^\d+[kKmM]?\/\d+[kKmM]?$/'],
            // RouterOS [wdhm] duration format, e.g. 30d, 6h, 1d02:03:04,
            // 5h30m — same format already read from live profiles by
            // RouterOsDuration::toSeconds() and VoucherBatchService.
            'session_timeout' => ['nullable', 'string', 'max:20', 'regex:/^(\d+[wdhms])+$|^\d{1,3}(:\d{2}){1,2}$/i'],
            'parent_queue' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Nama profile hanya boleh berisi huruf, angka, spasi, strip, dan underscore.',
            'rate_limit.regex' => 'Format Rate Limit tidak valid. Contoh: 512k/1M',
            'session_timeout.regex' => 'Format Session Timeout tidak valid. Contoh: 30d, 6h, 5h30m',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'shared_users' => 'Shared Users',
            'address_pool' => 'Address Pool',
            'rate_limit' => 'Rate Limit',
            'session_timeout' => 'Session Timeout',
            'parent_queue' => 'Parent Queue',
        ];
    }
}
