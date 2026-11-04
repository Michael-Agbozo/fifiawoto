<?php

namespace App\Support;

/**
 * Recognises YouTube and Google Drive share links pasted into the media
 * library and turns them into addresses the gallery can embed or display.
 *
 * Drive files must be shared as "Anyone with the link" to show on the site.
 */
class MediaLink
{
    private function __construct(
        public readonly string $provider,
        public readonly string $id,
    ) {}

    public static function parse(?string $url): ?self
    {
        if (blank($url) || ! preg_match('#^https?://#i', $url)) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            return self::make('youtube', ltrim($path, '/'));
        }

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            if (preg_match('#^/(?:embed|shorts|live|v)/([^/?]+)#', $path, $match)) {
                return self::make('youtube', $match[1]);
            }

            return self::make('youtube', (string) ($query['v'] ?? ''));
        }

        if ($host === 'drive.google.com' || $host === 'docs.google.com') {
            if (preg_match('#/file/d/([^/?]+)#', $path, $match)) {
                return self::make('drive', $match[1]);
            }

            return self::make('drive', (string) ($query['id'] ?? ''));
        }

        return null;
    }

    private static function make(string $provider, string $id): ?self
    {
        $pattern = $provider === 'youtube' ? '/^[A-Za-z0-9_-]{11}$/' : '/^[A-Za-z0-9_-]{10,}$/';

        return preg_match($pattern, $id) ? new self($provider, $id) : null;
    }

    public function isYouTube(): bool
    {
        return $this->provider === 'youtube';
    }

    /** Address to play the item inside the page's viewer. */
    public function embedUrl(): string
    {
        return $this->isYouTube()
            ? 'https://www.youtube-nocookie.com/embed/'.$this->id.'?rel=0'
            : 'https://drive.google.com/file/d/'.$this->id.'/preview';
    }

    /** A still image of the item, sized for gallery tiles or the full-screen viewer. */
    public function imageUrl(int $width = 800): string
    {
        return $this->isYouTube()
            ? 'https://i.ytimg.com/vi/'.$this->id.'/hqdefault.jpg'
            : 'https://drive.google.com/thumbnail?id='.$this->id.'&sz=w'.$width;
    }

    public function label(): string
    {
        return $this->isYouTube() ? 'YouTube video' : 'Google Drive file';
    }
}
