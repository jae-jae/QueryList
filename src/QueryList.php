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
use Illuminate\Support\Collection;
use Closure;

/**
 * Class QueryList
 * @package QL
 *
 * @method string getHtml()
 * @method QueryList setHtml($html)
 * @method QueryList html($html)
 * @method Dom\Elements find($selector)
 * @method QueryList rules(array $rules)
 * @method QueryList range($range)
 * @method QueryList removeHead()
 * @method QueryList query(Closure $callback = null)
 * @method Collection getData(Closure $callback = null)
 * @method QueryList setData(Collection $data)
 * @method QueryList encoding(string $outputEncoding,string $inputEncoding = null)
 * @method QueryList get($url,$args = null,$otherArgs = [])
 * @method QueryList post($url,$args = null,$otherArgs = [])
 * @method QueryList use($plugins,...$opt)
 */
class QueryList
{
    protected $query;
    protected $kernel;
    protected static $plugins = [];

    /**
     * QueryList constructor.
     */
    public function __construct()
    {
        $this->query = new Query($this);
        $this->kernel = (new Kernel($this))->bootstrap();
        Config::getInstance()->bootstrap($this);
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

    public static function config()
    {
        return Config::getInstance();
    }

    public function destruct()
    {
        phpQuery::$documents = [];
    }

    public function bind(string $name,Closure $provide)
    {
        $this->kernel->bind($name,$provide);
        return $this;
    }

}