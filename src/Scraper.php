<?php

namespace QueryList;

use QueryList\Contracts\EngineInterface;
use QueryList\Contracts\MiddlewareInterface;
use QueryList\Engines\StaticEngine;
use QueryList\Middleware\MiddlewarePipeline;
use QueryList\Parsers\DomCrawlerParser;
use QueryList\Support\Hydrator;

class Scraper
{
    protected static ?Scraper $instance = null;
    protected EngineInterface $engine;
    protected MiddlewarePipeline $pipeline;
    protected Hydrator $hydrator;
    
    protected ?string $targetDto = null;

    public function __construct(EngineInterface $engine = null)
    {
        $this->engine = $engine ?? new StaticEngine();
        $this->pipeline = new MiddlewarePipeline();
        $this->hydrator = new Hydrator();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function to(string $dtoClass): self
    {
        $instance = self::getInstance();
        $instance->targetDto = $dtoClass;
        return $instance;
    }

    public function withMiddleware(array|MiddlewareInterface $middleware): self
    {
        if (is_array($middleware)) {
            foreach ($middleware as $m) {
                $this->pipeline->pipe($m);
            }
        } else {
            $this->pipeline->pipe($middleware);
        }
        return $this;
    }

    public function get(string $url, array $options = []): object|array|null
    {
        return $this->request('GET', $url, $options);
    }

    public function post(string $url, array $data = [], array $options = []): object|array|null
    {
        $options['form_params'] = $data;
        return $this->request('POST', $url, $options);
    }

    protected function request(string $method, string $url, array $options = [])
    {
        $context = new Context(null, $options);
        // Inject metadata for the kernel
        $context->setOption('_method', $method);
        $context->setOption('_url', $url);

        // The Kernel: The final destination of the onion
        $kernel = function (Context $ctx) {
            $method = $ctx->getOption('_method');
            $url = $ctx->getOption('_url');
            // Allow middleware to modify options
            $options = $ctx->getOption('http_options', $ctx->getOption('options', []));

            $response = $this->engine->request($method, $url, $options);
            $ctx->setResponse($response);
            
            return $response;
        };

        // Execute Pipeline
        $response = $this->pipeline->handle($context, $kernel);
        
        // Parsing & Hydration
        $html = (string) $response->getBody();
        $parser = new DomCrawlerParser($html);

        if ($this->targetDto) {
            return $this->hydrator->hydrate($this->targetDto, $parser);
        }

        return $parser;
    }
}
