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
        $kernel->bind('encoding',function (string $outputEncoding,string $inputEncoding = null){
            return EncodeService::convert($this,$outputEncoding,$inputEncoding);
        });
    }
}