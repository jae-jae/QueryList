<?php

namespace QueryList\Contracts;

use QueryList\Context;

interface MiddlewareInterface
{
    /**
     * @param Context $context
     * @param callable $next
     * @return mixed
     */
    public function process(Context $context, callable $next);
}
