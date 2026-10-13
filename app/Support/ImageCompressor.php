<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Rasm hajmini sifatni (ko'zga ko'rinadigan darajada) pasaytirmasdan kamaytiradi:
 *  - EXIF burilish to'g'rilanadi, metama'lumotlar (GPS va b.) olib tashlanadi;
 *  - faqat juda katta rasmlar (uzun tomoni > 2560 px) kichraytiriladi;
 *  - JPEG -> q90 (progressive); PNG -> lossless WebP (yoki PNG level 9); WebP -> q90;
 *  - natija asldan katta bo'lsa, asl fayl saqlanadi.
 * GD kengaytmasi yo'q bo'lsa, fayl o'zgarmasdan saqlanadi.
 */
class ImageCompressor
{
    public const MAX_SIDE = 2560;

    public const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    /** @return array{data:string, ext:string, width:int, height:int, original:int, size:int} */
    public static function process(UploadedFile $file): array
    {
        $raw = (string) file_get_contents($file->getRealPath());
        $mime = (string) $file->getMimeType();
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'jpg';
        $info = @getimagesizefromstring($raw) ?: [0, 0];
        $out = ['data' => $raw, 'ext' => $ext, 'width' => (int) $info[0], 'height' => (int) $info[1], 'original' => strlen($raw), 'size' => strlen($raw)];

        if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
            return $out;
        }
        $img = @imagecreatefromstring($raw);
        if (! $img) {
            return $out;
        }

        // EXIF burilish (telefon rasmlari)
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->getRealPath());
            $rot = match ((int) ($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
            if ($rot) {
                $rotated = imagerotate($img, $rot, 0);
                if ($rotated) {
                    $img = $rotated;
                }
            }
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $max = max($w, $h);
        if ($max > self::MAX_SIDE) {
            $scale = self::MAX_SIDE / $max;
            $resized = imagescale($img, (int) round($w * $scale), (int) round($h * $scale), IMG_BICUBIC);
            if ($resized) {
                $img = $resized;
                $w = imagesx($img);
                $h = imagesy($img);
            }
        }

        ob_start();
        $newExt = $ext;
        if ($mime === 'image/png') {
            imagesavealpha($img, true);
            if (defined('IMG_WEBP_LOSSLESS') && function_exists('imagewebp')) {
                imagewebp($img, null, IMG_WEBP_LOSSLESS);
                $newExt = 'webp';
            } else {
                imagepng($img, null, 9);
            }
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagewebp($img, null, 90);
        } else {
            imageinterlace($img, true);
            imagejpeg($img, null, 90);
            $newExt = 'jpg';
        }
        $data = (string) ob_get_clean();
        imagedestroy($img);

        // Natija kattaroq bo'lsa (va rasm kichraytirilmagan bo'lsa) — asl faylni saqlaymiz
        if ($data === '' || (strlen($data) >= strlen($raw) && $max <= self::MAX_SIDE)) {
            return $out;
        }

        return ['data' => $data, 'ext' => $newExt, 'width' => $w, 'height' => $h, 'original' => strlen($raw), 'size' => strlen($data)];
    }
}
