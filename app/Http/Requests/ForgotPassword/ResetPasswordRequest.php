<?php

namespace App\Http\Requests\ForgotPassword;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return !($user=auth()->user()) || !($user instanceof User);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            //'email'=>'required|email|exists:users,email',
            'token'=>'required',
            'password'=>'required|min:6|confirmed'
        ];   
    }

    public function messages()
    {
        return [
            //'email.required' => "Le champ email est requis." ,
            //'email.email' => "Veuillez renter un email valide.",
            //'email.exists' => "Vous n'avez pas de compte sur ce site.",
            'token.required' => "Le champ token est requis." ,
            'password.required' => "Le champ mot de passe est requis." ,
            'password.min' => "Le champ du mot de passe doit contenir au moins 6 caractères.",
            'password.confirmed' => 'Le mot de passe ne correspond pas à la confirmation.',
        ];
    }
}
