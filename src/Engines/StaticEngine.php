<?php

namespace QueryList\Engines;

use GuzzleHttp\Client;
use QueryList\Context;
use QueryList\Contracts\EngineInterface;
use Psr\Http\Message\ResponseInterface;

class StaticEngine implements EngineInterface
{
    protected Client $client;

    public function __construct(array $config = [])
    {
        $this->client = new Client($config);
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $url, $options);
    }
}
