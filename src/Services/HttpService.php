<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/22
 */

namespace QL\Services;

use GuzzleHttp\Cookie\CookieJar;
use Jaeger\GHttp;
use QL\QueryList;

class HttpService
{
    protected static $cookieJar = null;

    public static function getCookieJar()
    {
        if(self::$cookieJar == null)
        {
            self::$cookieJar = new CookieJar();
        }
        return self::$cookieJar;
    }

    public static function get(QueryList $ql,$url,$args = null,$otherArgs = [])
    {
        $otherArgs = array_merge([
            'cookies' => self::getCookieJar(),
            'verify' => false
        ],$otherArgs);
        $html = GHttp::get($url,$args,$otherArgs);
        $ql->setHtml($html);
        return $ql;
    }

    public static function post(QueryList $ql,$url,$args = null,$otherArgs = [])
    {
        $otherArgs = array_merge([
            'cookies' => self::getCookieJar(),
            'verify' => false
        ],$otherArgs);
        $html = GHttp::post($url,$args,$otherArgs);
        $ql->setHtml($html);
        return $ql;
    }
}