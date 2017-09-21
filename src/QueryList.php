<?php
/**
 * QueryList
 *
 * 一个基于phpQuery的通用列表采集类
 *
 * @author 			Jaeger
 * @email 			JaegerCode@gmail.com
 * @link            https://github.com/jae-jae/QueryList
 * @version         4.0.0
 *
 */

namespace QL;
use phpQuery;
use QL\Dom\Query;


/**
 * Class QueryList
 * @package QL
 *
 * @method QueryList getHtml()
 * @method QueryList setHtml($html)
 * @method Dom\Elements find($selector)
 * @method QueryList rules(array $rules)
 * @method QueryList range($range)
 * @method QueryList removeHead()
 * @method \Illuminate\Support\Collection query($callback = null)
 * @method QueryList encoding(string $outputEncoding,string $inputEncoding = null)
 */
class QueryList
{
    protected $query;
    protected $kernel;

    /**
     * QueryList constructor.
     */
    public function __construct()
    {
        $this->query = new Query($this);
        $this->kernel = (new Kernel($this))->bootstrap();
    }

    public function __call($name, $arguments)
    {
        if(method_exists($this->query,$name)){
            $result = $this->query->$name(...$arguments);
        }else{
            $result = $this->kernel->getService($name)->call($this,...$arguments);
        }
       return $result;
    }

    public static function __callStatic($name, $arguments)
    {
        $instance = self::getInstance();
        return $instance->$name(...$arguments);
    }

    public function __destruct()
    {
        $this->destruct();
    }

    public static function getInstance()
    {
        $instance = new self();
        return $instance;
    }

    public function destruct()
    {
        phpQuery::$documents = [];
    }


}