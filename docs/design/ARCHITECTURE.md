# QueryList 5.0 Design Document

## 1. Vision
**QueryList 5.0** aims to be the most modern, type-safe, and elegant web scraping framework for PHP. 
It shifts from a "DOM wrapper" (V4) to a "Data Mapping Framework" (V5), leveraging PHP 8.2+ features like Attributes, Fibers, and Strong Typing.

## 2. Core Architecture

### 2.1 Component Diagram

```mermaid
graph TD
    User code --> Scraper
    Scraper --> MiddlewarePipeline
    MiddlewarePipeline --> EngineInterface
    EngineInterface --> |Static| GuzzleAdapter
    EngineInterface --> |Browser| PantherAdapter
    EngineInterface --> ParserInterface
    ParserInterface --> DomCrawlerAdapter
    Scraper --> Hydrator
    Hydrator --> |Reflects| DTO
    DTO --> |Defines| Attributes
```

### 2.2 Directory Structure

```text
src/
├── Scraper.php                  # Main Entry Point
├── Context.php                  # Request/Response Context
├── Contracts/                   # Interfaces
│   ├── EngineInterface.php
│   ├── ParserInterface.php
│   └── MiddlewareInterface.php
├── Attributes/                  # PHP 8 Attributes
│   ├── Selector.php             # #[Selector('h1')]
│   └── Filter.php               # #[Filter('trim')]
├── Middleware/                  # Standard Middleware
│   ├── RequestMiddleware.php
│   └── ResponseMiddleware.php
├── Engines/                     # Engine Implementations
│   ├── StaticEngine.php         # Guzzle + Symfony/DomCrawler
│   └── BrowserEngine.php        # Symfony/Panther
├── Support/
│   └── Hydrator.php             # Maps DOM to DTO
└── Exceptions/
```

### 2.3 Key Features

#### A. Attribute-Driven Mapping (The Core)
Users define data structures using DTOs (Data Transfer Objects). QueryList automatically fills them.

```php
class Article
{
    #[Selector('h1.title')]
    public string $title;

    #[Selector('.content img', attr: 'src')]
    public array $images;
}
```

#### B. Middleware Pipeline (The Flow)
Standard "Onion" architecture for requests/responses.

#### C. Engine Agnostic (The Driver)
Switch between fast static scraping (Guzzle) and full JS rendering (Chrome) without changing DTOs.

## 3. Technical Requirements
- **PHP**: >= 8.2
- **Dependencies**:
  - `symfony/dom-crawler`: Robust DOM parsing.
  - `symfony/css-selector`: CSS to XPath conversion.
  - `guzzlehttp/guzzle`: Default HTTP client.
  - `psr/http-message`: PSR-7 standards.
  - `psr/log`: PSR-3 standards.

## 4. Migration Guide (V4 -> V5)
- **Namespace**: `QL\` -> `QueryList\` (Proposed cleaner namespace, or keep `QL` for legacy). Let's stick to `QueryList` for V5 to mark the new era.
- **Removed**: `phpQuery` dependency.
- **Changed**: Array-based rules -> Attribute-based DTOs (Legacy rule support can be added as a bridge later).

## 5. Roadmap

1. **Core Foundation**: Interfaces, Context, basic Scraper shell.
2. **Hydrator System**: The logic to read Attributes and extract data from DOM.
3. **Engine Implementation**: StaticEngine (Guzzle+DomCrawler).
4. **Middleware System**: The pipeline logic.
5. **Testing & QA**: High coverage unit tests.
