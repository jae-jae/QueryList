<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 18/12/12
 * Time: 下午12:25
 */

namespace Tests\Dom;


use QL\QueryList;
use Tests\TestCaseBase;
use Tightenco\Collect\Support\Collection;

class RulesTest extends TestCaseBase
{
    protected $html;
    protected $ql;

    protected function setUp(): void
    {
        $this->html = $this->getSnippet('snippet-2');
        $this->ql = QueryList::html($this->html);
    }

    /**
     * @test
     */
    public function get_data_by_rules()
    {
        $rules = [
            'a' => ['a','text'],
            'img_src' => ['img','src'],
            'img_alt' => ['img','alt']
        ];
        $range = 'ul>li';
        $data = QueryList::rules($rules)->range($range)->html($this->html)->query()->getData();
        $this->assertInstanceOf(Collection::class,$data);
        $this->assertCount(3,$data);
        $this->assertEquals('http://querylist.com/2.jpg',$data[1]['img_src']);
    }
}