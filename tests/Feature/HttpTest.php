<?php
/**
 * Created by PhpStorm.
 * User: x
 * Date: 2018/12/10
 * Time: 12:35 AM
 */

namespace Tests\Feature;


use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use QL\QueryList;
use Tests\TestCaseBase;

class HttpTest extends TestCaseBase
{
    protected $urls;

    protected function setUp(): void
    {
        $this->urls = [
            'http://httpbin.org/get?name=php',
            'http://httpbin.org/get?name=golang',
            'http://httpbin.org/get?name=c++',
            'http://httpbin.org/get?name=java'
        ];
    }

    /**
     * @test
     */
    public function can_post_json_data()
    {
        $mock = new MockHandler([new Response()]);
        $data = [
            'name' => 'foo'
        ];
        QueryList::postJson('http://foo.com',$data,[
            'handler' => $mock
        ]);
        $this->assertEquals((string)$mock->getLastRequest()->getBody(),json_encode($data));
    }

    /**
     * @test
     */
    public function concurrent_requests_base_use()
    {
        $urls = $this->urls;
        QueryList::getInstance()
            ->multiGet($urls)
            ->success(function(QueryList $ql,Response $response, $index) use($urls){
                $body = json_decode((string)$response->getBody(),true);
                $this->assertEquals($urls[$index],$body['url']);
            })->send();
    }

    /**
     * @test
     */
    public function concurrent_requests_advanced_use()
    {
        $ua = 'QueryList/4.0';

        $errorUrl = 'http://web-site-not-exist.com';
        $urls = array_merge($this->urls,[$errorUrl]);

        QueryList::rules([])
            ->multiGet($urls)
            ->concurrency(2)
            ->withOptions([
                'timeout' => 60
            ])
            ->withHeaders([
                'User-Agent' => $ua
            ])
            ->success(function (QueryList $ql, Response $response, $index) use($ua){
                $body = json_decode((string)$response->getBody(),true);
                $this->assertEquals($ua,$body['headers']['User-Agent']);
            })
            ->error(function (QueryList $ql, $reason, $index) use($urls,$errorUrl){
                $this->assertEquals($urls[$index],$errorUrl);
            })
            ->send();
    }

    /**
     * @test
     */
    public function request_with_cache()
    {
        $url = $this->urls[0];
        $data = QueryList::get($url,null,[
            'cache' => sys_get_temp_dir(),
            'cache_ttl' => 600
        ])->getHtml();
        $data = json_decode($data,true);
        $this->assertEquals($url,$data['url']);

    }
}