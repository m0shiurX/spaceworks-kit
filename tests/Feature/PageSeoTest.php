<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Head\Facades\Head;
use Spaceworks\Kit\Seo\EntityGraph;
use Spaceworks\Kit\Seo\PageSeo;
use Spaceworks\Kit\Seo\SiteSeoDefaults;

function pageSeoFor(string $uri = '/'): PageSeo
{
    return new PageSeo(config(), Request::create($uri), new EntityGraph(config()));
}

beforeEach(function () {
    Route::get('og/{slug}.png', fn () => '')->where('slug', '[A-Za-z0-9_\-\/]+')->name('og.image');

    config([
        'app.url' => 'http://acme.test',
        'seo.site.name' => 'Acme App',
        'seo.title.suffix' => ' - Acme',
        'seo.title.brand' => 'Acme',
        'seo.open_graph.type' => 'website',
        'seo.open_graph.image_width' => 1200,
        'seo.open_graph.image_height' => 630,
        'seo.twitter.card' => 'summary_large_image',
    ]);

    app(SiteSeoDefaults::class)->apply();
});

test('titles that already name the brand skip the suffix', function () {
    pageSeoFor()->title('Pricing | Acme');

    expect(Head::toArray()['title'])->toBe('Pricing | Acme');
});

test('titles without the brand get the suffix', function () {
    pageSeoFor()->title('Pricing');

    expect(Head::toArray()['title'])->toBe('Pricing - Acme');
});

test('meta skips an empty description', function () {
    pageSeoFor()->meta('Pricing', '');

    expect(Head::toArray()['description'])->toBeNull();
});

test('social tags describe the current url on the app url without its query string', function () {
    $seo = pageSeoFor('/pricing?utm_source=chatgpt.com');

    $seo->social('Pricing | Acme', 'One plan.', $seo->ogImageUrl('pricing', ['title' => 'One plan | all in', 'eyebrow' => null]));

    $head = Head::toArray();
    $image = 'http://acme.test/og/pricing.png?title=One+plan+%7C+all+in';

    expect($head['canonical'])->toBe('http://acme.test/pricing')
        ->and($head['openGraph'])->toMatchArray([
            'type' => 'website',
            'title' => 'Pricing | Acme',
            'description' => 'One plan.',
            'url' => 'http://acme.test/pricing',
            'site_name' => 'Acme App',
            'images' => [['url' => $image, 'width' => 1200, 'height' => 630]],
        ])
        ->and($head['twitter'])->toMatchArray([
            'card' => 'summary_large_image',
            'title' => 'Pricing | Acme',
            'image' => ['url' => $image],
        ]);
});

test('the home page canonical keeps its trailing slash', function () {
    pageSeoFor('/')->social('Acme');

    expect(Head::toArray()['canonical'])->toBe('http://acme.test/');
});

test('social tags without an image use the summary twitter card', function () {
    pageSeoFor('/about')->social('About Acme');

    expect(Head::toArray()['twitter']['card'])->toBe('summary')
        ->and(Head::toArray()['openGraph'])->not->toHaveKey('images');
});

test('social cards use the og image of the current path', function () {
    pageSeoFor('/features/inventory?utm_source=x')->socialCard('Inventory | Acme', 'Stock.', 'Inventory module', 'Features');

    $head = Head::toArray();
    $image = 'http://acme.test/og/features/inventory.png?title=Inventory+module&eyebrow=Features';

    expect($head['canonical'])->toBe('http://acme.test/features/inventory')
        ->and($head['openGraph']['title'])->toBe('Inventory | Acme')
        ->and($head['openGraph']['images'])->toBe([['url' => $image, 'width' => 1200, 'height' => 630]])
        ->and($head['twitter'])->toMatchArray(['card' => 'summary_large_image', 'image' => ['url' => $image]]);
});

test('social cards default the image title to the page title and use the home slug on /', function () {
    $seo = pageSeoFor('/');

    $seo->socialCard('Acme home');

    expect($seo->ogImageSlug())->toBe('home')
        ->and(Head::toArray()['openGraph']['images'][0]['url'])->toBe('http://acme.test/og/home.png?title=Acme+home');
});

test('faq schema lists every question with its answer', function () {
    $schema = pageSeoFor()->faq([
        ['question' => 'Is it free?', 'answer' => 'No.'],
        ['question' => 'Can I cancel?', 'answer' => 'Anytime.'],
    ]);

    expect($schema->toJsonLd())->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            ['@type' => 'Question', 'name' => 'Is it free?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No.']],
            ['@type' => 'Question', 'name' => 'Can I cancel?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Anytime.']],
        ],
    ]);
});

test('breadcrumbs share the trail of the current path and describe it as schema on the site url', function () {
    config([
        'seo.site.url' => 'https://acme.example',
        'seo.breadcrumbs.home' => 'Start',
        'seo.breadcrumbs.labels' => ['hr' => 'HR'],
    ]);

    pageSeoFor('/features/hr/leave-policy?ref=nav')->breadcrumbs();

    $trail = [
        ['label' => 'Start', 'href' => '/'],
        ['label' => 'Features', 'href' => '/features'],
        ['label' => 'HR', 'href' => '/features/hr'],
        ['label' => 'Leave Policy', 'href' => '/features/hr/leave-policy'],
    ];

    expect(Inertia::getShared('breadcrumbs'))->toBe($trail)
        ->and(Head::toArray()['schemas'][0])->toBe([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Start', 'item' => 'https://acme.example/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Features', 'item' => 'https://acme.example/features'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => 'HR', 'item' => 'https://acme.example/features/hr'],
                ['@type' => 'ListItem', 'position' => 4, 'name' => 'Leave Policy', 'item' => 'https://acme.example/features/hr/leave-policy'],
            ],
        ]);
});

test('breadcrumbs keep absolute crumb urls', function () {
    pageSeoFor('/docs')->breadcrumbs([
        ['label' => 'Home', 'href' => '/'],
        ['label' => 'Docs', 'href' => 'https://docs.acme.example/'],
    ]);

    expect(Head::toArray()['schemas'][0]['itemListElement'][1]['item'])->toBe('https://docs.acme.example/');
});

test('the home page has an empty breadcrumb trail and no breadcrumb schema', function () {
    pageSeoFor('/')->breadcrumbs();

    expect(Inertia::getShared('breadcrumbs'))->toBe([])
        ->and(Head::toArray()['schemas'])->toBeEmpty();
});
