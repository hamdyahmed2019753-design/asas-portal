<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalise the IBAN (strip spaces, upper-case) before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('bank_iban')) {
            $this->merge([
                'bank_iban' => strtoupper(str_replace(' ', '', (string) $this->input('bank_iban'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'bank_iban' => ['required', 'string', 'regex:/^SA\d{22}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank_iban.regex' => 'صيغة الآيبان غير صحيحة — يجب أن يبدأ بـ SA ويتبعه 22 رقمًا.',
        ];
    }
}
