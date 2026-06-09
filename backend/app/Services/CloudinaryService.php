<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    /**
     * Upload a file to Cloudinary.
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string|null Secure URL of the uploaded asset, or null if Cloudinary is not configured or fails.
     */
    public static function upload($file)
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        // Check if Cloudinary is configured. If not, trigger local fallback.
        if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
            Log::info("Cloudinary is not configured. Falling back to local storage.");
            return null;
        }

        try {
            $realPath = '';
            $originalName = '';

            if (is_string($file)) {
                $realPath = $file;
                $originalName = basename($file);
            } else {
                $realPath = $file->getRealPath();
                $originalName = $file->getClientOriginalName();
            }

            if (!file_exists($realPath)) {
                Log::error("File not found at: {$realPath}");
                return null;
            }

            // Upload using Cloudinary Laravel package
            $uploadedFile = Cloudinary::upload($realPath, [
                'resource_type' => 'auto',
                'folder' => 'diamonds',
                'public_id' => pathinfo($originalName, PATHINFO_FILENAME) . '_' . time()
            ]);

            $secureUrl = $uploadedFile->getSecurePath();
            Log::info("File uploaded to Cloudinary: {$secureUrl}");
            return $secureUrl;
        } catch (Exception $e) {
            Log::error("Cloudinary upload failed: " . $e->getMessage());
            // Fail gracefully to trigger local fallback
            return null;
        }
    }

    /**
     * Delete a file from Cloudinary.
     * 
     * @param string $url Secure URL of the Cloudinary asset
     * @return bool True if deleted successfully, false otherwise
     */
    public static function delete(string $url): bool
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
            Log::info("Cloudinary credentials not configured. Skipping remote deletion.");
            return false;
        }

        try {
            $path = parse_url($url, PHP_URL_PATH);
            if (!$path) {
                return false;
            }

            // Path pattern: /cloud_name/image/upload/v12345678/diamonds/public_id.jpg
            $pathWithoutExt = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME);
            $segments = explode('/', trim($pathWithoutExt, '/'));
            
            $uploadIndex = array_search('upload', $segments);
            if ($uploadIndex === false) {
                return false;
            }

            $versionIndex = $uploadIndex + 1;
            if (isset($segments[$versionIndex]) && preg_match('/^v\d+$/', $segments[$versionIndex])) {
                $publicIdSegments = array_slice($segments, $versionIndex + 1);
            } else {
                $publicIdSegments = array_slice($segments, $uploadIndex + 1);
            }

            $publicId = implode('/', $publicIdSegments);
            if (empty($publicId)) {
                return false;
            }

            // Delete using Cloudinary Laravel package
            Cloudinary::destroy($publicId);
            Log::info("File deleted from Cloudinary: {$publicId}");
            return true;
        } catch (Exception $e) {
            Log::error("Cloudinary delete failed: " . $e->getMessage());
            return false;
        }
    }
}

