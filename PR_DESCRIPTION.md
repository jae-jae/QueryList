# Pull Request: QueryList 5.0 Core Architecture (Alpha)

## Summary
This PR introduces the foundational architecture for QueryList 5.0, a complete rewrite focusing on type safety and attribute-driven data mapping.

## Changes
- **Core**: Added `Scraper` entry point and `Context` object.
- **Contracts**: Defined `EngineInterface`, `ParserInterface`, `MiddlewareInterface`.
- **Attributes**: Introduced `#[Selector]` and `#[Filter]` for DTO mapping.
- **Hydrator**: Implemented reflection-based data hydration logic.
- **Engines**: Added `StaticEngine` (Guzzle + DomCrawler).
- **Middleware**: Implemented onion-style middleware pipeline.
- **Documentation**: Updated `README.md` with V5 usage.

## Usage Example

```php
class UserDTO {
    #[Selector(path: 'h1.name')]
    public string $name;
}

$user = Scraper::to(UserDTO::class)->get('https://example.com/user/1');
```

## Status
- [x] Architecture Design
- [ ] Core Implementation (In Progress via Sub-agents)
- [ ] Unit Tests
