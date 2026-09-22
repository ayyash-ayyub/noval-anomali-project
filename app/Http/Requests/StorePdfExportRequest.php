<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePdfExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'paper_size' => ['required', Rule::in(['a4', 'a5', 'thermal', 'card'])],
            'batch_id' => ['nullable', 'integer', 'exists:voucher_batches,id'],
            'voucher_ids' => ['nullable', 'array', 'min:1'],
            'voucher_ids.*' => ['integer', 'exists:vouchers,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasBatch = $this->filled('batch_id');
            $hasVouchers = $this->filled('voucher_ids');

            if ($hasBatch === $hasVouchers) {
                $validator->errors()->add('batch_id', 'Pilih salah satu: seluruh batch, atau voucher terpilih — tidak keduanya atau tidak sama sekali.');
            }
        });
    }
}
