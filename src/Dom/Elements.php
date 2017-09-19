<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/19
 */

namespace QL\Dom;

use phpQueryObject;

class Elements
{
    protected $elements;

    /**
     * Elements constructor.
     * @param $elements
     */
    public function __construct(phpQueryObject $elements)
    {
        $this->elements = $elements;
    }

    public function __get($name)
    {
        return property_exists($this->elements,$name)?$this->elements->$name:$this->elements->attr($name);
    }

    public function __call($name, $arguments)
    {
        $obj = call_user_func_array([$this->elements,$name],$arguments);
        if($obj instanceof phpQueryObject){
            $obj = new self($obj);
        }else if(is_string($obj)){
            $obj = trim($obj);
        }
        return $obj;
    }

    public function map($callback)
    {
        $collection = collect();
        $this->elements->each(function($dom) use(& $collection,$callback){
            $collection->push($callback(new self(pq($dom))));
        });
        return $collection;
    }

    public function attrs($attr)
    {
        return $this->map(function($item) use($attr){
            return $item->attr($attr);
        });
    }

    public function texts()
    {
        return $this->map(function($item){
            return trim($item->text());
        });
    }

    public function htmls()
    {
        return $this->map(function($item){
            return trim($item->html());
        });
    }
}