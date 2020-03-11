<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/21
 */

namespace QL\Dom;

use Tightenco\Collect\Support\Collection;
use phpQuery;
use QL\QueryList;
use Closure;

class Query
{
    protected $html;
    protected $document;
    protected $rules;
    protected $range = null;
    protected $ql;
    /**
     * @var Collection
     */
    protected $data;


    public function __construct(QueryList $ql)
    {
        $this->ql = $ql;
    }

    /**
     * @return mixed
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param $html
     * @param null $charset
     * @return QueryList
     */
    public function setHtml($html, $charset = null)
    {
        $this->html = value($html);
        $this->document = phpQuery::newDocumentHTML($this->html,$charset);
        return $this->ql;
    }

    /**
     * Get crawl results
     *
     * @param Closure|null $callback
     * @return Collection|static
     */
    public function getData(Closure $callback = null)
    {
        return  is_null($callback) ? $this->data : $this->data->map($callback);
    }

    /**
     * @param Collection $data
     */
    public function setData(Collection $data)
    {
        $this->data = $data;
    }


    /**
     * Searches for all elements that match the specified expression.
     *
     * @param $selector A string containing a selector expression to match elements against.
     * @return Elements
     */
    public function find($selector)
    {
        return (new Dom($this->document))->find($selector);
    }

    /**
     * Set crawl rule
     *
     * $rules = [
     *    'rule_name1' => ['selector','HTML attribute | text | html','Tag filter list','callback'],
     *    'rule_name2' => ['selector','HTML attribute | text | html','Tag filter list','callback'],
     *    // ...
     *  ]
     *
     * @param array $rules
     * @return QueryList
     */
    public function rules(array $rules)
    {
        $this->rules = $rules;
        return $this->ql;
    }


    /**
     * Set the slice area for crawl list
     *
     * @param $selector
     * @return QueryList
     */
    public function range($selector)
    {
        $this->range = $selector;
        return $this->ql;
    }

    /**
     * Remove HTML head,try to solve the garbled
     *
     * @return QueryList
     */
    public function removeHead()
    {
        $html = preg_replace('/<head.+?>.+<\/head>/is','<head></head>',$this->html);
        $this->setHtml($html);
        return $this->ql;
    }

    /**
     * Execute the query rule
     *
     * @param Closure|null $callback
     * @return QueryList
     */
    public function query(Closure $callback = null)
    {
        $this->data = $this->getList();
        $callback && $this->data = $this->data->map($callback);
        return $this->ql;
    }

    protected function getList()
    {
        $data = [];

        if ( ! empty($this->range)) {
            $root = $this->find($this->range);
        } else {
            $root = new Elements($this->document);
        }

        $i = 0;

        $root->map((function (Elements $element) use (&$data, &$i) {

            foreach ($this->rules as $key => $reg_value) {

                [$selector, $attr, $elementCallback, $htmlCallback, $tags] = $this->getRulesParams($reg_value);

                $htmls = $this->extractElements($element->find($selector), $elementCallback)
                              ->map((function (Elements $element) use ($attr, $tags) {

                                  return $this->extractString($element, $attr, $tags);

                              })->bindTo($this));

                if ($htmlCallback) {
                    $data[$i][$key] = call_user_func($htmlCallback, $htmls, $key);
                } else {
                    $data[$i][$key] = $htmls->join(' ');
                }
            }

            $i++;

        })->bindTo($this));

        return collect($data);
    }

    protected function getRulesParams(array $reg_value)
    {

        $selector = $reg_value[0];
        $attr     = $reg_value[1] ?? '';
        if (isset($reg_value[2]) && $reg_value[2] instanceof Closure) {
            $elementCallback = $reg_value[2];
            $tags            = '';
        } else {
            $elementCallback = null;
            $tags            = $reg_value[2] ?? '';
        }
        $htmlCallback = $reg_value[3] ?? null;

        return [$selector, $attr, $elementCallback, $htmlCallback, $tags];
    }

    /**
     * @param  \QL\Dom\Elements  $elements
     * @param  \Closure|null  $handle
     *
     * @return \QL\Dom\Elements
     */
    protected function extractElements(Elements $elements, Closure $handle = null)
    {
        return $elements->each(function ($dom) use ($handle) {

            if ($handle) {
                /* @var \QL\Dom\Elements $element */
                $element = call_user_func($handle, new Elements(pq($dom)));
                $dom     = $element->getDOMDocument();
            }

        });
    }

    protected function extractString(Elements $element, string $attr, string $tags = '', Closure $handle = null, $key = '')
    {

        switch ($attr) {
            case 'text':
                $html = $this->allowTags($element->html(), $tags);
                break;
            case 'html':
                $html = $this->stripTags($element->html(), $tags);
                break;
            case 'outerHTML':
                $html = $this->stripTags($element->htmlOuter(), $tags);
                break;
            default:
                $html = $element->attr($attr);
                break;
        }

        return $handle ? call_user_func($handle, $html, $key) : $html;

    }

    /**
     * 去除特定的html标签
     * @param  string $html
     * @param  string $tags_str 多个标签名之间用空格隔开
     * @return string
     */
    protected function stripTags($html,$tags_str)
    {
        $tagsArr = $this->tag($tags_str);
        $html = $this->removeTags($html,$tagsArr[1]);
        $p = array();
        foreach ($tagsArr[0] as $tag) {
            $p[]="/(<(?:\/".$tag."|".$tag.")[^>]*>)/i";
        }
        $html = preg_replace($p,"",trim($html));
        return $html;
    }

    /**
     * 保留特定的html标签
     * @param  string $html
     * @param  string $tags_str 多个标签名之间用空格隔开
     * @return string
     */
    protected function allowTags($html,$tags_str)
    {
        $tagsArr = $this->tag($tags_str);
        $html = $this->removeTags($html,$tagsArr[1]);
        $allow = '';
        foreach ($tagsArr[0] as $tag) {
            $allow .= "<$tag> ";
        }
        return strip_tags(trim($html),$allow);
    }

    protected function tag($tags_str)
    {
        $tagArr = preg_split("/\s+/",$tags_str,-1,PREG_SPLIT_NO_EMPTY);
        $tags = array(array(),array());
        foreach($tagArr as $tag)
        {
            if(preg_match('/-(.+)/', $tag,$arr))
            {
                array_push($tags[1], $arr[1]);
            }else{
                array_push($tags[0], $tag);
            }
        }
        return $tags;
    }

    /**
     * 移除特定的html标签
     * @param  string $html
     * @param  array  $tags 标签数组
     * @return string
     */
    protected function removeTags($html,$tags)
    {
        $tag_str = '';
        if(count($tags))
        {
            foreach ($tags as $tag) {
                $tag_str .= $tag_str?','.$tag:$tag;
            }
//            phpQuery::$defaultCharset = $this->inputEncoding?$this->inputEncoding:$this->htmlEncoding;
            $doc = phpQuery::newDocumentHTML($html);
            pq($doc)->find($tag_str)->remove();
            $html = pq($doc)->htmlOuter();
            $doc->unloadDocument();
        }
        return $html;
    }
}