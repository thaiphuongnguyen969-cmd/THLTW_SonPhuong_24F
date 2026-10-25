<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        $updating = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:100'],
            'link_type' => [$updating ? 'sometimes' : 'required', 'string', Rule::in(['internal', 'external'])],
            'url' => [$updating ? 'sometimes' : 'required', 'string', 'max:2048'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:2147483647'],
            'status' => [$updating ? 'sometimes' : 'required', 'string', Rule::in(['active', 'inactive'])],
            'id' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $menu = $this->route('menu');
            $url = $this->input('url', is_object($menu) ? $menu->url : null);
            $linkType = $this->input('link_type', is_object($menu) ? $menu->link_type : null);
            if (! is_string($url) || ! is_string($linkType)) return;
            $valid = false;
            if ($linkType === 'internal') {
                $valid = preg_match('~^/(?!/)[^\s\\\\]*$~u', $url) === 1;
            } elseif ($linkType === 'external') {
                $parts = parse_url($url);
                $valid = filter_var($url, FILTER_VALIDATE_URL) !== false
                    && is_array($parts)
                    && strtolower($parts['scheme'] ?? '') === 'https'
                    && ! isset($parts['user'])
                    && ! isset($parts['pass']);
            }

            if (! $valid) {
                $validator->errors()->add('url', 'Đường dẫn nội bộ phải bắt đầu bằng một dấu /; URL ngoài phải là HTTPS hợp lệ.');
            }
        }];
    }
}
