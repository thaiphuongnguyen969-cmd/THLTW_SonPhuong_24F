<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        $updating = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'title' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'image' => [
                $updating ? 'sometimes' : 'required',
                ...($updating ? ['nullable'] : []),
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'link_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'button_text' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => [$updating ? 'sometimes' : 'required', 'string', Rule::in(['active', 'inactive'])],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:2147483647'],
            'id' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowedFields = [
                'title',
                'description',
                'image',
                'link_url',
                'button_text',
                'status',
                'sort_order',
                '_method',
            ];

            foreach (array_diff(array_keys($this->all()), $allowedFields) as $field) {
                $validator->errors()->add($field, 'Trường này không được phép gửi.');
            }

            $url = $this->input('link_url');
            if ($url === null || $url === '') {
                return;
            }
            if (! is_string($url)) {
                $validator->errors()->add('link_url', 'Liên kết không hợp lệ.');

                return;
            }

            $validInternal = preg_match('~^/(?!/)[^\s\\\\]*$~u', $url) === 1;
            $parts = parse_url($url);
            $validExternal = filter_var($url, FILTER_VALIDATE_URL) !== false
                && is_array($parts)
                && strtolower($parts['scheme'] ?? '') === 'https'
                && ! isset($parts['user'])
                && ! isset($parts['pass']);

            if (! $validInternal && ! $validExternal) {
                $validator->errors()->add('link_url', 'Liên kết phải là đường dẫn nội bộ hoặc URL HTTPS hợp lệ.');
            }
        }];
    }
}
