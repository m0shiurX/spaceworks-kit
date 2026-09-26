<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Seo\Schema;

use Laravel\Head\Schema\SchemaObject;
use Laravel\Head\SchemaType;

/**
 * schema.org PostalAddress, which Laravel Head has no builder for.
 */
#[SchemaType('PostalAddress')]
class PostalAddress extends SchemaObject
{
    public function streetAddress(string $street): static
    {
        return $this->set('streetAddress', $street);
    }

    public function addressLocality(string $locality): static
    {
        return $this->set('addressLocality', $locality);
    }

    public function addressRegion(string $region): static
    {
        return $this->set('addressRegion', $region);
    }

    public function postalCode(string $postalCode): static
    {
        return $this->set('postalCode', $postalCode);
    }

    /**
     * An ISO 3166-1 alpha-2 country code or a country name.
     */
    public function addressCountry(string $country): static
    {
        return $this->set('addressCountry', $country);
    }
}
