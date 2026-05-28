<?php

namespace App\Services;

use Illuminate\Http\Request;

class BotDetector
{
    /**
     * Patterns User-Agent courants (crawlers, outils, monitoring).
     *
     * @var list<string>
     */
    private const PATTERNS = [
        'bot',
        'crawl',
        'spider',
        'slurp',
        'mediapartners',
        'googlebot',
        'bingbot',
        'yandex',
        'duckduckbot',
        'baiduspider',
        'facebookexternalhit',
        'linkedinbot',
        'twitterbot',
        'whatsapp',
        'telegrambot',
        'discordbot',
        'applebot',
        'semrush',
        'ahrefs',
        'mj12bot',
        'dotbot',
        'petalbot',
        'gptbot',
        'claudebot',
        'bytespider',
        'headlesschrome',
        'phantomjs',
        'selenium',
        'puppeteer',
        'playwright',
        'curl/',
        'wget/',
        'python-requests',
        'python-urllib',
        'go-http-client',
        'java/',
        'libwww',
        'scrapy',
        'httpclient',
        'okhttp',
        'postman',
        'insomnia',
        'uptimerobot',
        'pingdom',
        'gtmetrix',
        'lighthouse',
        'prerender',
    ];

    public function isBot(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return true;
        }

        $ua = strtolower($userAgent);

        foreach (self::PATTERNS as $pattern) {
            if (str_contains($ua, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function isBotRequest(Request $request): bool
    {
        return $this->isBot($request->userAgent());
    }
}
