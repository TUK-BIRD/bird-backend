<?php

namespace App\Http\Requests;

use App\Enums\UserSpaceRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(UserSpaceRole::class)],
        ];
    }
}
