<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeUserId = filter_var($this->route('user'), FILTER_VALIDATE_INT);
        $updating = $this->route('user') !== null;
        $partial = $this->isMethod('patch');
        $emailUnique = Rule::unique('users', 'email');

        if ($updating && $routeUserId !== false && $routeUserId > 0) {
            $emailUnique->ignore($routeUserId);
        }

        $requiredOrSometimes = $partial ? 'sometimes' : 'required';

        return [
            'name' => [$requiredOrSometimes, 'string', 'max:100'],
            'email' => [$requiredOrSometimes, 'string', 'email', 'max:100', $emailUnique],
            'password' => [
                $updating ? 'sometimes' : 'required',
                'string',
                'min:6',
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
            'status' => ['prohibited'],
            'avatar' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'remember_token' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
