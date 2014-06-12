<?php
/**
 * QueryList
 *
 * 一个基于phpQuery的通用列表采集类
 * 
 * @author 			Jaeger
 * @email 			734708094@qq.com
 * @link            http://git.oschina.net/jae/QueryList
 * @version         2.0.0     
 *
 * @example 
 *
 //获取CSDN移动开发栏目下的文章列表标题
$hj = QueryList::Query('http://mobile.csdn.net/',array("title"=>array('.unit h1','text')));
print_r($hj->jsonArr);

//获取CSDN文章页下面的文章标题和内容
$url = 'http://www.csdn.net/article/2014-06-05/2820091-build-or-buy-a-mobile-game-backend';
$reg = array(
    'title'=>array('h1','text'),    //获取纯文本格式的标题                   
    'summary'=>array('.summary','text','input strong'), //获取纯文本的文章摘要，但保留input和strong标签
    'content'=>array('.news_content','html','div a')    //获取html格式的文章内容，但过滤掉div和a标签
    );
$rang = '.left';
$hj = QueryList::Query($url,$reg,$rang,'curl');
print_r($hj->jsonArr);

//继续获取右边相关热门文章列表的标题以及链接地址
$hj->setQuery(array('title'=>array('','text'),'url'=>array('a','href')),'#con_two_2 li');
//输出json数据
echo $hj->getJson();
 */
