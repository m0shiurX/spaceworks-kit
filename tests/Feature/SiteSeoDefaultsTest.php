<?php

declare(strict_types=1);

use Laravel\Head\HeadBuilder;
use Laravel\Head\HeadManager;
use Laravel\Head\Rendering\HeadRenderer;
use Laravel\Head\TagRegistry;
use Spaceworks\Kit\Seo\SiteSeoDefaults;

/**
 * A fresh head manager carrying only the SiteSeoDefaults built from the current config.
 */
function siteSeoDefaultsHead(): HeadManager
{
    return (new HeadManager(app(), app(HeadRenderer::class), app(TagRegistry::class)))
        ->defaults(fn (HeadBuilder $head): HeadBuilder => app(SiteSeoDefaults::class)->configure($head));
}

test('the default config renders no site-wide tags', function () {
    expect(siteSeoDefaultsHead()->toElements())->toBe([]);
});

test('page titles set through Laravel Head get the configured suffix', function () {
    config(['seo.title.suffix' => ' - Acme']);

    $elements = siteSeoDefaultsHead()->title('Pricing')->toElements();

    expect($elements)->toBe(['<title>Pricing - Acme</title>']);
});

test('exact page titles skip the configured suffix', function () {
    config(['seo.title.suffix' => ' - Acme']);

    expect(siteSeoDefaultsHead()->title('Acme', exact: true)->toElements())->toBe(['<title>Acme</title>']);
});

test('enabled defaults render the site-wide tags from config', function () {
    config([
        'seo.site.name' => 'Acme App',
        'seo.title.default' => 'Acme',
        'seo.title.suffix' => ' - Acme',
        'seo.open_graph.type' => 'website',
        'seo.twitter.card' => 'summary_large_image',
        'seo.defaults' => [
            'title' => true,
            'canonical' => true,
            'open_graph' => true,
            'twitter' => true,
            'robots' => 'index, follow',
        ],
    ]);

    $head = siteSeoDefaultsHead()->toArray();

    expect($head['title'])->toBe('Acme')
        ->and($head['canonical'])->toStartWith('https://')
        ->and($head['robots'])->toBe('index, follow')
        ->and($head['openGraph'])->toMatchArray(['type' => 'website', 'site_name' => 'Acme App'])
        ->and($head['twitter'])->toMatchArray(['card' => 'summary_large_image']);
});
