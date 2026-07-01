<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership enforced by the controller policy.
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'receipt' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receipt.required' => 'يرجى رفع إيصال التحويل.',
            'receipt.mimes' => 'صيغة الملف يجب أن تكون PDF أو صورة (JPG/PNG).',
            'receipt.max' => 'أقصى حجم للملف 5 ميجابايت.',
        ];
    }
}
