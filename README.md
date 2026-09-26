# spaceworks/kit

Shared code for the Spaceworks sites (Ryzan, LAVLOSS, Spaceworks):

- **SEO** (`Spaceworks\Kit\Seo`): `SiteSeoDefaults`, `PageSeo`, `SiteSchema`, `EntityGraph` and schema builders Laravel Head lacks (`SoftwareApplication`, `HowTo`, `AggregateRating`, `SearchAction`). Every site points at one Spaceworks `Organization` (`https://spaceworks.dev/#organization`).
- **Attribution** (`Spaceworks\Kit\Attribution`): `CaptureAttribution` first-touch cookie middleware, `Attribution::leadAttributes()`, `ChannelClassifier` and the `Spaceworks\Kit\Enums\Channel` enum.

Plan: `~/work/agencywebsite/docs/spaceworks-platform-plan.md` (Phase 2 created this package; Phase 7 adds lead forwarding to the hub).

## Install

Requires Laravel 13.17+ and `laravel/head`.

```json
"repositories": [{ "type": "path", "url": "../spaceworks-kit", "options": { "symlink": true } }]
```

```bash
composer require spaceworks/kit:@dev
```

(Production: switch the repository to `{"type": "vcs", "url": "git@github.com:<owner>/spaceworks-kit.git"}` and require a tagged version.)

The service provider is auto-discovered. It merges the default `seo` and `attribution` config and registers the `Head::defaults()` from `config('seo.defaults')`.

1. Create `config/seo.php` with the site's own keys only (`site`, `title`, `brand`, `website`, `software`, `breadcrumbs`, …). Top-level keys you define replace the kit's; keys you leave out (e.g. `organization`) come from the kit. `php artisan vendor:publish --tag=spaceworks-kit-config` copies the full defaults.
2. Append the middleware to the web group in `bootstrap/app.php`:
   ```php
   $middleware->web(append: [\Spaceworks\Kit\Attribution\CaptureAttribution::class]);
   ```
3. Store lead attribution: `app(Attribution::class)->leadAttributes($request, $request->only(AttributionData::UTM_KEYS))`.
4. OG images: `PageSeo::ogImageUrl()` uses the route named in `seo.open_graph.image_route` (`og.image`), which the site provides.

## Tests

```bash
composer install && vendor/bin/pest
```
