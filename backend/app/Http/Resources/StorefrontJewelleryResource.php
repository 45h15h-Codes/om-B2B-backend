<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontJewelleryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $imageUrl = $this->image_url;
        if (!empty($imageUrl) && !str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
            $imageUrl = asset($imageUrl);
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'metal_type' => $this->metal_type,
            'metal_karat' => $this->metal_karat,
            'price' => floatval($this->price ?? 0.00),
            'image' => $imageUrl ?: null,
            'availability' => $this->inventory_status === 'available',
        ];
    }
}
