<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Attribution;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * A visitor's first touch: where they landed, who referred them and which
 * UTM tags they arrived with.
 */
final readonly class AttributionData
{
    public const array UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    public function __construct(
        public ?string $landingPage = null,
        public ?string $referrer = null,
        public ?string $utmSource = null,
        public ?string $utmMedium = null,
        public ?string $utmCampaign = null,
        public ?string $utmContent = null,
        public ?string $utmTerm = null,
        public ?CarbonImmutable $firstSeenAt = null,
    ) {}

    /**
     * Capture the first touch from a page request. A referrer on the
     * request's own host is internal navigation and is dropped.
     */
    public static function fromRequest(Request $request): self
    {
        $utm = [];

        foreach (self::UTM_KEYS as $key) {
            $value = $request->query($key);
            $utm[$key] = is_string($value) ? $value : null;
        }

        return self::fromArray([
            'landing_page' => $request->getRequestUri(),
            'referrer' => self::referrerFrom((string) $request->headers->get('referer'), $request->getHost()),
            ...$utm,
            'first_seen_at' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values  keys as in {@see toArray()}
     */
    public static function fromArray(array $values): self
    {
        $firstSeenAt = null;

        if (is_string($values['first_seen_at'] ?? null)) {
            try {
                $firstSeenAt = CarbonImmutable::parse($values['first_seen_at']);
            } catch (Throwable) {
                $firstSeenAt = null;
            }
        }

        return new self(
            landingPage: self::clean($values['landing_page'] ?? null),
            referrer: self::clean($values['referrer'] ?? null),
            utmSource: self::clean($values['utm_source'] ?? null),
            utmMedium: self::clean($values['utm_medium'] ?? null),
            utmCampaign: self::clean($values['utm_campaign'] ?? null),
            utmContent: self::clean($values['utm_content'] ?? null),
            utmTerm: self::clean($values['utm_term'] ?? null),
            firstSeenAt: $firstSeenAt,
        );
    }

    /**
     * Rebuild from the cookie's JSON payload; null when it is not valid.
     */
    public static function fromJson(?string $json): ?self
    {
        if ($json === null || $json === '') {
            return null;
        }

        $values = json_decode($json, true);

        return is_array($values) ? self::fromArray($values) : null;
    }

    /**
     * @return array{landing_page: ?string, referrer: ?string, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, utm_content: ?string, utm_term: ?string, first_seen_at: ?string}
     */
    public function toArray(): array
    {
        return [
            'landing_page' => $this->landingPage,
            'referrer' => $this->referrer,
            'utm_source' => $this->utmSource,
            'utm_medium' => $this->utmMedium,
            'utm_campaign' => $this->utmCampaign,
            'utm_content' => $this->utmContent,
            'utm_term' => $this->utmTerm,
            'first_seen_at' => $this->firstSeenAt?->toIso8601String(),
        ];
    }

    public function toJson(): string
    {
        return (string) json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Replace the UTM tags, keeping landing page, referrer and first seen time.
     *
     * @param  array<string, mixed>  $utm  utm_* keys
     */
    public function withUtm(array $utm): self
    {
        return self::fromArray([...$this->toArray(), ...array_intersect_key($utm, array_flip(self::UTM_KEYS))]);
    }

    /**
     * The referrer's host in lower case, without a leading "www.".
     */
    public function referrerHost(): ?string
    {
        if ($this->referrer === null) {
            return null;
        }

        $host = parse_url('//'.$this->referrer, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? self::normaliseHost($host) : null;
    }

    public static function normaliseHost(string $host): string
    {
        return Str::of($host)->lower()->rtrim('.')->chopStart('www.')->toString();
    }

    /**
     * "host/path" of an external referrer URL (no scheme, query or fragment).
     */
    private static function referrerFrom(string $url, string $ownHost): ?string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host']) || $parts['host'] === '') {
            return null;
        }

        $host = self::normaliseHost($parts['host']);

        if ($host === self::normaliseHost($ownHost)) {
            return null;
        }

        return $host.($parts['path'] ?? '');
    }

    /**
     * Trim the value and cut it to "attribution.max_length" bytes, keeping
     * whole UTF-8 characters, so it fits the lead columns and the cookie.
     */
    private static function clean(mixed $value): ?string
    {
        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8')) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_strcut($value, 0, (int) config('attribution.max_length', 255), 'UTF-8');
    }
}
