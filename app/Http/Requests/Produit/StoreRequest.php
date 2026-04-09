<?php

namespace App\Http\Requests\Produit;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'libelle' => 'required|string|max:255|unique:produits',
            'barcode' => 'nullable|string|unique:produits,barcode',  // ← ajouté
        ];
    }

    public function messages()
    {
        return [
            'libelle.required' => 'Ce champ est requis.',
            'libelle.unique'   => 'Ce libellé existe déjà.',
            'libelle.max'      => 'Le champ Libellé ne doit pas dépasser 255 caractères.',
            'barcode.unique'   => 'Ce barcode est déjà utilisé par un autre produit.',  // ← ajouté
        ];
    }
}