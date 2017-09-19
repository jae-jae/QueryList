<?php
namespace QL;
use phpQuery;
use QL\Dom\Dom;

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

class QueryList
{
    protected $html;
    protected $document;
    protected $rules;
    protected $range = null;
    protected $isRemoveHead = false;

    /**
     * QueryList constructor.
     */
    public function __construct()
    {
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
     * @return $this
     */
    public function setHtml($html)
    {
        $this->html = $html;
        $this->document = phpQuery::newDocumentHTML($this->html);
        return $this;
    }

    public function find($selector)
    {
        return (new Dom($this->document))->find($selector);
    }

    public function rules(array $rules)
    {
        $this->rules = $rules;
        return $this;
    }


    public function range($range)
    {
        $this->range = $range;
        return $this;
    }

    public function removeHead($isRemoveHead = true)
    {
        $this->isRemoveHead = $isRemoveHead;
        return $this;
    }

    public function query($callback = null)
    {
        $data = $this->_getList();
        return is_null($callback)?$data:$data->map($callback);
    }

    protected function _getList()
    {
        $data = [];
        $document = $this->document;
        if (!empty($this->range)) {
            $robj = pq($document)->find($this->range);
            $i = 0;
            foreach ($robj as $item) {
                while (list($key, $reg_value) = each($this->rules)) {
                    $tags = isset($reg_value[2])?$reg_value[2]:'';
                    $iobj = pq($item)->find($reg_value[0]);

                    switch ($reg_value[1]) {
                        case 'text':
                            $data[$i][$key] = $this->_allowTags(pq($iobj)->html(),$tags);
                            break;
                        case 'html':
                            $data[$i][$key] = $this->_stripTags(pq($iobj)->html(),$tags);
                            break;
                        default:
                            $data[$i][$key] = pq($iobj)->attr($reg_value[1]);
                            break;
                    }

                    if(isset($reg_value[3])){
                        $data[$i][$key] = call_user_func($reg_value[3],$data[$i][$key],$key);
                    }
                }
                //重置数组指针
                reset($this->rules);
                $i++;
            }
        } else {
            while (list($key, $reg_value) = each($this->rules)) {
                $tags = isset($reg_value[2])?$reg_value[2]:'';
                $lobj = pq($document)->find($reg_value[0]);
                $i = 0;
                foreach ($lobj as $item) {
                    switch ($reg_value[1]) {
                        case 'text':
                            $data[$i][$key] = $this->_allowTags(pq($item)->html(),$tags);
                            break;
                        case 'html':
                            $data[$i][$key] = $this->_stripTags(pq($item)->html(),$tags);
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
        phpQuery::$documents = array();
        return collect($data);
    }

    /**
     * 去除特定的html标签
     * @param  string $html
     * @param  string $tags_str 多个标签名之间用空格隔开
     * @return string
     */
    protected function _stripTags($html,$tags_str)
    {
        $tagsArr = $this->_tag($tags_str);
        $html = $this->_removeTags($html,$tagsArr[1]);
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
    protected function _allowTags($html,$tags_str)
    {
        $tagsArr = $this->_tag($tags_str);
        $html = $this->_removeTags($html,$tagsArr[1]);
        $allow = '';
        foreach ($tagsArr[0] as $tag) {
            $allow .= "<$tag> ";
        }
        return strip_tags(trim($html),$allow);
    }

    protected function _tag($tags_str)
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
    protected function _removeTags($html,$tags)
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