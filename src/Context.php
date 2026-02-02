<?php

namespace QueryList;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Context
{
    protected ?RequestInterface $request = null;
    protected ?ResponseInterface $response = null;
    protected array $options = [];
    protected array $meta = [];

    public function __construct(?RequestInterface $request = null, array $options = [])
    {
        $this->request = $request;
        $this->options = $options;
    }

    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    public function setRequest(RequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response): self
    {
        $this->response = $response;
        return $this;
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function setOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;
        return $this;
    }
}
