<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'phone:IR',
                'max:20',
                Rule::unique(User::class, 'phone')->ignore($this->user()->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام الزامی است.',
            'name.string' => 'نام باید متن باشد.',
            'name.max' => 'نام نباید بیش از 255 کاراکتر باشد.',
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.string' => 'شماره موبایل باید متن باشد.',
            'phone.phone' => 'شماره موبایل معتبر نیست.',
            'phone.max' => 'شماره موبایل نباید بیش از 20 کاراکتر باشد.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
        ];
    }
}
