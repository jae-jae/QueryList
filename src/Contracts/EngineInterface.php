<?php

declare(strict_types=1);

namespace QueryList\Contracts;

use Psr\Http\Message\ResponseInterface;

interface EngineInterface
{
    /**
     * Send an HTTP request.
     *
     * @param string $method
     * @param string $url
     * @param array<string, mixed> $options
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface;
}
