<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MarkManualEcheancePaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['required', 'date'],
            'payment_mode' => ['required', 'in:cash,bank_transfer,card_terminal,other'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
