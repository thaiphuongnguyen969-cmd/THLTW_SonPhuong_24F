<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        $partial = $this->isMethod('patch');
        $requiredOrSometimes = $partial ? 'sometimes' : 'required';
        $userId = filter_var($this->route('user'), FILTER_VALIDATE_INT);
        $email = Rule::unique('users', 'email');
        if ($userId !== false && $userId > 0) {
            $email->ignore($userId);
        }

        return [
            'name' => [$requiredOrSometimes, 'string', 'max:100'],
            'email' => [$requiredOrSometimes, 'string', 'email', 'max:100', $email],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['prohibited'],
            'password' => ['sometimes', 'string', 'min:6'],
            'role' => ['prohibited'],
            'id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'avatar' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'remember_token' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
