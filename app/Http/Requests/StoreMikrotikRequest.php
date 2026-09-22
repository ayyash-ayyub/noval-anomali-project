<?php

namespace App\Http\Requests;

use App\Models\Mikrotik;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMikrotikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Mikrotik::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'host' => ['required', 'string', 'max:255'],
            'port' => [
                'required',
                'integer',
                'between:1,65535',
                Rule::unique('mikrotiks')->where('host', $this->input('host')),
            ],
            'username' => ['required', 'string', 'max:100'],
            'password_encrypted' => ['required', 'string', 'max:255'],
            'api_type' => ['required', Rule::in(['api', 'rest'])],
            'ssl_enabled' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'password_encrypted' => 'password',
        ];
    }
}
