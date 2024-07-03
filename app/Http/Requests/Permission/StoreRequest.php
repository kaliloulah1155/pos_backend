<?php

namespace App\Http\Requests\Permission;

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
            'profil_id'=>'required',
        ];
    }

    public function messages()
    {
        return [
            'profil_id.required' => 'Ce profil est requis.',
        ];
    }

}