require 'phpQuery/phpQuery.php';
class QueryList
{
    private $regArr;
    public $jsonArr;
    private $regRange;
    private $html;
    private $outputEncoding;
    private $htmlEncoding;
    private static $ql;
    /**
     * 静态方法，访问入口
     * @param string $page            要抓取的网页URL地址(支持https);或者是html源代码
     * @param array  $regArr         【选择器数组】说明：格式array("名称"=>array("选择器","类型"[,"标签列表"]),.......),【类型】说明：值 "text" ,"html" ,"属性" ,【标签列表】:可选，当【类型】值为text时表示需要保留的HTML标签，为html时表示要过滤掉的HTML标签
     * @param string $regRange       【块选择器】：指 先按照规则 选出 几个大块 ，然后再分别再在块里面 进行相关的选择
     * @param string $getHtmlWay     【源码获取方式】指是通过curl抓取源码，还是通过file_get_contents抓取源码
     * @param string $outputEncoding【输出编码格式】指要以什么编码输出(UTF-8,GB2312,.....)，防止出现乱码,如果设置为 假值 则不改变原字符串编码
     */
    public static function Query($page, $regArr, $regRange = '', $getHtmlWay = 'curl', $outputEncoding = false)
    {
        if(!(self::$ql instanceof self))
        {
            self::$ql = new self();
        }
        self::$ql->_query($page, $regArr, $regRange, $getHtmlWay, $outputEncoding);
        return self::$ql;
    }
    /**
     * 重新设置选择器
     * @param array $regArr   选择器数组
     * @param string $regRange 块选择器
     */
    public function setQuery($regArr, $regRange = '')
    {
        $this->jsonArr = array();
        $this->regArr = $regArr;
        $this->regRange = $regRange;
        $this->_getList();
    }
    /**
     * 得到JSON结构的结果
     * @return string
     */
    public function getJSON()
    {
        return json_encode($this->jsonArr);
    }
    private function _query($page, $regArr, $regRange, $getHtmlWay, $outputEncoding)
    {
        $this->jsonArr = array();
        $this->outputEncoding = $outputEncoding;
        if ($this->_isURL($page)) {
            if ($getHtmlWay == 'curl') {
                //为了能获取https://
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $page);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $this->html = curl_exec($ch);
                curl_close($ch);
            } else {
                $this->html = file_get_contents($page);
            }
        } else {
            $this->html = $page;
        }
        //获取编码格式
        $this->htmlEncoding = $this->_getEncode($this->html);
        if (!empty($regArr)) {
            $this->regArr = $regArr;
            $this->regRange = $regRange;
            $this->_getList();
        }
    }
    private function _getList()
    {
        $hobj = phpQuery::newDocumentHTML($this->html);
        if (!empty($this->regRange)) {
            $robj = pq($hobj)->find($this->regRange);
            $i = 0;
            foreach ($robj as $item) {
                while (list($key, $reg_value) = each($this->regArr)) {
                    $tags = isset($reg_value[2])?$reg_value[2]:'';
                    $iobj = pq($item)->find($reg_value[0]);
                    switch ($reg_value[1]) {
                    case 'text':
                        $this->jsonArr[$i][$key] = $this->_allowTags(pq($iobj)->html(),$tags);
                        break;
                    case 'html':
                        $this->jsonArr[$i][$key] = $this->_stripTags(pq($iobj)->html(),$tags);
                        break;
                    default:
                        $this->jsonArr[$i][$key] = pq($iobj)->attr($reg_value[1]);
                        break;
                    }
                }
                //重置数组指针
                reset($this->regArr);
                $i++;
            }
        } else {
            while (list($key, $reg_value) = each($this->regArr)) {
                $tags = isset($reg_value[2])?$reg_value[2]:'';
                $lobj = pq($hobj)->find($reg_value[0]);
                $i = 0;
                foreach ($lobj as $item) {
                    switch ($reg_value[1]) {
                    case 'text':
                        $this->jsonArr[$i++][$key] = $this->_allowTags(pq($item)->html(),$tags);
                        break;
                    case 'html':
                        $this->jsonArr[$i++][$key] = $this->_stripTags(pq($item)->html(),$tags);
                        break;
                    default:
                        $this->jsonArr[$i++][$key] = pq($item)->attr($reg_value[1]);
                        break;
                    }
                }
            }
        }
        if ($this->outputEncoding) {
            //编码转换
            $this->jsonArr = $this->_arrayConvertEncoding($this->jsonArr, $this->outputEncoding, $this->htmlEncoding);
        }
    }
    /**
     * 获取文件编码
     * @param $string
     * @return string
     */
    private function _getEncode($string)
    {
        return mb_detect_encoding($string, array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
    }
    /**
     * 递归转换数组值得编码格式
     * @param  array $arr           
     * @param  string $toEncoding   
     * @param  string $fromEncoding 
     * @return array                
     */
    private function _arrayConvertEncoding($arr, $toEncoding, $fromEncoding)
    {
        if (!is_array($arr)) {
            return $arr;
        }
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = $this->_arrayConvertEncoding($value, $toEncoding, $fromEncoding);
            } else {
                $arr[$key] = mb_convert_encoding($value, $toEncoding, $fromEncoding);
            }
        }
        return $arr;
    }
    /**
     * 简单的判断一下参数是否为一个URL链接
     * @param  string  $str 
     * @return boolean      
     */
    private function _isURL($str)
    {
        if (preg_match('/^http(s)?:\\/\\/.+/', $str)) {
            return true;
        }
        return false;
    }
    /**
     * 去除特定的html标签
     * @param  string $html 
     * @param  string $tags 多个标签名之间用空格隔开
     * @return string       
     */
    private function _stripTags($html,$tags)
    {
        $tagsArr = preg_split("/\s+/",$tags,-1,PREG_SPLIT_NO_EMPTY);
        $p = array();
        foreach ($tagsArr as $tag) {  
            $p[]="/(<(?:\/".$tag."|".$tag.")[^>]*>)/i";  
        }  
        $html = preg_replace($p,"",trim($html));  
        return $html;  
    }
    /**
     * 保留特定的html标签
     * @param  string $html 
     * @param  string $tags 多个标签名之间用空格隔开
     * @return string       
     */
    private function _allowTags($html,$tags)
    {
        $tagsArr = preg_split("/\s+/",$tags,-1,PREG_SPLIT_NO_EMPTY);
        $allow = '';
        foreach ($tagsArr as $tag) {
            $allow .= "<$tag> ";
        }
        return strip_tags(trim($html),$allow);
    }
}

