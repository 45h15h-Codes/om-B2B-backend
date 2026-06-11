<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class DiamondResource extends JsonResource
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
            'stock_no' => $this->stock_no,
            'shape' => $this->shape,
            'size' => $this->size ? floatval($this->size) : null,
            'color' => $this->color,
            'clarity' => $this->clarity,
            'asking_price' => $this->asking_price ? floatval($this->asking_price) : null,
            'cash_price' => $this->cash_price ? floatval($this->cash_price) : null,
            'price' => floatval($this->asking_price ?? $this->cash_price ?? 0.00),
            'report_file' => $this->formatUrl($this->report_file),
            'report_link' => $this->report_link,
            'diamond_image' => $this->formatUrl($this->diamond_image),
            'diamond_image_link' => $this->diamond_image_link,
            'sarine_loupe' => $this->sarine_loupe,
            'cut' => $this->cut,
            'polish' => $this->polish,
            'symmetry' => $this->symmetry,
            'fluorescence_intensity' => $this->fluorescence_intensity,
            'lab' => $this->lab,
            'report_no' => $this->report_no,
            'measurements' => $this->measurements,
            'depth_percent' => $this->depth_percent ? floatval($this->depth_percent) : null,
            'table_percent' => $this->table_percent ? floatval($this->table_percent) : null,
            'country' => $this->country,
            'inventory_status' => $this->inventory_status ?: 'available',
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
