<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Attribution;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie as CookieFactory;
use Spaceworks\Kit\Enums\Channel;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Reads and writes the first-touch cookie and turns it into lead columns.
 */
class Attribution
{
    public function __construct(private ChannelClassifier $classifier) {}

    public function cookieName(): string
    {
        return (string) config('attribution.cookie.name', 'sw_attr');
    }

    public function hasFirstTouch(Request $request): bool
    {
        return $this->firstTouch($request) !== null;
    }

    /**
     * The first touch stored in the (decrypted) cookie, if any.
     */
    public function firstTouch(Request $request): ?AttributionData
    {
        $value = $request->cookie($this->cookieName());

        return AttributionData::fromJson(is_string($value) ? $value : null);
    }

    public function cookieFor(AttributionData $data): Cookie
    {
        $minutes = (int) config('attribution.cookie.lifetime_days', 90) * 24 * 60;

        return CookieFactory::make($this->cookieName(), $data->toJson(), $minutes);
    }

    /**
     * Column values for a lead record: the cookie's first touch. A visitor
     * without the cookie (cookies blocked, or first seen before it existed)
     * falls back to the UTM tags the form sent. The two are never mixed, so a
     * lead never combines the first visit's referrer with a later visit's
     * tags.
     *
     * @param  array<string, mixed>  $fallbackUtm  utm_* values sent by the form
     * @return array{referrer: ?string, landing_page: ?string, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string, utm_content: ?string, utm_term: ?string, channel: Channel, ai_assistant: ?string}
     */
    public function leadAttributes(Request $request, array $fallbackUtm = []): array
    {
        $data = $this->firstTouch($request) ?? (new AttributionData)->withUtm($fallbackUtm);

        return [
            'referrer' => $data->referrer,
            'landing_page' => $data->landingPage,
            'utm_source' => $data->utmSource,
            'utm_medium' => $data->utmMedium,
            'utm_campaign' => $data->utmCampaign,
            'utm_content' => $data->utmContent,
            'utm_term' => $data->utmTerm,
            'channel' => $this->classifier->classify($data),
            'ai_assistant' => $this->classifier->aiAssistant($data),
        ];
    }
}
