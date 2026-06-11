<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_number' => $this->shopify_order_number ?: $this->shopify_order_id,
            'status' => $this->status,
            'total' => $this->total ? floatval($this->total) : 0.00,
            'subtotal' => $this->subtotal ? floatval($this->subtotal) : 0.00,
            'discount' => $this->discount ? floatval($this->discount) : 0.00,
            'invoice_url' => $this->invoice_url,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'items' => $this->items ?: [],
        ];
    }
}
