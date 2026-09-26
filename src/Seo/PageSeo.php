<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Seo;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Head\Facades\Head;
use Laravel\Head\Schema\Breadcrumbs;
use Laravel\Head\Schema\Faq;
use Laravel\Head\Schema\SchemaObject;

/**
 * Per-page head helpers on top of Laravel Head: titles, descriptions, the
 * canonical/Open Graph/Twitter set, OG image URLs, FAQ schema and the
 * breadcrumb trail.
 */
class PageSeo
{
    public function __construct(protected Repository $config, protected Request $request, protected EntityGraph $graph) {}

    /**
     * Set the page title. Titles that already name the brand render exactly,
     * without the configured suffix.
     */
    public function title(string $title): static
    {
        $brand = (string) $this->config->get('seo.title.brand', '');

        Head::title($title, exact: $brand !== '' && str_contains($title, $brand) ? true : null);

        return $this;
    }

    /**
     * Set the page title and, when given, the meta description.
     */
    public function meta(string $title, ?string $description = null): static
    {
        $this->title($title);

        if (filled($description)) {
            Head::description($description);
        }

        return $this;
    }

    /**
     * Set the canonical link plus the Open Graph and Twitter tags for the
     * current URL. Social titles never carry the title suffix.
     */
    public function social(string $title, ?string $description = null, ?string $image = null, ?string $type = null): static
    {
        $url = $this->currentUrl();
        $description = filled($description) ? $description : null;

        Head::canonical($url, forceHttps: false)
            ->og(
                type: $type ?? $this->config->get('seo.open_graph.type'),
                title: $title,
                description: $description,
                url: $url,
                siteName: $this->config->get('seo.site.name'),
            )
            ->twitter(
                card: $image ? $this->config->get('seo.twitter.card') : 'summary',
                title: $title,
                description: $description,
            );

        if ($image) {
            Head::ogImage(
                $image,
                width: $this->config->get('seo.open_graph.image_width'),
                height: $this->config->get('seo.open_graph.image_height'),
            )->twitterImage($image);
        }

        return $this;
    }

    /**
     * The canonical, Open Graph and Twitter set with the page's generated OG
     * image (see ogImageSlug()). The image shows "imageTitle" (default: the
     * title) under the optional eyebrow.
     */
    public function socialCard(string $title, ?string $description = null, ?string $imageTitle = null, ?string $eyebrow = null, ?string $type = null): static
    {
        return $this->social($title, $description, $this->ogImageUrl($this->ogImageSlug(), [
            'title' => $imageTitle ?? $title,
            'eyebrow' => $eyebrow,
        ]), $type);
    }

    /**
     * The OG image slug of the current page: its path, or "home" for "/".
     */
    public function ogImageSlug(): string
    {
        $path = trim($this->request->path(), '/');

        return $path === '' ? 'home' : $path;
    }

    /**
     * Add JSON-LD nodes to the page, each as its own script block.
     *
     * @param  SchemaObject|array<string, mixed>  ...$schemas
     */
    public function schema(SchemaObject|array ...$schemas): static
    {
        foreach ($schemas as $schema) {
            Head::schema($schema);
        }

        return $this;
    }

    /**
     * The absolute URL of a generated OG image, e.g. ["title" => "…"].
     *
     * @param  array<string, string|null>  $query
     */
    public function ogImageUrl(string $slug, array $query = []): string
    {
        $path = route($this->config->get('seo.open_graph.image_route'), ['slug' => $slug], absolute: false);
        $query = http_build_query(array_filter($query, filled(...)));

        return $this->appUrl($path).($query === '' ? '' : '?'.$query);
    }

    /**
     * A FAQPage node from question/answer pairs.
     *
     * @param  iterable<array{question: string, answer: string}>  $faqs
     */
    public function faq(iterable $faqs): Faq
    {
        $schema = new Faq;

        foreach ($faqs as $faq) {
            $schema->question($faq['question'], $faq['answer']);
        }

        return $schema;
    }

    /**
     * Share a breadcrumb trail with the page (as the configured Inertia prop)
     * and add its BreadcrumbList schema, so the visible trail and the schema
     * come from the same data. Defaults to the trail of the current path.
     * Trails of fewer than two crumbs add no schema.
     *
     * @param  list<array{label: string, href: string}>|null  $trail
     */
    public function breadcrumbs(?array $trail = null): static
    {
        $trail ??= $this->breadcrumbTrail();

        Inertia::share((string) $this->config->get('seo.breadcrumbs.prop', 'breadcrumbs'), $trail);

        if (count($trail) > 1) {
            $schema = new Breadcrumbs;

            foreach ($trail as $crumb) {
                $schema->item($crumb['label'], Str::startsWith($crumb['href'], ['http://', 'https://'])
                    ? $crumb['href']
                    : $this->graph->url($crumb['href']));
            }

            Head::schema($schema);
        }

        return $this;
    }

    /**
     * The breadcrumb trail of the current path: the home crumb, then one crumb
     * per path segment, labelled from "seo.breadcrumbs.labels" or the
     * title-cased segment. The home page has an empty trail.
     *
     * @return list<array{label: string, href: string}>
     */
    public function breadcrumbTrail(): array
    {
        $segments = array_values(array_filter(explode('/', $this->request->path()), fn (string $segment): bool => $segment !== ''));

        if ($segments === []) {
            return [];
        }

        $labels = (array) $this->config->get('seo.breadcrumbs.labels', []);
        $trail = [['label' => (string) $this->config->get('seo.breadcrumbs.home', 'Home'), 'href' => '/']];
        $path = '';

        foreach ($segments as $segment) {
            $path .= '/'.$segment;
            $trail[] = [
                'label' => $labels[$segment] ?? implode(' ', array_map(ucfirst(...), explode('-', $segment))),
                'href' => $path,
            ];
        }

        return $trail;
    }

    /**
     * The current page on the configured app URL, without the query string.
     */
    public function currentUrl(): string
    {
        return $this->appUrl($this->request->path());
    }

    protected function appUrl(string $path): string
    {
        return rtrim((string) $this->config->get('app.url'), '/').'/'.ltrim($path, '/');
    }
}
