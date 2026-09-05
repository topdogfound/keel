<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'return_url' => ['nullable', 'string', 'max:2048', 'regex:~^/(?!/)[^\\\\]*$~'],
        ];
    }

    public function returnUrl(): string
    {
        return (string) $this->input('return_url', route('home', absolute: false));
    }
}
