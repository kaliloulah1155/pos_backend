<?php

namespace App\Http\Requests\Categorie;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'libelle'=>'required|string|max:255|unique:categories',
        ];
    }
    
    public function messages()
    {
        return [
            'libelle.required' => 'Ce champ est requis.',
            'libelle.unique' => 'Ce libelle existe.',
            'libelle.max' => 'Le champ Libelle ne doit pas dépasser 255 caractères.' 
        ];
    }
}
