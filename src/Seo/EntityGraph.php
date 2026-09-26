<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Seo;

use Illuminate\Contracts\Config\Repository;

/**
 * Stable JSON-LD @ids for the entities every page links to, built from
 * config/seo.php so each site in the graph points at the same nodes.
 */
class EntityGraph
{
    public function __construct(protected Repository $config) {}

    /**
     * The canonical site origin, without a trailing slash.
     */
    public function siteUrl(): string
    {
        return rtrim((string) $this->config->get('seo.site.url'), '/');
    }

    /**
     * An absolute URL on the canonical site origin.
     */
    public function url(string $path = '/'): string
    {
        return $this->siteUrl().'/'.ltrim($path, '/');
    }

    public function organizationId(): string
    {
        return $this->configuredId('seo.organization.id', '#organization');
    }

    public function brandId(): string
    {
        return $this->configuredId('seo.brand.id', '#brand');
    }

    public function websiteId(): string
    {
        return $this->configuredId('seo.website.id', '#website');
    }

    public function softwareApplicationId(): string
    {
        return $this->configuredId('seo.software.id', '#software');
    }

    /**
     * A JSON-LD node reference to the given @id.
     *
     * @return array{'@id': string}
     */
    public function reference(string $id): array
    {
        return ['@id' => $id];
    }

    protected function configuredId(string $key, string $fragment): string
    {
        $id = $this->config->get($key);

        return is_string($id) && $id !== '' ? $id : $this->url('/'.$fragment);
    }
}
