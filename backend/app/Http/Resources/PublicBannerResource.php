<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicBannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image ? asset('storage/'.$this->image) : null,
            'link_url' => $this->link_url,
            'button_text' => $this->button_text,
            'sort_order' => $this->sort_order,
        ];
    }
}
