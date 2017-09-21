<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/22
 */

namespace QL\Providers;


use QL\Contracts\ServiceProviderContract;
use QL\Kernel;
use QL\Services\HttpService;

class HttpServiceProvider implements ServiceProviderContract
{
    public function register(Kernel $kernel)
    {
        $kernel->bind('get',function (...$args){
           return HttpService::get($this,...$args);
        });

        $kernel->bind('post',function (...$args){
            return HttpService::post($this,...$args);
        });
    }
}