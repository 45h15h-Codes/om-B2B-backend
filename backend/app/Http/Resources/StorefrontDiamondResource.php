<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontDiamondResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Format a dynamic, clean storefront title
        $caratStr = $this->size ? floatval($this->size) . ' Carat ' : '';
        $shapeStr = $this->shape ? $this->shape . ' ' : '';
        $title = trim($caratStr . $shapeStr . 'Diamond');

        // Dynamic image resolver using new images column or legacy fields
        $imagePath = null;
        if (is_array($this->images) && count($this->images) > 0) {
            $imagePath = $this->images[0];
        } else {
            $imagePath = $this->diamond_image ?? $this->diamond_image_link;
        }

        $imageUrl = null;
        if (!empty($imagePath)) {
            if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
                $imageUrl = $imagePath;
            } elseif (str_starts_with($imagePath, 'diamonds/') || str_starts_with($imagePath, 'jewelleries/')) {
                $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath);
            } else {
                $imageUrl = asset($imagePath);
            }
        }

        return [
            'id' => $this->id,
            'sku' => $this->stock_no,
            'title' => $title,
            'shape' => $this->shape,
            'carat' => $this->size ? floatval($this->size) : null,
            'color' => $this->color,
            'clarity' => $this->clarity,
            'cut' => $this->cut,
            'price' => floatval($this->asking_price ?? $this->cash_price ?? 0.00),
            'image' => $imageUrl,
            'availability' => $this->inventory_status === 'available',
        ];
    }
}
