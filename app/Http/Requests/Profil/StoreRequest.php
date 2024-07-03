<?php

namespace App\Http\Requests\Profil;

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
            'libelle'=>'required|string|max:255|unique:profils',
        ];
    }

    public function messages()
    {
        return [
            'libelle.unique' => 'Ce libelle existe.' 
        ];
    }
}
