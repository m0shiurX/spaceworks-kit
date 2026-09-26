<?php

declare(strict_types=1);

use Spaceworks\Kit\Seo\EntityGraph;

test('entity ids derive from the canonical site url', function () {
    config([
        'seo.site.url' => 'https://acme.test/',
        'seo.organization.id' => null,
        'seo.brand.id' => null,
        'seo.website.id' => null,
        'seo.software.id' => null,
    ]);

    $graph = app(EntityGraph::class);

    expect($graph->siteUrl())->toBe('https://acme.test')
        ->and($graph->url('pricing'))->toBe('https://acme.test/pricing')
        ->and($graph->url())->toBe('https://acme.test/')
        ->and($graph->organizationId())->toBe('https://acme.test/#organization')
        ->and($graph->brandId())->toBe('https://acme.test/#brand')
        ->and($graph->websiteId())->toBe('https://acme.test/#website')
        ->and($graph->softwareApplicationId())->toBe('https://acme.test/#software');
});

test('configured entity ids take precedence', function () {
    config([
        'seo.site.url' => 'https://acme.test',
        'seo.organization.id' => 'https://parent.test/#organization',
        'seo.software.id' => 'https://acme.test/#app',
    ]);

    $graph = app(EntityGraph::class);

    expect($graph->organizationId())->toBe('https://parent.test/#organization')
        ->and($graph->softwareApplicationId())->toBe('https://acme.test/#app')
        ->and($graph->reference($graph->organizationId()))->toBe(['@id' => 'https://parent.test/#organization']);
});
