<?php

namespace App\Support;

/**
 * Превращает обычную ссылку на ролик в адрес для iframe.
 *
 * Отзывы на главную добавляют руками, копируя ссылку из адресной строки, —
 * поэтому принимаем то, что реально копируют: страницу ролика, короткую
 * ссылку, Shorts/клипы, а уже готовый embed-адрес пропускаем как есть.
 */
class VideoEmbed
{
    public static function url(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        return self::youtube($url) ?? self::rutube($url) ?? self::vk($url);
    }

    private static function youtube(string $url): ?string
    {
        $pattern = '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i';

        return preg_match($pattern, $url, $m)
            ? 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=1&rel=0'
            : null;
    }

    private static function rutube(string $url): ?string
    {
        return preg_match('~rutube\.ru/(?:video|shorts|play/embed)/([a-f0-9]{20,})~i', $url, $m)
            ? 'https://rutube.ru/play/embed/' . $m[1] . '?autoplay=1'
            : null;
    }

    private static function vk(string $url): ?string
    {
        if (preg_match('~(?:vk\.com|vkvideo\.ru)/video_ext\.php\?~i', $url)) {
            return $url;
        }

        return preg_match('~(?:vk\.com|vkvideo\.ru)/.*?(?:video|clip)(-?\d+)_(\d+)~i', $url, $m)
            ? 'https://vk.com/video_ext.php?oid=' . $m[1] . '&id=' . $m[2] . '&autoplay=1'
            : null;
    }
}
