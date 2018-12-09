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
}