<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/22
 */

namespace QL\Providers;

use QL\Contracts\ServiceProviderContract;
use QL\Kernel;

class SystemServiceProvider implements ServiceProviderContract
{
    public function register(Kernel $kernel)
    {
        $kernel->bind('html',function (...$args){
            $this->setHtml(...$args);
            return $this;
        });

    }
}