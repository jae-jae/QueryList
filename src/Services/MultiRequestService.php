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
use GuzzleHttp\Psr7\Response;
use QL\QueryList;
use GuzzleHttp\Exception\RequestException;

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
    protected $method;

    public function __construct(QueryList $ql,$method,$urls)
    {
        $this->ql = $ql;
        $this->method = $method;
        $this->multiRequest = GHttp::multiRequest($urls);
    }

    public function __call($name, $arguments)
    {
        $this->multiRequest = $this->multiRequest->$name(...$arguments);
        return $this;
    }

    public function success(Closure $success)
    {
        $this->multiRequest = $this->multiRequest->success(function(Response $response, $index) use($success){
           $this->ql->setHtml((String)$response->getBody());
           $success($this->ql,$response, $index);
       });
        return $this;
    }

    public function error(Closure $error)
    {
        $this->multiRequest = $this->multiRequest->error(function(RequestException $reason, $index) use($error){
            $error($this->ql,$reason, $index);
        });
        return $this;
    }

    public function send()
    {
        $this->multiRequest->{$this->method}();
    }
}