<?php

declare(strict_types=1);

namespace Spaceworks\Kit\Seo\Schema;

use Laravel\Head\Schema\SchemaObject;
use Laravel\Head\SchemaType;

/**
 * schema.org HowTo with numbered HowToStep entries, which Laravel Head has
 * no builder for.
 */
#[SchemaType('HowTo')]
class HowTo extends SchemaObject
{
    /** @var list<array{'@type': string, position: int, name: string, text: string}> */
    protected array $steps = [];

    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    public function description(string $description): static
    {
        return $this->set('description', $description);
    }

    /**
     * The total time as an ISO 8601 duration, e.g. "P1D".
     */
    public function totalTime(string $duration): static
    {
        return $this->set('totalTime', $duration);
    }

    /**
     * Append a step. Unnamed steps are called "{$prefix} {position}".
     */
    public function step(string $text, ?string $name = null, string $prefix = 'Step'): static
    {
        $position = count($this->steps) + 1;

        $this->steps[] = [
            '@type' => 'HowToStep',
            'position' => $position,
            'name' => $name ?? "{$prefix} {$position}",
            'text' => $text,
        ];

        return $this->set('step', $this->steps);
    }

    /**
     * @param  iterable<string>  $texts
     */
    public function steps(iterable $texts): static
    {
        foreach ($texts as $text) {
            $this->step($text);
        }

        return $this;
    }
}
