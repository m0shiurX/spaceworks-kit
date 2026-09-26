<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Seo\Schema;

use Laravel\Head\Schema\SchemaObject;
use Laravel\Head\SchemaType;

/**
 * schema.org ContactPoint, which Laravel Head has no builder for.
 */
#[SchemaType('ContactPoint')]
class ContactPoint extends SchemaObject
{
    public function telephone(string $telephone): static
    {
        return $this->set('telephone', $telephone);
    }

    public function contactType(string $contactType): static
    {
        return $this->set('contactType', $contactType);
    }

    public function email(string $email): static
    {
        return $this->set('email', $email);
    }

    public function areaServed(string $areaServed): static
    {
        return $this->set('areaServed', $areaServed);
    }

    /**
     * @param  list<string>  $languages
     */
    public function availableLanguage(array $languages): static
    {
        return $this->set('availableLanguage', $languages);
    }
}
