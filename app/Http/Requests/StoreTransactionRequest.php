<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'montant' => ['required', 'numeric', 'decimal:2', 'min:0.01'],
            'moyen' => ['nullable', 'in:especes,cheque,virement,autre'],
            'description' => ['nullable', 'string', 'max:255'],
        ];

        $maxDeposit = config('capoplus.max_deposit');
        if ($this->routeIs('accounts.depot') && $maxDeposit !== null) {
            $rules['montant'][] = 'max:' . $maxDeposit;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit etre un nombre.',
            'montant.min' => 'Le montant doit etre superieur a zero.',
            'montant.max' => 'Le montant depasse le plafond de depot autorise.',
            'moyen.in' => 'Le moyen de paiement selectionne est invalide.',
        ];
    }
}
