<?php

namespace App\Services\Prospects;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Resolves a photo reference from a prospect payload into a file the landing
 * image store accepts: a URL on an allowed host is downloaded, a relative path
 * is looked up inside the unpacked zip. Anything else is skipped.
 */
class ProspectPhotos
{
    /** @var array<int, string> */
    private array $temporary = [];

    public function resolve(mixed $reference, ?string $baseDir = null): ?UploadedFile
    {
        if (! is_string($reference) || trim($reference) === '') {
            return null;
        }

        $reference = trim($reference);

        return Str::startsWith($reference, ['http://', 'https://'])
            ? $this->download($reference)
            : $this->local($reference, $baseDir);
    }

    /** Deletes the downloaded copies once the store has re-encoded them. */
    public function cleanup(): void
    {
        foreach ($this->temporary as $path) {
            @unlink($path);
        }

        $this->temporary = [];
    }

    public function allowedHost(string $url): bool
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '' || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return false;
        }

        foreach ((array) config('prospects.photo_hosts', []) as $allowed) {
            $allowed = Str::lower(trim((string) $allowed));

            if ($allowed !== '' && ($host === $allowed || Str::endsWith($host, '.' . $allowed))) {
                return true;
            }
        }

        return false;
    }

    private function download(string $url): ?UploadedFile
    {
        if (! $this->allowedHost($url)) {
            Log::info('Prospect photo skipped: host not allowed.', ['url' => $url]);

            return null;
        }

        try {
            $response = Http::timeout(20)->withOptions(['allow_redirects' => false])->get($url);
        } catch (\Throwable $exception) {
            Log::info('Prospect photo download failed.', ['url' => $url, 'error' => $exception->getMessage()]);

            return null;
        }

        $body = $response->successful() ? $response->body() : '';

        if ($body === '' || strlen($body) > (int) config('prospects.photo_max_bytes')) {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'prospect');
        file_put_contents($path, $body);
        $this->temporary[] = $path;

        return $this->asUpload($path);
    }

    private function local(string $relative, ?string $baseDir): ?UploadedFile
    {
        if ($baseDir === null || str_contains($relative, '..')) {
            return null;
        }

        $root = realpath($baseDir);
        $path = realpath($baseDir . DIRECTORY_SEPARATOR . ltrim($relative, '/\\'));

        if (! $root || ! $path || ! Str::startsWith($path, $root) || ! is_file($path)) {
            return null;
        }

        if (filesize($path) > (int) config('prospects.photo_max_bytes')) {
            return null;
        }

        return $this->asUpload($path);
    }

    private function asUpload(string $path): ?UploadedFile
    {
        $info = @getimagesize($path);

        if (! $info) {
            return null;
        }

        return new UploadedFile($path, basename($path), $info['mime'], null, true);
    }
}
