<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontDiamondDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // 1. Slug and Title generation for React storefront
        $shapeLower = strtolower(str_replace(' ', '-', trim($this->shape)));
        $slug = $this->id . '-' . ($shapeLower ?: 'unknown') . '-diamond';

        $caratStr = $this->size ? floatval($this->size) . ' Carat ' : '';
        $shapeStr = $this->shape ? $this->shape . ' ' : '';
        $title = trim($caratStr . $shapeStr . 'Diamond');

        // 2. Map specifications dynamically with production field fallbacks
        $specs = $this->specifications ?? [];

        $cut = $specs['cut'] ?? $this->cut;
        $polish = $specs['polish'] ?? $this->polish;
        $symmetry = $specs['symmetry'] ?? $this->symmetry;
        
        $fluorescence = $specs['fluorescence'] ?? $specs['fluorescence_intensity'] ?? $this->fluorescence_intensity;
        $measurements = $specs['measurements'] ?? $this->measurements;
        
        $certificate = $specs['certificate'] ?? $specs['lab'] ?? $this->lab;
        $certificateNumber = $specs['certificate_number'] ?? $specs['report_no'] ?? $this->report_no;
        
        $depthPercentage = $specs['depth'] ?? $specs['depth_percent'] ?? $this->depth_percent;
        $tablePercentage = $specs['table'] ?? $specs['table_percent'] ?? $this->table_percent;

        // 3. Absolute image URLs collection formatting
        $images = [];
        $dbImages = $this->images;
        if (is_array($dbImages) && count($dbImages) > 0) {
            foreach ($dbImages as $img) {
                if (!empty($img)) {
                    $images[] = (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) 
                        ? $img 
                        : (str_starts_with($img, 'diamonds/') || str_starts_with($img, 'jewelleries/') ? \Illuminate\Support\Facades\Storage::disk('public')->url($img) : asset($img));
                }
            }
        } else {
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
        }

        // Fallback to legacy single image if images array is empty
        if (empty($images)) {
            $singleImage = $this->diamond_image ?? $this->diamond_image_link;
            if (!empty($singleImage)) {
                $images[] = (str_starts_with($singleImage, 'http://') || str_starts_with($singleImage, 'https://')) ? $singleImage : asset($singleImage);
            }
        }

        // 4. Video link formatting
        $videos = [];
        $videoUrl = null;
        $dbVideos = $this->videos;
        if (is_array($dbVideos) && count($dbVideos) > 0) {
            foreach ($dbVideos as $vid) {
                if (!empty($vid)) {
                    $absVidUrl = (str_starts_with($vid, 'http://') || str_starts_with($vid, 'https://')) 
                        ? $vid 
                        : (str_starts_with($vid, 'diamonds/') || str_starts_with($vid, 'jewelleries/') ? \Illuminate\Support\Facades\Storage::disk('public')->url($vid) : asset($vid));
                    $videos[] = $absVidUrl;
                }
            }
            if (count($videos) > 0) {
                $videoUrl = $videos[0];
            }
        } else {
            $videoUrl = $specs['video'] ?? $this->sarine_loupe;
            if (!empty($videoUrl)) {
                if (!str_starts_with($videoUrl, 'http://') && !str_starts_with($videoUrl, 'https://')) {
                    $videoUrl = asset($videoUrl);
                }
                $videos[] = $videoUrl;
            }
        }

        return [
            'id' => $this->id,
            'sku' => $this->stock_no,
            'slug' => $slug,
            'title' => $title,
            'shape' => $this->shape,
            'carat' => $this->size ? floatval($this->size) : null,
            'color' => $this->color,
            'clarity' => $this->clarity,
            'cut' => $cut,
            'polish' => $polish,
            'symmetry' => $symmetry,
            'fluorescence' => $fluorescence,
            'certificate' => $certificate,
            'certificate_number' => $certificateNumber,
            'measurements' => $measurements,
            'depth_percentage' => $depthPercentage ? strval(floatval($depthPercentage)) : null,
            'table_percentage' => $tablePercentage ? strval(floatval($tablePercentage)) : null,
            'price' => floatval($this->asking_price ?? $this->cash_price ?? 0.00),
            'asking_price' => $this->asking_price ? floatval($this->asking_price) : null,
            'cash_price' => $this->cash_price ? floatval($this->cash_price) : null,
            'availability' => $this->inventory_status === 'available',
            'description' => $specs['description'] ?? null,
            'images' => $images,
            'video' => $videoUrl ?: null,
            'videos' => $videos,
            'created_at' => $this->created_at ? $this->created_at->utc()->format('Y-m-d\TH:i:s\Z') : null,
        ];
    }
}
