<?php

namespace App\Services\Build;

use InvalidArgumentException;
use RuntimeException;

class ImageSanitizer
{
    public const MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    public const MAX_DIMENSION = 2048;

    public const MIN_DIMENSION = 48;

    /**
     * Validate and re-encode an image to strip EXIF metadata and neutralize polyglot payloads.
     *
     * @param  string  $rawBinary  The raw binary string of the image.
     * @return string Safe re-encoded PNG binary.
     */
    public function sanitizeAndReencode(string $rawBinary): string
    {
        if (strlen($rawBinary) > self::MAX_BYTES) {
            throw new InvalidArgumentException('Image file size exceeds maximum limit of 5MB.');
        }

        // Security check: reject SVGs and potential script injections
        if (str_contains(strtolower($rawBinary), '<svg') || str_contains(strtolower($rawBinary), '<script')) {
            throw new InvalidArgumentException('SVG and script-containing image formats are prohibited.');
        }

        // Load image using GD
        $img = @imagecreatefromstring($rawBinary);
        if ($img === false) {
            throw new InvalidArgumentException('Invalid or corrupt image format. Only PNG, JPEG, and WebP are allowed.');
        }

        $width = imagesx($img);
        $height = imagesy($img);

        if ($width < self::MIN_DIMENSION || $height < self::MIN_DIMENSION) {
            imagedestroy($img);
            throw new InvalidArgumentException('Image dimensions are too small. Minimum is '.self::MIN_DIMENSION.'x'.self::MIN_DIMENSION.'px.');
        }

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            imagedestroy($img);
            throw new InvalidArgumentException('Image dimensions are too large. Maximum is '.self::MAX_DIMENSION.'x'.self::MAX_DIMENSION.'px.');
        }

        // Create clean truecolor canvas to guarantee EXIF strip
        $clean = imagecreatetruecolor($width, $height);
        if (! $clean) {
            imagedestroy($img);
            throw new RuntimeException('Failed to allocate image resource.');
        }

        // Preserve alpha transparency
        imagealphablending($clean, false);
        imagesavealpha($clean, true);
        $transparent = imagecolorallocatealpha($clean, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($clean, 0, 0, $width, $height, $transparent);
        }

        imagecopy($clean, $img, 0, 0, 0, 0, $width, $height);
        imagedestroy($img);

        ob_start();
        imagepng($clean);
        $pngData = (string) ob_get_clean();
        imagedestroy($clean);

        return $pngData;
    }

    /**
     * Generate Android mipmap icon set from a clean icon image.
     *
     * @return array<string, string> Map of folder name => PNG binary
     */
    public function generateAndroidMipmaps(string $cleanPngBinary): array
    {
        $src = @imagecreatefromstring($cleanPngBinary);
        if ($src === false) {
            throw new InvalidArgumentException('Cannot load sanitized PNG binary.');
        }

        $sizes = [
            'mipmap-mdpi' => 48,
            'mipmap-hdpi' => 72,
            'mipmap-xhdpi' => 96,
            'mipmap-xxhdpi' => 144,
            'mipmap-xxxhdpi' => 192,
        ];

        $output = [];
        $srcW = imagesx($src);
        $srcH = imagesy($src);

        foreach ($sizes as $folder => $size) {
            $resized = imagecreatetruecolor($size, $size);
            if ($resized) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $trans = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                if ($trans !== false) {
                    imagefilledrectangle($resized, 0, 0, $size, $size, $trans);
                }

                imagecopyresampled($resized, $src, 0, 0, 0, 0, $size, $size, $srcW, $srcH);

                ob_start();
                imagepng($resized);
                $output[$folder] = (string) ob_get_clean();
                imagedestroy($resized);
            }
        }

        imagedestroy($src);

        return $output;
    }
}
