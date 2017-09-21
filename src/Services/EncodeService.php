<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/20
 */

namespace QL\Services;

class EncodeService
{
    public static function convert($ql,string $outputEncoding,string $inputEncoding = null)
    {
        dump($outputEncoding,$inputEncoding);
        return $ql;
    }

    public static function detect()
    {

    }
}