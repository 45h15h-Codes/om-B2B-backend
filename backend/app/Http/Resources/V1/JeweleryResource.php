<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class JeweleryResource extends JsonResource
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
            'sku' => $this->sku,
            'name' => $this->name,
            'type' => $this->type,
            'price' => $this->price ? floatval($this->price) : null,
            'image_url' => $this->formatUrl($this->image_url),
            'location' => $this->location,
            'inventory_status' => $this->inventory_status ?: 'available',
            'metal_type' => $this->metal_type,
            'metal_karat' => $this->metal_karat,
            'total_weight' => $this->total_weight ? floatval($this->total_weight) : null,
            'gemstone_type' => $this->gemstone_type,
            'gemstone_shape' => $this->gemstone_shape,
            'carat_weight' => $this->carat_weight ? floatval($this->carat_weight) : null,
            'ring_size' => $this->ring_size,
            'delivery_time' => $this->delivery_time,
            'msrp' => $this->msrp ? floatval($this->msrp) : null,
            'description' => $this->description,
        ];
    }

    /**
     * Helper to format path as full URL if not already complete.
     */
    private function formatUrl($path)
    {
        if (empty($path)) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return asset($path);
    }
}
