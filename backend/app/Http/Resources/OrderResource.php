<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Order) {
            throw new LogicException('Order resource requires an Order model.');
        }

        $shippingAddress = json_decode((string) $this->shipping_address_detail, true);

        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'amounts' => [
                'subtotal' => $this->subtotal,
                'discount' => $this->discount_amount,
                'shipping_fee' => $this->shipping_fee,
                'total' => $this->total_amount ?? $this->total,
            ],
            'shipping_address' => is_array($shippingAddress) ? $shippingAddress : [
                'address_detail' => $this->shipping_address_detail ?? $this->address,
                'phone' => $this->shipping_phone ?? $this->phone,
            ],
            'note' => $this->note,
            'cancelled_reason' => $this->cancelled_reason,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'size' => $item->size,
                'color' => $item->color,
                'unit_price' => $item->price,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_method' => $payment->payment_method,
                'transaction_code' => $payment->transaction_code,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at?->toDateTimeString(),
            ])),
            'status_histories' => $this->whenLoaded(
                'statusHistories',
                fn () => $this->statusHistories->map(fn ($history) => [
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'note' => $history->note,
                    'created_at' => $history->created_at?->toDateTimeString(),
                ])
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
