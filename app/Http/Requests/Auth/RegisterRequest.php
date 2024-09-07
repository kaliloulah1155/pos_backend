<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    
    public function rules(): array
    {
        return [
            'nom'=>'required|string|max:255',
            'prenoms'=>'required|string|max:255',
            'telephone'=>'required|regex:/^\d{10}$/',
            'email'=>'required|string|email|max:255|unique:users',
            'password'=>'required|string|min:6|confirmed',
            'profil_id'=>'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'nom.required' => 'Le champ nom est requis.',
            'prenoms.required' => 'Le champ prenoms est requis.',
            'telephone.required' => 'Le champ téléphone est requis.',
            'telephone.regex' => 'Le format du N° de téléphone est invalide.',
            'email.unique' => 'Cette adresse email existe.',
            'email.required' => 'Le champ email est requis.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'password.required' => 'Le champ mot de passe est requis.',
            'password.confirmed' => 'Le mot de passe ne correspond pas à la confirmation.',
            'profil_id.required' => 'Le champ profil est requis.',
        ];
    }
}
