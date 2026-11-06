<?php

namespace App\Http\Requests;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
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
        /** @var Brand|null $brand */
        $brand = $this->route('brand');
        $updating = $this->isMethod('put') || $this->isMethod('patch');
        $nameUnique = Rule::unique('brands', 'name');
        $slugUnique = Rule::unique('brands', 'slug');

        if ($brand) {
            $nameUnique->ignore($brand->getKey());
            $slugUnique->ignore($brand->getKey());
        }

        return [
            'name' => [
                $updating ? 'sometimes' : 'required',
                'string',
                'max:100',
                $nameUnique,
            ],
            'slug' => [
                $updating ? 'sometimes' : 'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $slugUnique,
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'deleted_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
        ];
    }
}
