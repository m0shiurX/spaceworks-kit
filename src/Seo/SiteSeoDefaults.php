<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Seo;

use Illuminate\Contracts\Config\Repository;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;

/**
 * Registers the site-wide Laravel Head defaults from config/seo.php.
 *
 * Every rendered default is opt-in (seo.defaults.*), so tags that a client-side
 * <Head> still renders are never emitted twice. The title suffix is always
 * applied because it only decorates titles set through Laravel Head.
 */
class SiteSeoDefaults
{
    public function __construct(protected Repository $config) {}

    /**
     * Register the defaults with Laravel Head.
     */
    public function apply(): void
    {
        Head::defaults(fn (HeadBuilder $head): HeadBuilder => $this->configure($head));
    }

    /**
     * Apply the configured defaults to the given head builder.
     */
    public function configure(HeadBuilder $head): HeadBuilder
    {
        $this->configureTitle($head);

        if ($this->config->get('seo.defaults.canonical')) {
            $head->canonical();
        }

        if ($this->config->get('seo.defaults.open_graph')) {
            $head->og(
                type: $this->string('seo.open_graph.type'),
                siteName: $this->string('seo.site.name'),
            );
        }

        if ($this->config->get('seo.defaults.twitter')) {
            $head->twitter(card: $this->string('seo.twitter.card'));
        }

        if ($robots = $this->config->get('seo.defaults.robots')) {
            $head->robots($robots);
        }

        return $head;
    }

    /**
     * A default title renders as-is; without one, an empty default title
     * carries the suffix to page titles while rendering no tag itself.
     */
    protected function configureTitle(HeadBuilder $head): void
    {
        $suffix = $this->string('seo.title.suffix');
        $default = $this->config->get('seo.defaults.title') ? $this->string('seo.title.default') : null;

        if (is_null($suffix) && is_null($default)) {
            return;
        }

        $head->title($default ?? '', suffix: $suffix);
    }

    protected function string(string $key): ?string
    {
        $value = $this->config->get($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
