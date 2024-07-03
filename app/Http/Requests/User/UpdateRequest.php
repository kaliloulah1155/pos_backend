<?php

namespace App\Http\Requests\User;

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
            //'email'=>'unique:users',
            //'telephone'=>'unique:users',
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => 'Cette adresse email existe.',
            'telephone.unique' => 'Ce n° de telephone existe.',
        ];
    }
}
