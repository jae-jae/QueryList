<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/22
 */

namespace QL\Providers;

use QL\Contracts\ServiceProviderContract;
use QL\Kernel;
use QL\Services\PluginService;

class PluginServiceProvider implements ServiceProviderContract
{
    public function register(Kernel $kernel)
    {
        $kernel->bind('use',function ($plugins,...$opt){
            return PluginService::install($this,$plugins,...$opt);
        });
    }

}