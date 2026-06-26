<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingDocumentsRequest extends FormRequest
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
        $file = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        return [
            'identity' => $file,
            'iban' => $file,
            'address' => $file,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'identity' => 'الهوية',
            'iban' => 'شهادة الآيبان',
            'address' => 'إثبات العنوان',
        ];
    }
}
