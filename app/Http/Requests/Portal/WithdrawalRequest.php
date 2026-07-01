<?php

namespace App\Http\Requests\Portal;

use App\Services\Portal\WalletService;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only investors with a bank account on file may request a withdrawal.
        return $this->user() !== null && $this->user()->hasBankAccount();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $balance = app(WalletService::class)->balance($this->user());

        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:'.$balance],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.max' => 'المبلغ يتجاوز رصيد محفظتك المتاح.',
            'amount.min' => 'أقل مبلغ للسحب هو 1 ر.س.',
        ];
    }
}
