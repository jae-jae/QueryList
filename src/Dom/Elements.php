<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/19
 */

namespace QL\Dom;

use phpQueryObject;

/**
 * Class Elements
 * @package QL\Dom
 *
 * @method Elements toReference($var)
 * @method  documentFragment($state)
 * @method Elements toRoot()
 * @method Elements getDocumentIDRef($documentID)
 * @method Elements getDocument()
 * @method  getDOMDocument()
 * @method Elements getDocumentID()
 * @method Elements unloadDocument()
 * @method  isHTML()
 * @method  isXHTML()
 * @method  isXML()
 * @method  serialize()
 * @method  serializeArray($submit)
 * @method  get($index,$callback1,$callback2,$callback3)
 * @method  getString($index,$callback1,$callback2,$callback3)
 * @method  getStrings($index,$callback1,$callback2,$callback3)
 * @method  newInstance($newStack)
 * @method Elements find($selectors,$context,$noHistory)
 * @method Elements is($selector,$nodes)
 * @method Elements filterCallback($callback,$_skipHistory)
 * @method Elements filter($selectors,$_skipHistory)
 * @method  load($url,$data,$callback)
 * @method Elements trigger($type,$data)
 * @method Elements triggerHandler($type,$data)
 * @method Elements bind($type,$data,$callback)
 * @method  unbind($type,$callback)
 * @method Elements change($callback)
 * @method Elements submit($callback)
 * @method Elements click($callback)
 * @method Elements wrapAllOld($wrapper)
 * @method Elements wrapAll($wrapper)
 * @method Elements wrapAllPHP($codeBefore,$codeAfter)
 * @method Elements wrap($wrapper)
 * @method Elements wrapPHP($codeBefore,$codeAfter)
 * @method Elements wrapInner($wrapper)
 * @method Elements wrapInnerPHP($codeBefore,$codeAfter)
 * @method Elements contents()
 * @method Elements contentsUnwrap()
 * @method  switchWith($markup)
 * @method Elements eq($num)
 * @method Elements size()
 * @method Elements length()
 * @method  count()
 * @method Elements end($level)
 * @method Elements _clone()
 * @method Elements replaceWithPHP($code)
 * @method Elements replaceWith($content)
 * @method Elements replaceAll($selector)
 * @method Elements remove($selector)
 * @method  markup($markup,$callback1,$callback2,$callback3)
 * @method  markupOuter($callback1,$callback2,$callback3)
 * @method  html($html,$callback1,$callback2,$callback3)
 * @method  xml($xml,$callback1,$callback2,$callback3)
 * @method  htmlOuter($callback1,$callback2,$callback3)
 * @method  xmlOuter($callback1,$callback2,$callback3)
 * @method Elements php($code)
 * @method  markupPHP($code)
 * @method  markupOuterPHP()
 * @method Elements children($selector)
 * @method Elements ancestors($selector)
 * @method Elements append($content)
 * @method Elements appendPHP($content)
 * @method Elements appendTo($seletor)
 * @method Elements prepend($content)
 * @method Elements prependPHP($content)
 * @method Elements prependTo($seletor)
 * @method Elements before($content)
 * @method Elements beforePHP($content)
 * @method Elements insertBefore($seletor)
 * @method Elements after($content)
 * @method Elements afterPHP($content)
 * @method Elements insertAfter($seletor)
 * @method Elements insert($target,$type)
 * @method  index($subject)
 * @method Elements slice($start,$end)
 * @method Elements reverse()
 * @method  text($text,$callback1,$callback2,$callback3)
 * @method Elements plugin($class,$file)
 * @method  extend($class,$file)
 * @method Elements _next($selector)
 * @method Elements _prev($selector)
 * @method Elements prev($selector)
 * @method Elements prevAll($selector)
 * @method Elements nextAll($selector)
 * @method Elements siblings($selector)
 * @method Elements not($selector)
 * @method Elements add($selector)
 * @method Elements parent($selector)
 * @method Elements parents($selector)
 * @method  stack($nodeTypes)
 * @method  attr($attr,$value)
 * @method Elements attrPHP($attr,$code)
 * @method Elements removeAttr($attr)
 * @method  val($val)
 * @method Elements andSelf()
 * @method Elements addClass($className)
 * @method Elements addClassPHP($className)
 * @method  hasClass($className)
 * @method Elements removeClass($className)
 * @method Elements toggleClass($className)
 * @method Elements _empty()
 * @method Elements each($callback,$param1,$param2,$param3)
 * @method Elements callback($callback,$param1,$param2,$param3)
 * @method  data($key,$value)
 * @method  removeData($key)
 * @method  rewind()
 * @method  current()
 * @method  key()
 * @method Elements next($cssSelector)
 * @method  valid()
 * @method  offsetExists($offset)
 * @method  offsetGet($offset)
 * @method  offsetSet($offset,$value)
 * @method  offsetUnset($offset)
 * @method  whois($oneNode)
 * @method Elements dump()
 * @method  dumpWhois()
 * @method  dumpLength()
 * @method  dumpTree($html,$title)
 * @method  dumpDie()
 */

class Elements
{
    /**
     * @var phpQueryObject
     */
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

    /**
     * Iterating elements
     *
     * @param $callback
     * @return \Illuminate\Support\Collection
     */
    public function map($callback)
    {
        $collection = collect();
        $this->elements->each(function($dom) use(& $collection,$callback){
            $collection->push($callback(new self(pq($dom))));
        });
        return $collection;
    }

    /**
     * Gets the attributes of all the elements
     *
     * @param $attr HTML attribute name
     * @return \Illuminate\Support\Collection
     */
    public function attrs($attr)
    {
        return $this->map(function($item) use($attr){
            return $item->attr($attr);
        });
    }

    /**
     * Gets the text of all the elements
     *
     * @return \Illuminate\Support\Collection
     */
    public function texts()
    {
        return $this->map(function($item){
            return trim($item->text());
        });
    }

    /**
     * Gets the html of all the elements
     *
     * @return \Illuminate\Support\Collection
     */
    public function htmls()
    {
        return $this->map(function($item){
            return trim($item->html());
        });
    }

    /**
     * @return phpQueryObject
     */
    public function getElements(): phpQueryObject
    {
        return $this->elements;
    }

}