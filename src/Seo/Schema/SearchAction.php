<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Seo\Schema;

use Laravel\Head\Schema\SchemaObject;
use Laravel\Head\SchemaType;

/**
 * schema.org SearchAction for a WebSite's sitelinks search box.
 */
#[SchemaType('SearchAction')]
class SearchAction extends SchemaObject
{
    /**
     * The search URL template, e.g. "https://example.com/search?q={search_term_string}".
     */
    public function target(string $urlTemplate): static
    {
        return $this->set('target', $urlTemplate);
    }

    public function queryInput(string $queryInput = 'required name=search_term_string'): static
    {
        return $this->set('query-input', $queryInput);
    }
}
