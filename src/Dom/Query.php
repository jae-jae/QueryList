<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/21
 */

namespace QL\Dom;

use Tightenco\Collect\Support\Collection;
use phpQuery;
use phpQueryObject;
use QL\QueryList;
use Closure;

class Query
{
    protected $html;
    /**
     * @var \phpQueryObject
     */
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
     * @param bool $rel
     * @return String
     */
    public function getHtml($rel = true)
    {
        return $rel ? $this->document->htmlOuter() : $this->html;
    }

    /**
     * @param $html
     * @param null $charset
     * @return QueryList
     */
    public function setHtml($html, $charset = null)
    {
        $this->html = value($html);
        $this->destroyDocument();
        $this->document = phpQuery::newDocumentHTML($this->html, $charset);
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
        return $this->handleData($this->data, $callback);
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
        $html = preg_replace('/<head.+?>.+<\/head>/is', '<head></head>', $this->html);
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
        $this->data = $this->handleData($this->data, $callback);
        return $this->ql;
    }

    public function handleData(Collection $data, $callback)
    {
        if (is_callable($callback)) {
            if (empty($this->range)) {
                $data = new Collection($callback($data->all(), null));
            } else {
                $data = $data->map($callback);
            }
        }

        return $data;
    }

    protected function getList()
    {
        $data = [];
        if (empty($this->range)) {
            foreach ($this->rules as $key => $reg_value) {
                $rule = $this->parseRule($reg_value);
                $contentElements = $this->document->find($rule['selector']);
                $data[$key] = $this->extractContent($contentElements, $key, $rule);
            }
        } else {
            $rangeElements = $this->document->find($this->range);
            $i = 0;
            foreach ($rangeElements as $element) {
                foreach ($this->rules as $key => $reg_value) {
                    $rule = $this->parseRule($reg_value);
                    $contentElements = pq($element)->find($rule['selector']);
                    $data[$i][$key] = $this->extractContent($contentElements, $key, $rule);
                }
                $i++;
            }
        }

        return new Collection($data);
    }

    protected function extractContent(phpQueryObject $pqObj, $ruleName, $rule)
    {
        switch ($rule['attr']) {
            case 'text':
                $content = $this->allowTags($pqObj->html(), $rule['filter_tags']);
                break;
            case 'texts':
                $content = (new Elements($pqObj))->map(function (Elements $element) use ($rule) {
                    return $this->allowTags($element->html(), $rule['filter_tags']);
                })->all();
                break;
            case 'html':
                $content = $this->stripTags($pqObj->html(), $rule['filter_tags']);
                break;
            case 'htmls':
                $content = (new Elements($pqObj))->map(function (Elements $element) use ($rule) {
                    return $this->stripTags($element->html(), $rule['filter_tags']);
                })->all();
                break;
            case 'htmlOuter':
                $content = $this->stripTags($pqObj->htmlOuter(), $rule['filter_tags']);
                break;
            case 'htmlOuters':
                $content = (new Elements($pqObj))->map(function (Elements $element) use ($rule) {
                    return $this->stripTags($element->htmlOuter(), $rule['filter_tags']);
                })->all();
                break;
            default:
                if(preg_match('/attr\((.+)\)/', $rule['attr'], $arr)) {
                    $content = $pqObj->attr($arr[1]);
                } elseif (preg_match('/attrs\((.+)\)/', $rule['attr'], $arr)) {
                    $content = (new Elements($pqObj))->attrs($arr[1])->all();
                } else {
                    $content = $pqObj->attr($rule['attr']);
                }
                break;
        }

        if (is_callable($rule['handle_callback'])) {
            $content = call_user_func($rule['handle_callback'], $content, $ruleName);
        }

        return $content;
    }

    protected function parseRule($rule)
    {
        $result = [];
        $result['selector'] = $rule[0];
        $result['attr'] = $rule[1];
        $result['filter_tags'] = $rule[2] ?? '';
        $result['handle_callback'] = $rule[3] ?? null;

        return $result;
    }

    /**
     * 去除特定的html标签
     * @param string $html
     * @param string $tags_str 多个标签名之间用空格隔开
     * @return string
     */
    protected function stripTags($html, $tags_str)
    {
        $tagsArr = $this->tag($tags_str);
        $html = $this->removeTags($html, $tagsArr[1]);
        $p = array();
        foreach ($tagsArr[0] as $tag) {
            $p[] = "/(<(?:\/" . $tag . "|" . $tag . ")[^>]*>)/i";
        }
        $html = preg_replace($p, "", trim($html));
        return $html;
    }

    /**
     * 保留特定的html标签
     * @param string $html
     * @param string $tags_str 多个标签名之间用空格隔开
     * @return string
     */
    protected function allowTags($html, $tags_str)
    {
        $tagsArr = $this->tag($tags_str);
        $html = $this->removeTags($html, $tagsArr[1]);
        $allow = '';
        foreach ($tagsArr[0] as $tag) {
            $allow .= "<$tag> ";
        }
        return strip_tags(trim($html), $allow);
    }

    protected function tag($tags_str)
    {
        $tagArr = preg_split("/\s+/", $tags_str, -1, PREG_SPLIT_NO_EMPTY);
        $tags = array(array(), array());
        foreach ($tagArr as $tag) {
            if (preg_match('/-(.+)/', $tag, $arr)) {
                array_push($tags[1], $arr[1]);
            } else {
                array_push($tags[0], $tag);
            }
        }
        return $tags;
    }

    /**
     * 移除特定的html标签
     * @param string $html
     * @param array $tags 标签数组
     * @return string
     */
    protected function removeTags($html, $tags)
    {
        $tag_str = '';
        if (count($tags)) {
            foreach ($tags as $tag) {
                $tag_str .= $tag_str ? ',' . $tag : $tag;
            }
//            phpQuery::$defaultCharset = $this->inputEncoding?$this->inputEncoding:$this->htmlEncoding;
            $doc = phpQuery::newDocumentHTML($html);
            pq($doc)->find($tag_str)->remove();
            $html = pq($doc)->htmlOuter();
            $doc->unloadDocument();
        }
        return $html;
    }

    protected function destroyDocument()
    {
        if ($this->document instanceof phpQueryObject) {
            $this->document->unloadDocument();
        }
    }

    public function __destruct()
    {
        $this->destroyDocument();
    }
}
