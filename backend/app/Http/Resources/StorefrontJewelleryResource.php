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
        $imageUrl = null;
        if (is_array($this->images) && count($this->images) > 0) {
            $img = $this->images[0];
            $imageUrl = (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) 
                ? $img 
                : (str_starts_with($img, 'diamonds/') || str_starts_with($img, 'jewelleries/') ? \Illuminate\Support\Facades\Storage::disk('public')->url($img) : asset($img));
        } else {
            $imageUrl = $this->image_url;
            if (!empty($imageUrl) && !str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
                $imageUrl = asset($imageUrl);
            }
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
