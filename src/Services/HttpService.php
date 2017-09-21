<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/22
 */

namespace QL\Services;

use Jaeger\GHttp;
use QL\QueryList;

class HttpService
{
    public static function get(QueryList $ql,$url,$args = null,$otherArgs = [])
    {
        $html = GHttp::get($url,$args,$otherArgs);
        $ql->setHtml($html);
        return $ql;
    }

    public static function post(QueryList $ql,$url,$args = null,$otherArgs = [])
    {
        $html = GHttp::post($url,$args,$otherArgs);
        $ql->setHtml($html);
        return $ql;
    }
}