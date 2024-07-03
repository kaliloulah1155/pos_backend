<?php

namespace App\Http\Requests\Salaire;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
     public function rules(): array
    {
        return [
            'user_id'=>'required',
            'montant'=>'required',
            'date_salaire'=>'required',
        ];
    }
    
    public function messages()
    {
        return [
            'user_id.required' => 'Ce champ est requis.',
            'montant.required' => 'Ce champ est requis.',
            'date_salaire.required' => 'Ce champ est requis.',
             
        ];
    }
}
