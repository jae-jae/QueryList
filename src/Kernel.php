<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/21
 */

namespace QL;

use QL\Contracts\ServiceProviderContract;
use QL\Providers\EncodeServiceProvider;


class Kernel
{
    protected $providers = [
        EncodeServiceProvider::class
    ];

    protected $binds = [];

    public function bootstrap()
    {
        $this->registerProviders();

        return $this;
    }

    public function registerProviders()
    {
        foreach ($this->providers as $provider) {
            $this->register(new $provider());
        }
    }

    public function bind($name, $provider)
    {
        $this->binds[$name] = value($provider);
    }

    private function register(ServiceProviderContract $instance)
    {
        $instance->register($this);
    }


}