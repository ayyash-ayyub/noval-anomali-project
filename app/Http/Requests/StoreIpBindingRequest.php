<?php

namespace App\Http\Requests;

use App\Models\Mikrotik;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIpBindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ability doesn't vary per-router (Admin-only), same pattern as
        // StoreHotspotProfileRequest — checked against the class rather
        // than resolving a specific Mikrotik before validation runs.
        return $this->user()->can('createIpBinding', Mikrotik::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mikrotik_id' => ['required', 'integer', 'exists:mikrotiks,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'mac_address' => ['required', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'],
            'type' => ['required', Rule::in(['bypassed', 'blocked', 'regular'])],
            'address' => ['nullable', 'ip'],
            'to_address' => ['nullable', 'ip'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mac_address.regex' => 'Format MAC Address tidak valid. Contoh: AA:BB:CC:00:11:22',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'mac_address' => 'MAC Address',
            'to_address' => 'To Address',
        ];
    }
}
