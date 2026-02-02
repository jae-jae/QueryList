<?php

namespace QueryList\Middleware;

use QueryList\Contracts\MiddlewareInterface;
use QueryList\Context;
use Closure;

class MiddlewarePipeline
{
    /**
     * @var MiddlewareInterface[]
     */
    protected array $middleware = [];

    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Run the pipeline.
     * 
     * @param Context $context
     * @param Closure $destination The final kernel to execute
     * @return mixed Response
     */
    public function handle(Context $context, Closure $destination)
    {
        // 我们需要把 $destination 包装成符合 middleware 签名的闭包
        // Middleware::process(Context $ctx, Closure $next)
        
        // $pipeline 初始值应该是 Kernel
        $next = $destination;

        // 反向遍历中间件，层层包裹
        // [M1, M2] -> M1(M2(Kernel))
        // array_reduce 是从左到右，所以我们要先 reverse
        
        foreach (array_reverse($this->middleware) as $middleware) {
            $nextMiddleware = $next;
            $next = function (Context $ctx) use ($middleware, $nextMiddleware) {
                return $middleware->process($ctx, $nextMiddleware);
            };
        }

        return $next($context);
    }
}
