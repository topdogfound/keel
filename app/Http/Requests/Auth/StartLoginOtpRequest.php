<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StartLoginOtpRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'remember' => ['nullable', 'boolean'],
            'modal' => ['nullable', 'boolean'],
            'return_url' => ['nullable', 'string', 'max:2048', 'regex:~^/(?!/)[^\\\\]*$~'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email')
                ? Str::lower(trim((string) $this->input('email')))
                : null,
        ]);
    }

    public function email(): string
    {
        return (string) $this->input('email');
    }

    public function returnUrl(): string
    {
        return (string) $this->input('return_url', route('home', absolute: false));
    }
}
