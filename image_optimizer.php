<?php

/**
 * Optimizes uploaded images in-place using GD.
 * - Resizes large images to a practical max dimension.
 * - Re-encodes images with sensible quality/compression defaults.
 */
function optimizeImageInPlace(string $filePath, int $maxDimension = 1920): bool
{
    if (!file_exists($filePath)) {
        return false;
    }

    $imageInfo = @getimagesize($filePath);
    if ($imageInfo === false) {
        return false;
    }

    $mime = $imageInfo['mime'] ?? '';
    $width = (int)($imageInfo[0] ?? 0);
    $height = (int)($imageInfo[1] ?? 0);
    if ($width <= 0 || $height <= 0) {
        return false;
    }

    $sourceImage = null;
    switch ($mime) {
        case 'image/jpeg':
            $sourceImage = @imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $sourceImage = @imagecreatefrompng($filePath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $sourceImage = @imagecreatefromwebp($filePath);
            }
            break;
        case 'image/gif':
            $sourceImage = @imagecreatefromgif($filePath);
            break;
        default:
            return false;
    }

    if (!$sourceImage) {
        return false;
    }

    $scale = min(1, $maxDimension / max($width, $height));
    $targetWidth = max(1, (int)round($width * $scale));
    $targetHeight = max(1, (int)round($height * $scale));

    $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
    if (!$targetImage) {
        imagedestroy($sourceImage);
        return false;
    }

    // Preserve alpha for transparent formats.
    if ($mime === 'image/png' || $mime === 'image/webp' || $mime === 'image/gif') {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
        imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);
    }

    imagecopyresampled(
        $targetImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $targetWidth,
        $targetHeight,
        $width,
        $height
    );

    $saved = false;
    switch ($mime) {
        case 'image/jpeg':
            $saved = imagejpeg($targetImage, $filePath, 78);
            break;
        case 'image/png':
            // 0 = no compression, 9 = max compression.
            $saved = imagepng($targetImage, $filePath, 8);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) {
                $saved = imagewebp($targetImage, $filePath, 78);
            }
            break;
        case 'image/gif':
            $saved = imagegif($targetImage, $filePath);
            break;
    }

    imagedestroy($targetImage);
    imagedestroy($sourceImage);
    return (bool)$saved;
}

