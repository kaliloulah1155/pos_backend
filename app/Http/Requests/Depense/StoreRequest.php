<?php

namespace App\Http\Requests\Depense;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description'=>'required',
            'montant'=>'required',
            'date_depense'=>'required',
        ];
    }
    
    public function messages()
    {
        return [
            'description.required' => 'Ce champ est requis.',
            'montant.required' => 'Ce champ est requis.',
            'date_depense.required' => 'Ce champ est requis.',
             
        ];
    }
}
