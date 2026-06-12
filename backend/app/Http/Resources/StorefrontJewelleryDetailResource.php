<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontJewelleryDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $specs = $this->specifications ?? [];

        // Dynamic images array collector
        $images = [];
        $specImages = $specs['images'] ?? null;
        if (is_array($specImages)) {
            foreach ($specImages as $img) {
                if (!empty($img)) {
                    $images[] = (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) ? $img : asset($img);
                }
            }
        } elseif (is_string($specImages) && !empty($specImages)) {
            $images[] = (str_starts_with($specImages, 'http://') || str_starts_with($specImages, 'https://')) ? $specImages : asset($specImages);
        }

        // Fallback to legacy single image if specifications images array is empty
        if (empty($images) && !empty($this->image_url)) {
            $images[] = (str_starts_with($this->image_url, 'http://') || str_starts_with($this->image_url, 'https://')) ? $this->image_url : asset($this->image_url);
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'type' => $this->type,
            'type_style' => $this->type_style,
            'category' => $this->category,
            'condition' => $this->condition,
            'brand' => $this->brand,
            'quality' => $this->quality,
            'metal_type' => $this->metal_type,
            'metal_karat' => $this->metal_karat,
            'total_weight' => $this->total_weight ? floatval($this->total_weight) : null,
            'gemstone_type' => $this->gemstone_type,
            'gemstone_shape' => $this->gemstone_shape,
            'carat_weight' => $this->carat_weight ? floatval($this->carat_weight) : null,
            'lab' => $this->lab,
            'lab_no' => $this->lab_no,
            'lot_no' => $this->lot_no,
            'description' => $this->description,
            'price' => floatval($this->price ?? 0.00),
            'images' => $images,
            'availability' => $this->inventory_status === 'available',
            'created_at' => $this->created_at ? $this->created_at->utc()->format('Y-m-d\TH:i:s\Z') : null,
        ];
    }
}
