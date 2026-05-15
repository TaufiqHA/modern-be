<?php

namespace App\Http\Requests\Shipping;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CalculateShippingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address_id' => [
                'required',
                'exists:addresses,id,user_id,'.$this->user()->id,
            ],
            'weight' => 'required|integer|min:1',
            'courier' => 'required|string|in:jne,pos,tiki',
        ];
    }
}
