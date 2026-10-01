<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Both Admin and Operator may generate vouchers (spec: Operator
        // "generate voucher"); no policy restriction is needed here.
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mikrotik_id' => ['required', 'integer', 'exists:mikrotiks,id'],
            'profile' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'username_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'username_method' => ['required', Rule::in(['sequential', 'random', 'user_equals_password'])],
            'username_length' => ['required', 'integer', 'between:3,12'],
            'password_method' => ['required', Rule::in(['numeric', 'alphanumeric'])],
            'password_length' => ['required', 'integer', 'between:4,20'],
        ];
    }
}
