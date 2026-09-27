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
use Spaceworks\Kit\Seo\Schema\ContactPoint;
use Spaceworks\Kit\Seo\Schema\PostalAddress;
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
     * The organization with its image, profiles, address and contact details
     * (each only when configured) and the site's brand.
     */
    public function organization(): Organization
    {
        $organization = $this->publisher();

        if ($image = $this->string('seo.organization.image')) {
            $organization->set('image', $image);
        }

        if ($sameAs = $this->sameAs('seo.organization.same_as')) {
            $organization->set('sameAs', $sameAs);
        }

        if ($address = $this->organizationAddress()) {
            $organization->set('address', $address);
        }

        if ($telephone = $this->string('seo.organization.telephone')) {
            $organization->set('telephone', $telephone);
        }

        if ($email = $this->string('seo.organization.email')) {
            $organization->set('email', $email);
        }

        if ($contactPoint = $this->organizationContactPoint()) {
            $organization->set('contactPoint', $contactPoint);
        }

        return $organization->set('brand', $this->brand());
    }

    /**
     * The organization's postal address, or null when no part is configured.
     */
    public function organizationAddress(): ?PostalAddress
    {
        $parts = [
            'streetAddress' => $this->string('seo.organization.address.street'),
            'addressLocality' => $this->string('seo.organization.address.locality'),
            'addressRegion' => $this->string('seo.organization.address.region'),
            'postalCode' => $this->string('seo.organization.address.postal_code'),
            'addressCountry' => $this->string('seo.organization.address.country'),
        ];

        $parts = array_filter($parts, fn (string $part): bool => $part !== '');

        if ($parts === []) {
            return null;
        }

        $address = new PostalAddress;

        foreach ($parts as $property => $value) {
            $address->set($property, $value);
        }

        return $address;
    }

    /**
     * The organization's contact point, or null when it has no telephone or
     * email.
     */
    public function organizationContactPoint(): ?ContactPoint
    {
        $telephone = $this->string('seo.organization.contact_point.telephone');
        $email = $this->string('seo.organization.contact_point.email');

        if ($telephone === '' && $email === '') {
            return null;
        }

        $contactPoint = new ContactPoint;

        if ($telephone !== '') {
            $contactPoint->telephone($telephone);
        }

        if ($type = $this->string('seo.organization.contact_point.contact_type')) {
            $contactPoint->contactType($type);
        }

        if ($email !== '') {
            $contactPoint->email($email);
        }

        if ($areaServed = $this->string('seo.organization.contact_point.area_served')) {
            $contactPoint->areaServed($areaServed);
        }

        if ($languages = array_values(array_filter((array) $this->config->get('seo.organization.contact_point.available_language')))) {
            $contactPoint->availableLanguage($languages);
        }

        return $contactPoint;
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
            ->applicationCategory($this->string('seo.software.application_category'));

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
