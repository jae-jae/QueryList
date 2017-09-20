<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/20
 */

namespace QL\Providers;

use QL\Contracts\ServiceProviderContract;
use QL\Kernel;
use QL\Services\EncodeService;

class EncodeServiceProvider implements ServiceProviderContract
{
    public function register(Kernel $kernel)
    {
        $kernel->bind('encoder',function (){
            return new EncodeService();
        });
    }
}