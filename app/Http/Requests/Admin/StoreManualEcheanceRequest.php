<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualEcheanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'entreprise_id' => ['nullable', 'exists:entreprises,id'],
            'subscription_type' => ['required', 'in:default,site_web,multi_personnes'],
            'periode_debut' => ['required', 'date'],
            'periode_fin' => ['required', 'date', 'after_or_equal:periode_debut'],
            'jour_facturation' => ['nullable', 'integer', 'min:1', 'max:31'],
            'montant_du' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
