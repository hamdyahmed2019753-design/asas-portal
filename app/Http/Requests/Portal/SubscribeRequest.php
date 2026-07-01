<?php

namespace App\Http\Requests\Portal;

use App\Models\Contract;
use App\Services\Portal\SubscriptionService;
use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership + KYC enforced by the controller policy.
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Contract $contract */
        $contract = $this->route('contract');
        $bounds = app(SubscriptionService::class)->shareBounds($contract);

        $rules = ['required', 'integer', 'min:'.$bounds['min']];
        if ($bounds['max'] !== null) {
            $rules[] = 'max:'.$bounds['max'];
        }

        return ['shares' => $rules];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shares.required' => 'يرجى إدخال عدد الحصص.',
            'shares.min' => 'أقل عدد حصص مسموح هو :min.',
            'shares.max' => 'أقصى عدد حصص مسموح هو :max.',
        ];
    }
}
