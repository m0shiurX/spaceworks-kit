<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Seo;

use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Laravel\Head\Enums\OfferAvailability;
use Laravel\Head\Schema\Article;
use Laravel\Head\Schema\Brand;
use Laravel\Head\Schema\Offer;
use Laravel\Head\Schema\Organization;
use Laravel\Head\Schema\Product;
use Laravel\Head\Schema\SchemaObject;
use Laravel\Head\Schema\WebSite;
use Spaceworks\Kit\Seo\Schema\AggregateRating;
use Spaceworks\Kit\Seo\Schema\SearchAction;
use Spaceworks\Kit\Seo\Schema\SoftwareApplication;

/**
 * JSON-LD nodes for the site's own entities, built from config/seo.php and
 * the EntityGraph: the organization (the company behind every site) with the
 * site's brand, the website, the software and its product listing, and
 * articles. The organization publishes the website, software and articles.
 */
class SiteSchema
{
    public function __construct(protected Repository $config, protected EntityGraph $graph) {}

    /**
     * The organization with its profiles and the site's brand.
     */
    public function organization(): Organization
    {
        $organization = $this->publisher();

        if ($sameAs = $this->sameAs('seo.organization.same_as')) {
            $organization->set('sameAs', $sameAs);
        }

        return $organization->set('brand', $this->brand());
    }

    /**
     * The organization as the publisher named by other nodes: its @id, name,
     * url and logo.
     */
    public function publisher(): Organization
    {
        $publisher = $this->identify(new Organization, $this->graph->organizationId())
            ->name($this->string('seo.organization.name'))
            ->url($this->string('seo.organization.url') ?: $this->graph->siteUrl());

        if ($logo = $this->string('seo.organization.logo')) {
            $publisher->logo($logo);
        }

        return $publisher;
    }

    /**
     * The site's product brand, owned by the organization.
     */
    public function brand(): Brand
    {
        $brand = $this->identify(new Brand, $this->graph->brandId())
            ->name($this->string('seo.brand.name'));

        if ($url = $this->string('seo.brand.url')) {
            $brand->url($url);
        }

        if ($logo = $this->string('seo.brand.logo')) {
            $brand->logo($logo);
        }

        if ($sameAs = $this->sameAs('seo.brand.same_as')) {
            $brand->set('sameAs', $sameAs);
        }

        return $brand;
    }

    public function webSite(): WebSite
    {
        $website = $this->identify(new WebSite, $this->graph->websiteId())
            ->name($this->string('seo.site.name'))
            ->url($this->graph->siteUrl());

        if ($searchPath = $this->string('seo.website.search_path')) {
            $website->set('potentialAction', (new SearchAction)
                ->target($this->graph->url($searchPath))
                ->queryInput());
        }

        return $website->set('publisher', $this->publisher());
    }

    /**
     * The software application, priced at its first (headline) plan.
     */
    public function softwareApplication(): SoftwareApplication
    {
        $software = $this->identify(new SoftwareApplication, $this->graph->softwareApplicationId())
            ->name($this->string('seo.software.name'))
            ->operatingSystem($this->string('seo.software.operating_system'))
            ->applicationCategory($this->string('seo.software.application_category'))
            ->brand($this->brand());

        if ($plan = $this->plans()[0] ?? null) {
            $software->offers((new Offer)
                ->price($plan['price'])
                ->currency($this->string('seo.software.offers.currency')));
        }

        if ($rating = $this->aggregateRating()) {
            $software->aggregateRating($rating);
        }

        return $software->set('publisher', $this->publisher());
    }

    /**
     * The software as a Product with one Offer per plan, for pricing pages.
     */
    public function product(): Product
    {
        $product = (new Product)
            ->name($this->string('seo.software.name'))
            ->description($this->string('seo.software.description'))
            ->brand($this->brand());

        $url = $this->graph->url($this->string('seo.software.offers.pricing_path') ?: '/');
        $availability = OfferAvailability::tryFrom($this->string('seo.software.offers.availability'));

        $offers = array_map(function (array $plan) use ($url, $availability): Offer {
            $offer = (new Offer)
                ->name($plan['name'])
                ->price($plan['price'])
                ->currency($this->string('seo.software.offers.currency'))
                ->url($url);

            return $availability ? $offer->availability($availability) : $offer;
        }, $this->plans());

        if ($offers !== []) {
            $product->offers($offers);
        }

        if ($rating = $this->aggregateRating()) {
            $product->set('aggregateRating', $rating);
        }

        return $product;
    }

    /**
     * An Article published by the organization. Empty optional values are
     * left out.
     */
    public function article(
        string $headline,
        ?string $description = null,
        ?string $image = null,
        DateTimeInterface|string|null $publishedAt = null,
        ?string $author = null,
        ?string $section = null,
    ): Article {
        $article = (new Article)->headline($headline);

        if (filled($description)) {
            $article->description($description);
        }

        if (filled($image)) {
            $article->image([$image]);
        }

        if (filled($publishedAt)) {
            $article->publishedAt($publishedAt);
        }

        if (filled($author)) {
            $article->author((new Organization)->name($author));
        }

        $article->set('publisher', $this->publisher());

        if (filled($section)) {
            $article->set('articleSection', $section);
        }

        return $article;
    }

    public function aggregateRating(): ?AggregateRating
    {
        $value = $this->string('seo.software.rating.value');
        $count = $this->string('seo.software.rating.count');

        if ($value === '' || $count === '') {
            return null;
        }

        return (new AggregateRating)->ratingValue($value)->ratingCount($count);
    }

    /**
     * @return list<array{name: string, price: string}>
     */
    protected function plans(): array
    {
        return array_values((array) $this->config->get('seo.software.offers.plans', []));
    }

    /**
     * Give the node its stable @id when entity linking is enabled.
     *
     * @template T of SchemaObject
     *
     * @param  T  $node
     * @return T
     */
    protected function identify(SchemaObject $node, string $id): SchemaObject
    {
        return $this->config->get('seo.graph.link_ids') ? $node->set('@id', $id) : $node;
    }

    /**
     * The non-empty profile URLs at the given config key.
     *
     * @return list<string>
     */
    protected function sameAs(string $key): array
    {
        return array_values(array_filter((array) $this->config->get($key)));
    }

    protected function string(string $key): string
    {
        return (string) $this->config->get($key, '');
    }
}
