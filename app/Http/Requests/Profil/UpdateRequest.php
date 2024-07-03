<?php

namespace App\Http\Requests\Profil;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
     

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'libelle'=>'unique:profils',
        ];
    }
    public function messages()
    {
        return [
            'libelle.unique' => 'Ce profil existe.' ,
            
        ];
    }
}
