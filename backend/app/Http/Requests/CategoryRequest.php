<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && ($this->isMethod('post') || $this->exists('slug'))) {
            $this->merge([
                'slug' => Str::slug((string) $this->input('name')),
            ]);
        }
    }

    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');
        $updating = $this->isMethod('put') || $this->isMethod('patch');
        $slugUnique = Rule::unique('categories', 'slug');

        if ($category) {
            $slugUnique->ignore($category->getKey());
        }

        return [
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:100'],
            'slug' => [
                $updating ? 'sometimes' : 'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $slugUnique,
            ],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'deleted_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('parent_id') || ! $this->filled('parent_id')) {
                    return;
                }

                /** @var Category|null $category */
                $category = $this->route('category');
                $parentId = (int) $this->input('parent_id');

                if (! $category) {
                    return;
                }

                $visited = [];
                $currentId = $parentId;

                while ($currentId !== 0) {
                    if ($currentId === (int) $category->getKey()) {
                        $validator->errors()->add('parent_id', 'Danh mục không thể là cha của chính nó hoặc danh mục hậu duệ.');

                        return;
                    }

                    if (isset($visited[$currentId])) {
                        $validator->errors()->add('parent_id', 'Quan hệ danh mục hiện tại có vòng lặp và không thể cập nhật an toàn.');

                        return;
                    }

                    $visited[$currentId] = true;
                    $currentId = (int) (Category::query()
                        ->whereKey($currentId)
                        ->value('parent_id') ?? 0);
                }
            },
        ];
    }
}
