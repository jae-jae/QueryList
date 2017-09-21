<?php
/**
 * Created by PhpStorm.
 * User: Jaeger <JaegerCode@gmail.com>
 * Date: 2017/9/21
 */

namespace QL\Dom;

use phpQuery;
use QL\QueryList;

class Query
{
    protected $html;
    protected $document;
    protected $rules;
    protected $range = null;
    protected $ql;


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

    public function setHtml($html)
    {
        $this->html = value($html);
        $this->document = phpQuery::newDocumentHTML($this->html);
        return $this->ql;
    }

    public function find($selector)
    {
        return (new Dom($this->document))->find($selector);
    }

    public function rules(array $rules)
    {
        $this->rules = $rules;
        return $this->ql;
    }


    public function range($range)
    {
        $this->range = $range;
        return $this->ql;
    }

    public function removeHead()
    {
        $html = preg_replace('/<head.+?>.+<\/head>/is','<head></head>',$this->html);
        $this->setHtml($html);
        return $this->ql;
    }

    public function query($callback = null)
    {
        $data = $this->getList();
        return is_null($callback)?$data:$data->map($callback);
    }

    protected function getList()
    {
        $data = [];
        $document = $this->document;
        if (!empty($this->range)) {
            $robj = pq($document)->find($this->range);
            $i = 0;
            foreach ($robj as $item) {
                foreach ($this->rules as $key => $reg_value){
                    $tags = $reg_value[2] ?? '';
                    $iobj = pq($item)->find($reg_value[0]);
                    switch ($reg_value[1]) {
                        case 'text':
                            $data[$i][$key] = $this->allowTags(pq($iobj)->html(),$tags);
                            break;
                        case 'html':
                            $data[$i][$key] = $this->stripTags(pq($iobj)->html(),$tags);
                            break;
                        default:
                            $data[$i][$key] = pq($iobj)->attr($reg_value[1]);
                            break;
                    }

                    if(isset($reg_value[3])){
                        $data[$i][$key] = call_user_func($reg_value[3],$data[$i][$key],$key);
                    }
                }
                $i++;
            }
        } else {
            foreach ($this->rules as $key => $reg_value){
                $tags = $reg_value[2] ?? '';
                $lobj = pq($document)->find($reg_value[0]);
                $i = 0;
                foreach ($lobj as $item) {
                    switch ($reg_value[1]) {
                        case 'text':
                            $data[$i][$key] = $this->allowTags(pq($item)->html(),$tags);
                            break;
                        case 'html':
                            $data[$i][$key] = $this->stripTags(pq($item)->html(),$tags);
                            break;
                        default:
                            $data[$i][$key] = pq($item)->attr($reg_value[1]);
                            break;
                    }

                    if(isset($reg_value[3])){
                        $data[$i][$key] = call_user_func($reg_value[3],$data[$i][$key],$key);
                    }

                    $i++;
                }
            }
        }
//        phpQuery::$documents = array();
        return collect($data);
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
            phpQuery::$defaultCharset = $this->inputEncoding?$this->inputEncoding:$this->htmlEncoding;
            $doc = phpQuery::newDocumentHTML($html);
            pq($doc)->find($tag_str)->remove();
            $html = pq($doc)->htmlOuter();
            $doc->unloadDocument();
        }
        return $html;
    }
}