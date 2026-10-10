<?php

namespace App\Services\Landing;

use App\Models\Landing;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Photos the owner puts on a landing. Files go to the `landing_media` disk
 * (public/uploads/landings/{id}), the path is kept in settings.images.{key}.
 *
 * With GD the photo is re-encoded and scaled down: EXIF (GPS included) is
 * dropped, phone photos shrink from megabytes to a few hundred KB, and only
 * real image data survives. Without GD the validated original is kept.
 */
class LandingImageStore
{
    public const MAX_SIDE = 1600;

    public const MAX_FILES_PER_LANDING = 40;

    public function __construct(private readonly LandingContent $content)
    {
    }

    public function disk()
    {
        return Storage::disk('landing_media');
    }

    /** Public URL of the stored path, or null when the file is gone. */
    public function url(?string $path): ?string
    {
        return is_string($path) && $path !== '' && $this->disk()->exists($path)
            ? $this->disk()->url($path)
            : null;
    }

    public function store(Landing $landing, string $key, UploadedFile $file): string
    {
        $dir = 'landings/' . $landing->id;
        $old = data_get($landing->settings, 'images.' . $key);

        if (! $old && count($this->disk()->files($dir)) >= self::MAX_FILES_PER_LANDING) {
            throw ValidationException::withMessages(['file' => __('landings.editor.errors.image_quota')]);
        }

        [$binary, $extension] = $this->encode($file);

        $path = $dir . '/' . $key . '-' . Str::lower(Str::random(10)) . '.' . $extension;
        $this->disk()->put($path, $binary);

        $this->content->setImage($landing, $key, $path);

        if (is_string($old) && $old !== '' && $old !== $path) {
            $this->disk()->delete($old);
        }

        return $this->disk()->url($path);
    }

    public function remove(Landing $landing, string $key): void
    {
        $old = data_get($landing->settings, 'images.' . $key);

        $this->content->setImage($landing, $key, null);

        if (is_string($old) && $old !== '') {
            $this->disk()->delete($old);
        }
    }

    public function removeAll(Landing $landing): void
    {
        $this->disk()->deleteDirectory('landings/' . $landing->id);
    }

    /** @return array{0: string, 1: string} binary and file extension */
    private function encode(UploadedFile $file): array
    {
        $bytes = (string) file_get_contents($file->getRealPath());

        if (! function_exists('imagecreatefromstring')) {
            return [$bytes, $this->extensionFor((string) $file->getMimeType())];
        }

        $image = @imagecreatefromstring($bytes);

        if (! $image) {
            throw ValidationException::withMessages(['file' => __('landings.editor.errors.image_type')]);
        }

        $image = $this->orient($image, $bytes);
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest > self::MAX_SIDE) {
            $scaled = imagescale($image, (int) round($width * self::MAX_SIDE / $longest), -1, IMG_BICUBIC);

            if ($scaled) {
                $image = $scaled;
            }
        }

        ob_start();

        if (function_exists('imagewebp')) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagewebp($image, null, 82);
            $extension = 'webp';
        } else {
            $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
            imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            imagejpeg($flat, null, 84);
            $extension = 'jpg';
        }

        $binary = (string) ob_get_clean();

        return [$binary, $extension];
    }

    /** Phone photos are stored rotated by an EXIF flag; apply it before it is dropped. */
    private function orient(\GdImage $image, string $bytes): \GdImage
    {
        if (! function_exists('exif_read_data') || ! function_exists('imagerotate')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($bytes));
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle ? (imagerotate($image, $angle, 0) ?: $image) : $image;
    }

    private function extensionFor(string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
