<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'blocked'])],
            'role' => ['prohibited'],
            'password' => ['prohibited'],
        ];
    }
}
