<?php

declare(strict_types=1);

namespace QueryList\Contracts;

interface ParserInterface
{
    /**
     * Load HTML content into the parser.
     */
    public function parse(string $html): self;

    /**
     * Filter elements by selector.
     */
    public function filter(string $selector): self;

    /**
     * Get the text content of the current element(s).
     */
    public function text(): string;

    /**
     * Get the value of an attribute.
     */
    public function attr(string $name): ?string;
}
