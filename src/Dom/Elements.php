<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/19
 */

namespace QL\Dom;

use phpDocumentor\Reflection\Types\Null_;
use phpQueryObject;
use Tightenco\Collect\Support\Collection;

/**
 * Class Elements
 * @package QL\Dom
 *
 * @method Elements toReference(&$var)
 * @method Elements documentFragment($state = null)
 * @method Elements toRoot()
 * @method Elements getDocumentIDRef(&$documentID)
 * @method Elements getDocument()
 * @method \DOMDocument getDOMDocument()
 * @method Elements getDocumentID()
 * @method Elements unloadDocument()
 * @method bool isHTML()
 * @method bool isXHTML()
 * @method bool isXML()
 * @method string serialize()
 * @method array serializeArray($submit = null)
 * @method \DOMElement|\DOMElement[] get($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
 * @method string|array getString($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
 * @method string|array getStrings($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
 * @method Elements newInstance($newStack = null)
 * @method Elements find($selectors, $context = null, $noHistory = false)
 * @method Elements|bool is($selector, $nodes = null)
 * @method Elements filterCallback($callback, $_skipHistory = false)
 * @method Elements filter($selectors, $_skipHistory = false)
 * @method Elements load($url, $data = null, $callback = null)
 * @method Elements trigger($type, $data = [])
 * @method Elements triggerHandler($type, $data = [])
 * @method Elements bind($type, $data, $callback = null)
 * @method Elements unbind($type = null, $callback = null)
 * @method Elements change($callback = null)
 * @method Elements submit($callback = null)
 * @method Elements click($callback = null)
 * @method Elements wrapAllOld($wrapper)
 * @method Elements wrapAll($wrapper)
 * @method Elements wrapAllPHP($codeBefore, $codeAfter)
 * @method Elements wrap($wrapper)
 * @method Elements wrapPHP($codeBefore, $codeAfter)
 * @method Elements wrapInner($wrapper)
 * @method Elements wrapInnerPHP($codeBefore, $codeAfter)
 * @method Elements contents()
 * @method Elements contentsUnwrap()
 * @method Elements switchWith($markup)
 * @method Elements eq($num)
 * @method Elements size()
 * @method Elements length()
 * @method int count()
 * @method Elements end($level = 1)
 * @method Elements _clone()
 * @method Elements replaceWithPHP($code)
 * @method Elements replaceWith($content)
 * @method Elements replaceAll($selector)
 * @method Elements remove($selector = null)
 * @method Elements|string markup($markup = null, $callback1 = null, $callback2 = null, $callback3 = null)
 * @method string markupOuter($callback1 = null, $callback2 = null, $callback3 = null)
 * @method Elements|string html($html = null, $callback1 = null, $callback2 = null, $callback3 = null)
 * @method Elements|string xml($xml = null, $callback1 = null, $callback2 = null, $callback3 = null)
 * @method string htmlOuter($callback1 = null, $callback2 = null, $callback3 = null)
 * @method string xmlOuter($callback1 = null, $callback2 = null, $callback3 = null)
 * @method Elements php($code)
 * @method string markupPHP($code)
 * @method string markupOuterPHP()
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
 * @method Elements insert($target, $type)
 * @method int index($subject)
 * @method Elements slice($start, $end = null)
 * @method Elements reverse()
 * @method Elements|string text($text = null, $callback1 = null, $callback2 = null, $callback3 = null)
 * @method Elements plugin($class, $file = null)
 * @method Elements _next($selector = null)
 * @method Elements _prev($selector = null)
 * @method Elements prev($selector = null)
 * @method Elements prevAll($selector = null)
 * @method Elements nextAll($selector = null)
 * @method Elements siblings($selector = null)
 * @method Elements not($selector = null)
 * @method Elements add($selector = null)
 * @method Elements parent($selector = null)
 * @method Elements parents($selector = null)
 * @method Elements stack($nodeTypes = null)
 * @method Elements|string attr($attr = null, $value = null)
 * @method Elements attrPHP($attr, $code)
 * @method Elements removeAttr($attr)
 * @method Elements|string val($val = null)
 * @method Elements andSelf()
 * @method Elements addClass($className)
 * @method Elements addClassPHP($className)
 * @method bool hasClass($className)
 * @method Elements removeClass($className)
 * @method Elements toggleClass($className)
 * @method Elements _empty()
 * @method Elements callback($callback, $param1 = null, $param2 = null, $param3 = null)
 * @method string data($key, $value = null)
 * @method Elements removeData($key)
 * @method void rewind()
 * @method Elements current()
 * @method int key()
 * @method Elements next($cssSelector = null)
 * @method bool valid()
 * @method bool offsetExists($offset)
 * @method Elements offsetGet($offset)
 * @method void offsetSet($offset, $value)
 * @method string whois($oneNode)
 * @method Elements dump()
 * @method Elements dumpWhois()
 * @method Elements dumpLength()
 * @method Elements dumpTree($html, $title)
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
        return property_exists($this->elements, $name) ? $this->elements->$name : $this->elements->attr($name);
    }

    public function __call($name, $arguments)
    {
        $obj = call_user_func_array([$this->elements, $name], $arguments);
        if ($obj instanceof phpQueryObject) {
            $obj = new self($obj);
        } else if (is_string($obj)) {
            $obj = trim($obj);
        }
        return $obj;
    }

    /**
     * Iterating elements
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->elements as $key => $element) {
            $break = $callback(new self(pq($element)), $key);
            if ($break === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Iterating elements
     *
     * @param $callback
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    public function map($callback)
    {
        $collection = new Collection();
        $this->elements->each(function ($dom) use (& $collection, $callback) {
            $collection->push($callback(new self(pq($dom))));
        });
        return $collection;
    }

    /**
     * Gets the attributes of all the elements
     *
     * @param string $attr HTML attribute name
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    public function attrs($attr)
    {
        return $this->map(function ($item) use ($attr) {
            return $item->attr($attr);
        });
    }

    /**
     * Gets the text of all the elements
     *
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    public function texts()
    {
        return $this->map(function ($item) {
            return trim($item->text());
        });
    }

    /**
     * Gets the html of all the elements
     *
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    public function htmls()
    {
        return $this->map(function ($item) {
            return trim($item->html());
        });
    }

    /**
     * Gets the htmlOuter of all the elements
     *
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    public function htmlOuters()
    {
        return $this->map(function ($item) {
            return trim($item->htmlOuter());
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