<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Services\OrderService;

class AdminOrderResource extends OrderResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        if ($this->resource->relationLoaded('statusHistories')) {
            $data['status_histories'] = $this->statusHistories->map(fn ($history) => [
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'note' => $history->note,
                'created_at' => $history->created_at?->toDateTimeString(),
                'changed_by' => $history->changed_by,
                'changed_by_name' => $history->relationLoaded('changedBy') ? $history->changedBy?->name : null,
            ]);
        }

        return [
            ...$data,
            'allowed_status_transitions' => OrderService::availableAdminTransitions((string) $this->status),
            'customer' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null),
        ];
    }
}
