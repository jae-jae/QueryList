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
        //加入disguise_headers的判断
        if (isset($ql->disguise_headers)) {
            if (isset($otherArgs['headers']))
                $headers = array_merge($ql->disguise_headers['headers'],$otherArgs['headers']);
            else
                $headers = $ql->disguise_headers['headers'];
            
            $otherArgs['headers'] = $headers;
        }
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
        //加入disguise_headers的判断
        if (isset($ql->disguise_headers)) {
            if (isset($otherArgs['headers']))
                $headers = array_merge($ql->disguise_headers['headers'],$otherArgs['headers']);
            else
                $headers = $ql->disguise_headers['headers'];
            
            $otherArgs['headers'] = $headers;
        }
        $otherArgs = array_merge([
            'cookies' => self::getCookieJar(),
            'verify' => false
        ],$otherArgs);
        $html = GHttp::post($url,$args,$otherArgs);
        $ql->setHtml($html);
        return $ql;
    }

    public static function postJson(QueryList $ql,$url,$args = null,$otherArgs = [])
    {
        //加入disguise_headers的判断
        if (isset($ql->disguise_headers)) {
            if (isset($otherArgs['headers']))
                $headers = array_merge($ql->disguise_headers['headers'],$otherArgs['headers']);
            else
                $headers = $ql->disguise_headers['headers'];

            $otherArgs['headers'] = $headers;
        }
        $otherArgs = array_merge([
            'cookies' => self::getCookieJar(),
            'verify' => false
        ],$otherArgs);
        $html = GHttp::postJson($url,$args,$otherArgs);
        $ql->setHtml($html);
        return $ql;
    }
}
