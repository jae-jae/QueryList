<?php

declare(strict_types=1);

namespace QueryList\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Selector
{
    /**
     * @param string $path CSS selector
     * @param string $attr Attribute to extract (default 'text')
     * @param string|null $filter Callable name for filtering/processing
     * @param bool $multiple Whether to fetch multiple elements (list)
     */
    public function __construct(
        public string $path,
        public string $attr = 'text',
        public ?string $filter = null,
        public bool $multiple = false
    ) {
    }
}
