<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use Spaceworks\Kit\Attribution\CaptureAttribution;

use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;
use function Pest\Laravel\withCookie;
use function Pest\Laravel\withHeaders;

beforeEach(function () {
    Route::middleware(['web', CaptureAttribution::class])->group(function () {
        Route::get('/pricing', fn () => response('<html><body>Pricing</body></html>'));
        Route::get('/llms.txt', fn () => response('# Acme', headers: ['Content-Type' => 'text/plain']));
    });
});

test('the first page load stores the first touch for 90 days', function () {
    travelTo(CarbonImmutable::parse('2026-09-25 10:00:00'));

    $response = withHeaders(['Referer' => 'https://www.perplexity.ai/search/erp?q=1'])
        ->get('/pricing?utm_source=newsletter&utm_medium=email&utm_campaign=launch&utm_content=hero&utm_term=erp')
        ->assertOk()
        ->assertCookie('sw_attr');

    $cookie = collect($response->headers->getCookies())->firstWhere(fn ($cookie) => $cookie->getName() === 'sw_attr');
    expect($cookie->getExpiresTime())->toBe(CarbonImmutable::now()->addDays(90)->getTimestamp())
        ->and($cookie->isHttpOnly())->toBeTrue();

    expect(json_decode($response->getCookie('sw_attr')->getValue(), true))->toBe([
        'landing_page' => '/pricing?utm_source=newsletter&utm_medium=email&utm_campaign=launch&utm_content=hero&utm_term=erp',
        'referrer' => 'perplexity.ai/search/erp',
        'utm_source' => 'newsletter',
        'utm_medium' => 'email',
        'utm_campaign' => 'launch',
        'utm_content' => 'hero',
        'utm_term' => 'erp',
        'first_seen_at' => '2026-09-25T10:00:00+00:00',
    ]);
});

test('the cookie is encrypted', function () {
    $response = get('/pricing?utm_source=x');

    expect($response->getCookie('sw_attr', decrypt: false)->getValue())->not->toContain('utm_source');
});

test('an existing first touch is never overwritten', function () {
    withCookie('sw_attr', json_encode(['landing_page' => '/', 'utm_source' => 'chatgpt.com']))
        ->get('/pricing?utm_source=google&utm_medium=cpc')
        ->assertOk()
        ->assertCookieMissing('sw_attr');
});

test('overlong values are capped so the encrypted cookie stays under 4 KB', function () {
    $long = str_repeat('ক', 1000);

    $response = withHeaders(['Referer' => 'https://example.com/'.str_repeat('p', 1000)])
        ->get('/pricing?'.http_build_query(array_fill_keys(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'], $long)))
        ->assertOk();

    $values = json_decode($response->getCookie('sw_attr')->getValue(), true);

    expect(strlen($values['utm_campaign']))->toBeLessThanOrEqual(255)
        ->and(mb_check_encoding($values['utm_campaign'], 'UTF-8'))->toBeTrue()
        ->and(strlen($values['landing_page']))->toBe(255)
        ->and(strlen($values['referrer']))->toBe(255)
        ->and(strlen($response->getCookie('sw_attr', decrypt: false)->getValue()))->toBeLessThan(4096);
});

test('invalid utf-8 values are dropped instead of breaking the cookie', function () {
    $response = get('/pricing?utm_source=%FF&utm_medium=email')->assertOk();

    expect(json_decode($response->getCookie('sw_attr')->getValue(), true))
        ->utm_source->toBeNull()
        ->utm_medium->toBe('email')
        ->landing_page->toBe('/pricing?utm_source=%FF&utm_medium=email');
});

test('internal referrers are not stored', function () {
    $response = withHeaders(['Referer' => url('/features')])->get('/pricing');

    expect(json_decode($response->getCookie('sw_attr')->getValue(), true)['referrer'])->toBeNull();
});

test('inertia visits, xhr, non-html and failed requests do not set the cookie', function (string $uri, array $headers) {
    withHeaders($headers)->get($uri)->assertCookieMissing('sw_attr');
})->with([
    'inertia visit' => ['/pricing', ['X-Inertia' => 'true']],
    'xhr' => ['/pricing', ['X-Requested-With' => 'XMLHttpRequest']],
    'prefetch' => ['/pricing', ['Purpose' => 'prefetch']],
    'not found' => ['/definitely-not-a-page', []],
    'non-html' => ['/llms.txt', []],
]);
