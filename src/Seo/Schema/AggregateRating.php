<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Seo\Schema;

use Laravel\Head\Schema\SchemaObject;
use Laravel\Head\SchemaType;

/**
 * schema.org AggregateRating, which Laravel Head has no builder for.
 */
#[SchemaType('AggregateRating')]
class AggregateRating extends SchemaObject
{
    public function ratingValue(float|int|string $value): static
    {
        return $this->set('ratingValue', $value);
    }

    public function ratingCount(int|string $count): static
    {
        return $this->set('ratingCount', $count);
    }
}
