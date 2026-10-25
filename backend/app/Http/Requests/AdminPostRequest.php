<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminPostRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('post') && ! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => Str::slug((string) $this->input('title'))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        $updating = $this->isMethod('put') || $this->isMethod('patch');
        $post = $this->route('post');
        $uniqueSlug = Rule::unique('posts', 'slug');
        if ($post) {
            $uniqueSlug->ignore(is_object($post) ? $post->getKey() : $post);
        }

        return [
            'title' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [$updating ? 'sometimes' : 'required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $uniqueSlug],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'content' => [$updating ? 'sometimes' : 'required', 'string', 'max:1000000'],
            'status' => [$updating ? 'sometimes' : 'required', 'string', Rule::in(['draft', 'published'])],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'thumbnail' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'user_id' => ['prohibited'],
            'author_id' => ['prohibited'],
            'deleted_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
