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
use QL\Services\MultiRequestService;

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

        $kernel->bind('postJson',function (...$args){
            return HttpService::postJson($this,...$args);
        });

        $kernel->bind('multiGet',function (...$args){
            return new MultiRequestService($this,'get',...$args);
        });

        $kernel->bind('multiPost',function (...$args){
            return new MultiRequestService($this,'post',...$args);
        });
    }
}