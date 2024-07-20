<?php

namespace App\Http\Requests\ForgotPassword;

use Illuminate\Foundation\Http\FormRequest;
 

class ResetPasswordRequest extends FormRequest
{
    
    public function rules(): array
    {
        return [
            'token'=>'required',
            'password'=>'required|min:6|confirmed'
        ];   
    }

    public function messages()
    {
        return [
            'token.required' => "Le champ token est requis." ,
            'password.required' => "Le champ mot de passe est requis." ,
            'password.min' => "Le champ du mot de passe doit contenir au moins 6 caractères.",
            'password.confirmed' => 'Le mot de passe ne correspond pas à la confirmation.',
        ];
    }
}
