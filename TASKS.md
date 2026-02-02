# QueryList 5.0 Tasks

## Core System
- [ ] **Define Contracts**: Create `EngineInterface`, `ParserInterface`, `MiddlewareInterface`.
- [ ] **Scraper Entry**: Implement the main `QueryList\Scraper` class.
- [ ] **Context**: Implement `QueryList\Context` to hold request/response state.

## Hydration System (The Brain)
- [ ] **Attributes**: Create `#[Selector]`, `#[Filter]` attributes.
- [ ] **Hydrator**: Implement reflection logic to map DOM -> DTO properties.

## Engine & Parsing (The Muscle)
- [ ] **StaticEngine**: Implement `Guzzle` + `DomCrawler` integration.
- [ ] **ParserAdapter**: Wrapper around `Symfony\DomCrawler`.

## Middleware (The Nervous System)
- [ ] **Pipeline**: Implement the middleware dispatcher (Onion pattern).
- [ ] **Standard Middleware**: Add `UserAgentMiddleware` and `ProxyMiddleware`.

## Documentation & DX
- [ ] **README.md**: Write the new English README with V5 usage.
- [ ] **Composer**: Update `composer.json` for V5 deps.
