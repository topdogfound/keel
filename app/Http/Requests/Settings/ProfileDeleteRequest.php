<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Rules\NoSolelyOwnedTeams;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileDeleteRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'password' => [
                ...$this->currentPasswordRules(),
                // Deleting the sole owner of a shared team strands it: the FK
                // cascade removes the membership and nobody left can manage it.
                new NoSolelyOwnedTeams($user),
            ],
        ];
    }
}
