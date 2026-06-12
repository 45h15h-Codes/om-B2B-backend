<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontCartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $title = 'Unknown Product';
        $image = null;
        $price = 0.0;
        $availability = 'unavailable';
        $quantity = intval($this->quantity);

        if ($this->product_type === 'diamond') {
            $diamond = \App\Models\Diamond::find($this->product_id);
            if ($diamond) {
                $caratStr = $diamond->size ? floatval($diamond->size) . ' CT ' : '';
                $shapeStr = $diamond->shape ? $diamond->shape . ' ' : '';
                $title = trim($caratStr . $shapeStr . 'Diamond');

                $imagePath = $diamond->diamond_image ?? $diamond->diamond_image_link;
                if (!empty($imagePath)) {
                    $image = (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) ? $imagePath : asset($imagePath);
                }

                $price = floatval($diamond->asking_price ?? $diamond->cash_price ?? 0.00);
                $availability = ($diamond->inventory_status === 'available') ? 'available' : 'unavailable';
            }
        } elseif ($this->product_type === 'jewellery') {
            $jewellery = \App\Models\Jewelery::find($this->product_id);
            if ($jewellery) {
                $title = $jewellery->name;

                $imagePath = $jewellery->image_url;
                if (!empty($imagePath)) {
                    $image = (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) ? $imagePath : asset($imagePath);
                }

                $price = floatval($jewellery->price ?? 0.00);
                $availability = ($jewellery->inventory_status === 'available') ? 'available' : 'unavailable';
            }
        }

        $line_total = $price * $quantity;

        return [
            'id' => $this->id,
            'product_type' => $this->product_type,
            'product_id' => $this->product_id,
            'title' => $title,
            'image' => $image,
            'price' => $price,
            'quantity' => $quantity,
            'line_total' => $line_total,
            'availability' => $availability,
        ];
    }
}
