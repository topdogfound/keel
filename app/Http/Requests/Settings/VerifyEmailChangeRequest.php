<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Actions\Auth\LoginConfiguration;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailChangeRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email_code' => ['required', 'digits:'.app(LoginConfiguration::class)->otpLength()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email_code' => $this->normalizeCode($this->input('email_code')),
        ]);
    }

    public function code(): string
    {
        return (string) $this->input('email_code', '');
    }

    private function normalizeCode(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return is_string($digits) && $digits !== '' ? $digits : null;
    }
}
