<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Attribution;

use Spaceworks\Kit\Enums\Channel;

/**
 * Sorts a first touch into a marketing channel using the rules in
 * config/attribution.php. The first matching rule wins: AI assistant, paid,
 * email, social, organic search, referral, direct.
 */
class ChannelClassifier
{
    public function classify(AttributionData $data): Channel
    {
        $medium = $this->lower($data->utmMedium);
        $source = $this->lower($data->utmSource);
        $host = $data->referrerHost();

        return match (true) {
            $this->aiAssistant($data) !== null => Channel::AiAssistant,
            $this->inList($medium, 'attribution.paid_mediums') => Channel::Paid,
            $this->inList($medium, 'attribution.email_mediums') => Channel::Email,
            $this->inList($medium, 'attribution.social_mediums'),
            $this->inList($source, 'attribution.social_sources'),
            $this->matchesHost($source, $this->hosts('attribution.social_networks')),
            $this->matchesHost($host, $this->hosts('attribution.social_networks')) => Channel::Social,
            $this->inList($medium, 'attribution.organic_mediums'),
            $this->matchesHost($host, $this->hosts('attribution.search_engines')) => Channel::Organic,
            $host !== null, $source !== null => Channel::Referral,
            default => Channel::Direct,
        };
    }

    /**
     * The AI assistant's name when utm_source or the referrer is one of the
     * configured assistants (by host, or by name in utm_source).
     */
    public function aiAssistant(AttributionData $data): ?string
    {
        /** @var array<string, string> $assistants */
        $assistants = config('attribution.ai_assistants', []);
        $source = $this->lower($data->utmSource);

        foreach ([$source, $data->referrerHost()] as $candidate) {
            foreach ($assistants as $host => $name) {
                if ($candidate !== null && ($this->hostMatches($candidate, $host) || $candidate === mb_strtolower($name))) {
                    return $name;
                }
            }
        }

        return null;
    }

    private function inList(?string $value, string $configKey): bool
    {
        return $value !== null && in_array($value, (array) config($configKey, []), true);
    }

    /**
     * @return list<string>
     */
    private function hosts(string $configKey): array
    {
        return array_values((array) config($configKey, []));
    }

    /**
     * @param  list<string>  $patterns
     */
    private function matchesHost(?string $host, array $patterns): bool
    {
        if ($host === null) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($this->hostMatches($host, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function hostMatches(string $host, string $pattern): bool
    {
        $host = AttributionData::normaliseHost($host);
        $pattern = mb_strtolower($pattern);

        if (str_ends_with($pattern, '.*')) {
            $base = preg_quote(substr($pattern, 0, -2), '/');

            return preg_match('/(^|\.)'.$base.'(\.[a-z]{2,})+$/', $host) === 1;
        }

        return $host === $pattern || str_ends_with($host, '.'.$pattern);
    }

    private function lower(?string $value): ?string
    {
        return $value === null ? null : mb_strtolower($value);
    }
}
