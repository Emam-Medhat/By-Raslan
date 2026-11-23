<?php

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /** Build Auth credentials from either a phone or an email login. */
    public function credentials(): array
    {
        $login = Str::lower(trim((string) $this->input('login')));

        if (preg_match('/^[+\d\s-]{6,}$/', $login)) {
            return ['phone' => preg_replace('/\D+/', '', $login), 'password' => $this->input('password')];
        }

        return ['email' => $login, 'password' => $this->input('password')];
    }

    public function loginKey(): string
    {
        return Str::lower(trim((string) $this->input('login')));
    }
}