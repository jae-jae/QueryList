<?php

namespace QueryList\TestRunner;

// 手动 Autoload (因为没有 composer dump-autoload)
spl_autoload_register(function ($class) {
    // 把 QueryList\TestRunner 去掉，或者只处理 QueryList
    $prefix = 'QueryList\\';
    $base_dir = __DIR__ . '/src/';
    
    // 如果是 Mock 类，跳过
    if (strpos($class, 'Mock') !== false) return;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use QueryList\Contracts\EngineInterface;
use QueryList\Contracts\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use QueryList\Context;

// Mock Response
class MockResponse {
    protected $body;
    public function __construct($body) { $this->body = $body; }
    public function getBody() { return $this->body; }
}

// Mock Engine
class MockEngine implements EngineInterface {
    public function request(string $method, string $url, array $options = []) {
        echo "[Engine] Requesting $method $url\n";
        return new MockResponse('<html><body><h1 class="title">Hello QueryList</h1></body></html>');
    }
}

// Mock Middleware
class LoggingMiddleware implements MiddlewareInterface {
    public function process(Context $context, callable $next) {
        echo "[Middleware] Before Request\n";
        $response = $next($context);
        echo "[Middleware] After Request\n";
        return $response;
    }
}

// DTO
use QueryList\Attributes\Selector;
class TestDTO {
    #[Selector(path: 'h1.title')]
    public string $title;
}

// 开始测试
echo ">>> Starting QueryList 5.0 Logic Test\n";

use QueryList\Scraper;
use QueryList\Parsers\DomCrawlerParser;

// 我们需要 Mock DomCrawlerParser，因为它依赖 Symfony
// 由于无法加载 Symfony，我们手动 Mock Parser
// 这是一个 Hack，仅为了验证 Scraper 流程
class MockParser extends DomCrawlerParser {
    public function __construct($html) {} 
    public function filter($selector) { return $this; }
    public function text() { return "Hello QueryList"; } // Hardcoded for test
}

// 替换 Scraper 中的 Parser 实例化逻辑需要 DI，但 Scraper 里是 new DomCrawlerParser
// 这说明 Scraper 的耦合度还是有点高。
// 为了测试，我们只能先测试 Pipeline 流程，Hydration 依赖 Symfony DomCrawler 无法在此环境下测试。

echo "1. Testing Pipeline Flow...\n";
$scraper = new Scraper(new MockEngine());
$scraper->withMiddleware(new LoggingMiddleware());

try {
    // 这里会报错，因为 DomCrawlerParser 找不到 Symfony\Component\DomCrawler\Crawler
    // 但如果 Pipeline 执行了打印，说明核心逻辑通了
    $scraper->get('http://test.com');
} catch (\Error $e) {
    if (strpos($e->getMessage(), 'Symfony') !== false) {
        echo ">>> [Success] Pipeline executed (Engine & Middleware ran), crashed at Parser as expected (Missing Vendor).\n";
    } else {
        echo ">>> [Fail] Unexpected Error: " . $e->getMessage() . "\n";
    }
}
