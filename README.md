# QueryList 5.0 (Alpha)

<p align="center">
  <img src="https://querylist.cc/logo.png" width="200" alt="QueryList Logo">
</p>

> **Type-Safe, Attribute-Driven, Engine-Agnostic Web Scraping Framework for PHP.**

QueryList 5.0 is a complete rewrite of the popular PHP scraping library. It abandons the legacy `phpQuery` dependency in favor of a modern, structured approach using **PHP 8 Attributes**, **DTOs**, and a flexible **Middleware Pipeline**.

## ✨ Features

- **Attribute-Driven Mapping**: Define your data structure with PHP classes and Attributes. No more messy config arrays.
- **Type Safety**: Full IDE autocompletion and type checking for your scraped data.
- **Engine Agnostic**: Switch between static (Guzzle) and dynamic (Panther/WebDriver) engines seamlessly.
- **Middleware Pipeline**: Powerful request/response interception (Proxy, User-Agent, Retries).
- **Modern Core**: Built for PHP 8.2+, utilizing Fibers, Enums, and Strong Typing.

## 📦 Installation

```bash
composer require jaeger/querylist:^5.0
```

## 🚀 Quick Start

### 1. Define your Data Model (DTO)

Create a class and decorate properties with `#[Selector]`.

```php
use QueryList\Attributes\Selector;

class GithubRepo
{
    #[Selector(path: 'h1.h3.lh-condensed', attr: 'text')]
    public string $name;

    #[Selector(path: 'p.f4', attr: 'text')]
    public string $description;

    #[Selector(path: 'span.Counter', attr: 'text')]
    public string $stars;

    #[Selector(path: 'div.BorderGrid-cell ul.list-style-none li a', attr: 'href')]
    public array $topics;
}
```

### 2. Scrape It

```php
use QueryList\Scraper;

$repo = Scraper::to(GithubRepo::class)
    ->get('https://github.com/jae-jae/QueryList');

echo $repo->name; // "jae-jae/QueryList"
echo $repo->description; // "Simple, elegant, extensible PHP Web Scraper..."
```

## 🛠 Advanced Usage

### Middleware (The Onion)

Add custom logic to the request/response lifecycle.

```php
use QueryList\Scraper;
use QueryList\Middleware\RandomUserAgent;
use QueryList\Middleware\ProxyRotation;

$data = Scraper::to(MyData::class)
    ->withMiddleware([
        new RandomUserAgent(),
        new ProxyRotation(['192.168.1.1:8080'])
    ])
    ->get('https://httpbin.org/get');
```

### Hooks (Events)

Listen to lifecycle events for logging or side effects.

```php
Scraper::to(MyData::class)
    ->on('requesting', function($context) {
        echo "Fetching: " . $context->getUrl();
    })
    ->get('https://example.com');
```

## ⚠️ Migration from V4

V5 is **NOT** backward compatible with V4.
- `QueryList::rules()` is replaced by DTOs.
- `phpQuery` selectors are replaced by `Symfony\CssSelector`.
- `bind()` is replaced by Middleware/Extensions.

## 📄 License

MIT
