<?php

namespace QueryList\Parsers;

use QueryList\Contracts\ParserInterface;
use Symfony\Component\DomCrawler\Crawler;

class DomCrawlerParser implements ParserInterface
{
    protected Crawler $crawler;

    public function __construct(Crawler|string $crawler)
    {
        if (is_string($crawler)) {
            $this->crawler = new Crawler($crawler);
        } else {
            $this->crawler = $crawler;
        }
    }

    public function parse(string $html): ParserInterface
    {
        return new self($html);
    }

    public function filter(string $selector): ParserInterface
    {
        // 返回一个新的 Parser 实例，包含过滤后的节点
        try {
            $newCrawler = $this->crawler->filter($selector);
            return new self($newCrawler);
        } catch (\Exception $e) {
            // 如果没找到，返回空的 Crawler 以避免链式调用崩溃
            return new self(new Crawler());
        }
    }

    public function text(): string
    {
        try {
            return $this->crawler->text();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function html(): string
    {
        try {
            return $this->crawler->html();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function attr(string $name): ?string
    {
        try {
            return $this->crawler->attr($name);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 支持获取多个元素的属性或文本
     */
    public function texts(): array
    {
        return $this->crawler->each(function (Crawler $node) {
            return $node->text();
        });
    }

    public function attrs(string $name): array
    {
        return $this->crawler->each(function (Crawler $node) use ($name) {
            return $node->attr($name);
        });
    }

    public function map(callable $callback): array
    {
        return $this->crawler->each(function (Crawler $node) use ($callback) {
            // 包装成 ParserInterface 传给回调，保持接口统一
            return $callback(new self($node));
        });
    }
}
