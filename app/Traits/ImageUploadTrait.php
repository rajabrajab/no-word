<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait ImageUploadTrait
{

    public function uploadImage($image, $folder = 'images', $customName = null)
    {
        if ($image && $image->isValid()) {
            $extension = $image->getClientOriginalExtension();

            $imageName = $customName
                ? $customName . '_' . uniqid() . '_' . now()->format('Ymd_His') . '.' . $extension
                : uniqid() . '_' . now()->format('Ymd_His') . '.' . $extension;

            $path = $image->storeAs($folder, $imageName, 'public');

            return $path;
        }

        return null;
    }

    public function deleteImage($imagePath)
    {
        if (!$imagePath) {
            return false;
        }

        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $urlPath = parse_url($imagePath, PHP_URL_PATH);
            $urlPath = ltrim($urlPath, '/');

            if (str_starts_with($urlPath, 'uploads/')) {
                $relative = substr($urlPath, strlen('uploads/'));
            } else {
                $relative = $urlPath;
            }
        } else {
            $relative = $imagePath;
        }

        if (Storage::disk('public')->exists($relative)) {
            return Storage::disk('public')->delete($relative);
        }

        return false;
    }
}
