<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Identity
    |--------------------------------------------------------------------------
    |
    | The public identity used by the SEO kit (Spaceworks\Kit\Seo). "url" is
    | the canonical production origin used for JSON-LD @ids and schema URLs;
    | set SEO_SITE_URL so structured data stays stable across environments.
    |
    */

    'site' => [
        'name' => env('APP_NAME', 'Laravel'),
        'url' => env('SEO_SITE_URL', env('APP_URL', 'http://localhost')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Titles
    |--------------------------------------------------------------------------
    |
    | "suffix" decorates every page title set through Laravel Head (it renders
    | nothing on its own). "default" is the title for pages that set none.
    | Page titles that already contain "brand" skip the suffix.
    |
    */

    'title' => [
        'default' => null,
        'suffix' => null,
        'brand' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Site-wide Head Defaults
    |--------------------------------------------------------------------------
    |
    | Tags applied to every page through Head::defaults(). Keep each one off
    | while a client-side <Head> still renders the same tag, because Inertia
    | does not de-duplicate a server tag and a client tag for one element.
    |
    */

    'defaults' => [
        'title' => false,
        'canonical' => false,
        'open_graph' => false,
        'twitter' => false,
        'robots' => null,
    ],

    'open_graph' => [
        'type' => 'website',
        'image_route' => 'og.image',
        'image_width' => 1200,
        'image_height' => 630,
    ],

    'twitter' => [
        'card' => 'summary_large_image',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Graph
    |--------------------------------------------------------------------------
    |
    | When "link_ids" is on, the organization, brand, website and software
    | nodes carry their EntityGraph @id, so every site in the graph points at
    | the same company.
    |
    */

    'graph' => [
        'link_ids' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Organization
    |--------------------------------------------------------------------------
    |
    | Spaceworks, the company behind every site in the graph. It is the root
    | Organization: it publishes each website, software and article, and owns
    | each site's brand. It is defined once, here, so all sites describe the
    | same node; sites should not override it.
    |
    | "address", "telephone", "email" and "contact_point" are optional; a null
    | value (or an address with no parts) is left out of the node. Opening
    | hours belong on a LocalBusiness, not here.
    |
    */

    'organization' => [
        'id' => env('SEO_ORGANIZATION_ID', 'https://spaceworks.dev/#organization'),
        'name' => 'Spaceworks',
        'url' => env('SEO_ORGANIZATION_URL', 'https://spaceworks.dev'),
        'logo' => env('SEO_ORGANIZATION_LOGO'),
        'same_as' => [],
        'address' => [
            'street' => 'Uposhohor R/A',
            'locality' => 'Bogura',
            'region' => null,
            'postal_code' => '5800',
            'country' => 'BD',
        ],
        'telephone' => '+8801625292000',
        'email' => null,
        'contact_point' => [
            'contact_type' => 'customer support',
            'telephone' => '+8801625292000',
            'email' => null,
            'area_served' => 'BD',
            'available_language' => ['Bengali', 'English'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    |
    | The site's product brand, owned by the organization above. "id"
    | defaults to "{site.url}/#brand" when null.
    |
    */

    'brand' => [
        'id' => null,
        'name' => null,
        'url' => null,
        'logo' => null,
        'same_as' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSite
    |--------------------------------------------------------------------------
    |
    | "id" defaults to "{site.url}/#website". "search_path" feeds the
    | SearchAction target; null leaves the SearchAction out.
    |
    */

    'website' => [
        'id' => null,
        'search_path' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Software Application (product facts)
    |--------------------------------------------------------------------------
    |
    | Only for product sites. "id" defaults to "{site.url}/#software".
    | "offers.plans" lists the paid plans (name => price); the first one is
    | the headline price. "availability" is a schema.org ItemAvailability.
    |
    */

    'software' => [
        'id' => null,
        'name' => null,
        'description' => null,
        'operating_system' => 'Web',
        'application_category' => 'BusinessApplication',
        'offers' => [
            'currency' => null,
            'availability' => 'InStock',
            'pricing_path' => '/pricing',
            'plans' => [],
        ],
        'rating' => [
            'value' => null,
            'count' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | The trail built from the request path: "home" first, then one crumb per
    | path segment, labelled from "labels" (segment => label) or the title-
    | cased segment. The trail is shared with the page as the "prop" prop and
    | emitted as BreadcrumbList schema on the canonical site origin.
    |
    */

    'breadcrumbs' => [
        'prop' => 'breadcrumbs',
        'home' => 'Home',
        'labels' => [],
    ],

];
