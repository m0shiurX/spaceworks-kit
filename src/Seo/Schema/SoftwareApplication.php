<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Seo\Schema;

use Laravel\Head\Schema\Offer;
use Laravel\Head\Schema\SchemaObject;
use Laravel\Head\SchemaType;

/**
 * schema.org SoftwareApplication, which Laravel Head has no builder for.
 * It is a CreativeWork, so it has no `brand`: the brand hangs off the
 * publishing Organization instead.
 */
#[SchemaType('SoftwareApplication')]
class SoftwareApplication extends SchemaObject
{
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    public function description(string $description): static
    {
        return $this->set('description', $description);
    }

    public function operatingSystem(string $operatingSystem): static
    {
        return $this->set('operatingSystem', $operatingSystem);
    }

    public function applicationCategory(string $category): static
    {
        return $this->set('applicationCategory', $category);
    }

    /**
     * @param  Offer|array<int, Offer>  $offers
     */
    public function offers(Offer|array $offers): static
    {
        return $this->set('offers', $offers);
    }

    public function aggregateRating(AggregateRating $rating): static
    {
        return $this->set('aggregateRating', $rating);
    }
}
