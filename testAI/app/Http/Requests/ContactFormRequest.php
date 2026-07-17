<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'min:2', 'max:100'],
            'phone'   => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'email'   => ['required', 'email', 'max:255'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Укажите имя.',
            'name.min'    => 'Имя должно быть больше 2 символов',
            'name.max'    => 'Имя не должно быть больше 100 символов',
            'phone.required'   => 'Укажите телефон.',
            'phone.regex'      => 'Некорректный формат телефона.',
            'email.email'      => 'Некорректный email.',
            'email.max'      => 'Email не должен быть больше 100 символов',
            'comment.required' => 'Комментарий не может быть пустым.',
            'comment.min'      => 'Комментарий слишком короткий.',
            'comment.max'      => 'Комментарий слишком длинный.',
        ];
    }
}
