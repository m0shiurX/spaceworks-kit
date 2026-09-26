<?php

declare(strict_types=1);

use Spaceworks\Kit\Seo\SiteSchema;

beforeEach(function () {
    config([
        'seo.site' => ['name' => 'Acme App', 'url' => 'https://acme.test'],
        'seo.graph.link_ids' => false,
        'seo.organization' => [
            'id' => 'https://parent.test/#organization',
            'name' => 'Parent Co',
            'url' => 'https://parent.test',
            'logo' => 'https://parent.test/logo.png',
            'same_as' => ['https://social.test/parent'],
        ],
        'seo.brand' => [
            'id' => null,
            'name' => 'Acme',
            'url' => 'https://acme.test',
            'logo' => 'https://acme.test/logo.png',
            'same_as' => ['https://social.test/acme'],
        ],
        'seo.website' => ['id' => null, 'search_path' => '/search?q={search_term_string}'],
        'seo.software' => [
            'id' => null,
            'name' => 'Acme App',
            'operating_system' => 'Web',
            'application_category' => 'BusinessApplication',
            'offers' => [
                'currency' => 'USD',
                'plans' => [
                    ['name' => 'Monthly', 'price' => '10'],
                    ['name' => 'Annual', 'price' => '100'],
                ],
            ],
            'rating' => ['value' => '4.5', 'count' => '20'],
        ],
    ]);
});

test('the organization is built from config and names the site brand', function () {
    expect(app(SiteSchema::class)->organization()->toJsonLd())->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Parent Co',
        'url' => 'https://parent.test',
        'logo' => 'https://parent.test/logo.png',
        'sameAs' => ['https://social.test/parent'],
        'brand' => [
            '@type' => 'Brand',
            'name' => 'Acme',
            'url' => 'https://acme.test',
            'logo' => 'https://acme.test/logo.png',
            'sameAs' => ['https://social.test/acme'],
        ],
    ]);
});

test('the organization and brand omit an empty logo and profile list', function () {
    config([
        'seo.organization.logo' => null,
        'seo.organization.same_as' => [],
        'seo.brand.logo' => null,
        'seo.brand.same_as' => [],
    ]);

    $organization = app(SiteSchema::class)->organization()->toJsonLd();

    expect($organization)->not->toHaveKeys(['logo', 'sameAs']);
    expect($organization['brand'])->not->toHaveKeys(['logo', 'sameAs']);
});

test('the website carries a search action on the site url and names its publisher', function () {
    expect(app(SiteSchema::class)->webSite()->toJsonLd())->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Acme App',
        'url' => 'https://acme.test',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => 'https://acme.test/search?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Parent Co',
            'url' => 'https://parent.test',
            'logo' => 'https://parent.test/logo.png',
        ],
    ]);
});

test('the website has no search action without a search path', function () {
    config(['seo.website.search_path' => null]);

    expect(app(SiteSchema::class)->webSite()->toJsonLd())->not->toHaveKey('potentialAction');
});

test('the software application is priced at its first plan, with its brand and publisher', function () {
    expect(app(SiteSchema::class)->softwareApplication()->toJsonLd())->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Acme App',
        'operatingSystem' => 'Web',
        'applicationCategory' => 'BusinessApplication',
        'brand' => [
            '@type' => 'Brand',
            'name' => 'Acme',
            'url' => 'https://acme.test',
            'logo' => 'https://acme.test/logo.png',
            'sameAs' => ['https://social.test/acme'],
        ],
        'offers' => ['@type' => 'Offer', 'price' => '10', 'priceCurrency' => 'USD'],
        'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => '4.5', 'ratingCount' => '20'],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Parent Co',
            'url' => 'https://parent.test',
            'logo' => 'https://parent.test/logo.png',
        ],
    ]);
});

test('the software application omits offers and rating when none are configured', function () {
    config(['seo.software.offers.plans' => [], 'seo.software.rating' => ['value' => null, 'count' => null]]);

    expect(app(SiteSchema::class)->softwareApplication()->toJsonLd())
        ->not->toHaveKeys(['offers', 'aggregateRating']);
});

test('entity nodes carry their graph ids when linking is enabled', function () {
    config(['seo.graph.link_ids' => true]);

    $schema = app(SiteSchema::class);
    $organization = $schema->organization()->toJsonLd();

    expect($organization['@id'])->toBe('https://parent.test/#organization');
    expect($organization['brand']['@id'])->toBe('https://acme.test/#brand');
    expect($schema->webSite()->toJsonLd()['@id'])->toBe('https://acme.test/#website');
    expect($schema->softwareApplication()->toJsonLd()['@id'])->toBe('https://acme.test/#software');
});

test('every publisher points at the one organization id when linking is enabled', function () {
    config(['seo.graph.link_ids' => true]);

    $schema = app(SiteSchema::class);

    expect([
        $schema->webSite()->toJsonLd()['publisher']['@id'],
        $schema->softwareApplication()->toJsonLd()['publisher']['@id'],
        $schema->article('Hello')->toJsonLd()['publisher']['@id'],
    ])->each->toBe('https://parent.test/#organization');
});

test('the organization is the root of the graph and has no publisher', function () {
    config(['seo.graph.link_ids' => true]);

    expect(app(SiteSchema::class)->organization()->toJsonLd())->not->toHaveKey('publisher');
});

test('the product lists one offer per plan on the pricing url', function () {
    config([
        'seo.software.description' => 'All-in-one app.',
        'seo.software.offers.pricing_path' => '/pricing',
        'seo.software.offers.availability' => 'InStock',
    ]);

    $offer = fn (string $name, string $price): array => [
        '@type' => 'Offer',
        'name' => $name,
        'price' => $price,
        'priceCurrency' => 'USD',
        'url' => 'https://acme.test/pricing',
        'availability' => 'https://schema.org/InStock',
    ];

    expect(app(SiteSchema::class)->product()->toJsonLd())->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => 'Acme App',
        'description' => 'All-in-one app.',
        'brand' => [
            '@type' => 'Brand',
            'name' => 'Acme',
            'url' => 'https://acme.test',
            'logo' => 'https://acme.test/logo.png',
            'sameAs' => ['https://social.test/acme'],
        ],
        'offers' => [$offer('Monthly', '10'), $offer('Annual', '100')],
        'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => '4.5', 'ratingCount' => '20'],
    ]);
});

test('product offers omit an unknown availability', function () {
    config(['seo.software.offers.availability' => 'Whenever']);

    expect(app(SiteSchema::class)->product()->toJsonLd()['offers'][0])->not->toHaveKey('availability');
});

test('the article is published by the organization', function () {
    $article = app(SiteSchema::class)->article(
        headline: 'Hello',
        description: 'A post.',
        image: 'https://img.test/hello.webp',
        publishedAt: '2026-01-02T00:00:00+00:00',
        author: 'Acme Team',
        section: 'News',
    );

    expect($article->toJsonLd())->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'Hello',
        'description' => 'A post.',
        'image' => ['https://img.test/hello.webp'],
        'datePublished' => '2026-01-02T00:00:00+00:00',
        'author' => ['@type' => 'Organization', 'name' => 'Acme Team'],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Parent Co',
            'url' => 'https://parent.test',
            'logo' => 'https://parent.test/logo.png',
        ],
        'articleSection' => 'News',
    ]);
});

test('the article omits empty optional values', function () {
    expect(app(SiteSchema::class)->article('Hello', '', '', null, '', '')->toJsonLd())
        ->toHaveKeys(['@context', '@type', 'headline', 'publisher'])
        ->not->toHaveKeys(['description', 'image', 'datePublished', 'author', 'articleSection']);
});
