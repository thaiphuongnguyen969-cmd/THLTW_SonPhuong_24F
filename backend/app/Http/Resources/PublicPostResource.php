<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'thumbnail' => $this->thumbnail ? asset('storage/'.$this->thumbnail) : null,
            'author' => $this->whenLoaded('author', fn () => $this->author?->name),
            'published_at' => $this->published_at?->toDateTimeString(),
        ];
    }
}
