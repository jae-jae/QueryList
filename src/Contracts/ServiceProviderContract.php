<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/20
 */

namespace QL\Contracts;

use QL\Kernel;

interface ServiceProviderContract
{
    public function register(Kernel $kernel);
}