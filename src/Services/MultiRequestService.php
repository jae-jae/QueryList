<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 18/12/10
 * Time: 下午7:05
 */

namespace QL\Services;


use Jaeger\GHttp;
use Closure;

/**
 * Class MultiRequestService
 * @package QL\Services
 *
 * @method MultiRequestService withHeaders($headers)
 * @method MultiRequestService withOptions($options)
 * @method MultiRequestService concurrency($concurrency)
 */
class MultiRequestService
{
    protected $ql;
    protected $multiRequest;
    public function __construct($ql,$urls)
    {
        $this->ql = $ql;
        $this->multiRequest = GHttp::multiRequest($urls);
    }

    public function __call($name, $arguments)
    {
        return $this->multiRequest->$name(...$arguments);
    }

    public function success(Closure $success)
    {
       return $this->multiRequest->success(function($response, $index) use($success){
           $this->ql->setHtml((String)$response->getBody());
           $success($this->ql,$response, $index);
       });
    }

    public function error(Closure $error)
    {
        return $this->multiRequest->error(function($reason, $index) use($error){
            $error($this->ql,$reason, $index);
        });
    }

    public function sendGet()
    {
        $this->multiRequest->get();
    }

    public function sendPost()
    {
        $this->multiRequest->post();
    }
}