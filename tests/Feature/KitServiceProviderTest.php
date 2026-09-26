<?php

declare(strict_types=1);

use Spaceworks\Kit\Seo\SiteSchema;

test('the kit merges its default seo and attribution config', function () {
    expect(config('seo.organization.id'))->toBe('https://spaceworks.dev/#organization')
        ->and(config('seo.organization.name'))->toBe('Spaceworks')
        ->and(config('seo.breadcrumbs.prop'))->toBe('breadcrumbs')
        ->and(config('attribution.cookie.name'))->toBe('sw_attr')
        ->and(config('attribution.ai_assistants'))->toHaveKey('chatgpt.com', 'ChatGPT');
});

test('every site names the spaceworks organization as publisher by default', function () {
    expect(app(SiteSchema::class)->publisher()->toJsonLd())->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        '@id' => 'https://spaceworks.dev/#organization',
        'name' => 'Spaceworks',
        'url' => 'https://spaceworks.dev',
    ]);
});
