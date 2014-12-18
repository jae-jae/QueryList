<?php
/**
 * QueryList
 *
 * 一个基于phpQuery的通用列表采集类
 * 
 * @author 			Jaeger
 * @email 			734708094@qq.com
 * @link            http://git.oschina.net/jae/QueryList
 * @version         2.2.1     
 *
 * @example 
 *
 //获取CSDN移动开发栏目下的文章列表标题
$hj = QueryList::Query('http://mobile.csdn.net/',array("title"=>array('.unit h1','text')));
print_r($hj->jsonArr);

//回调函数1
function callfun1($content,$key)
{
    return '回调函数1：'.$key.'-'.$content;
}
class HJ{
    //回调函数2
    static public function callfun2($content,$key)
    {
        return '回调函数2：'.$key.'-'.$content;
    }
}
//获取CSDN文章页下面的文章标题和内容
$url = 'http://www.csdn.net/article/2014-06-05/2820091-build-or-buy-a-mobile-game-backend';
$reg = array(
    'title'=>array('h1','text','','callfun1'),    //获取纯文本格式的标题,并调用回调函数1                   
    'summary'=>array('.summary','text','-input strong'), //获取纯文本的文章摘要，但保strong标签并去除input标签
    'content'=>array('.news_content','html','div a -.copyright'),    //获取html格式的文章内容，但过滤掉div和a标签,去除类名为copyright的元素
    'callback'=>array('HJ','callfun2')      //调用回调函数2作为全局回调函数
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
    private function __construct() {
    }
    /**
     * 静态方法，访问入口
     * @param string $page            要抓取的网页URL地址(支持https);或者是html源代码
     * @param array  $regArr         【选择器数组】说明：格式array("名称"=>array("选择器","类型"[,"标签过滤列表"][,"回调函数"]),.......[,"callback"=>"全局回调函数"]);
     *                               【选择器】说明:可以为任意的jQuery选择器语法
     *                               【类型】说明：值 "text" ,"html" ,"HTML标签属性" ,
     *                               【标签过滤列表】:可选，当标签名前面添加减号(-)时（此时标签可以为任意的元素选择器），表示移除该标签以及标签内容，否则当【类型】值为text时表示需要保留的HTML标签，为html时表示要过滤掉的HTML标签
     *                               【回调函数】/【全局回调函数】：可选，字符串（函数名） 或 数组（array("类名","类的静态方法")），回调函数应有俩个参数，第一个参数是选择到的内容，第二个参数是选择器数组下标，回调函数会覆盖全局回调函数
     *
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
        // $this->html = $this->_removeTags($this->html,array('script','style'));
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
                    if($key=='callback')continue;
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

                    if(isset($reg_value[3])){
                        $this->jsonArr[$i][$key] = call_user_func($reg_value[3],$this->jsonArr[$i][$key],$key);
                    }else if(isset($this->regArr['callback'])){
                        $this->jsonArr[$i][$key] = call_user_func($this->regArr['callback'],$this->jsonArr[$i][$key],$key);
                    }
                }
                //重置数组指针
                reset($this->regArr);
                $i++;
            }
        } else {
            while (list($key, $reg_value) = each($this->regArr)) {
                if($key=='callback')continue;
                $hobj = phpQuery::newDocumentHTML($this->html);
                $tags = isset($reg_value[2])?$reg_value[2]:'';
                $lobj = pq($hobj)->find($reg_value[0]);
                $i = 0;
                foreach ($lobj as $item) {
                    switch ($reg_value[1]) {
                    case 'text':
                        $this->jsonArr[$i][$key] = $this->_allowTags(pq($item)->html(),$tags);
                        break;
                    case 'html':
                        $this->jsonArr[$i][$key] = $this->_stripTags(pq($item)->html(),$tags);
                        break;
                    default:
                        $this->jsonArr[$i][$key] = pq($item)->attr($reg_value[1]);
                        break;
                    }

                    if(isset($reg_value[3])){
                        $this->jsonArr[$i][$key] = call_user_func($reg_value[3],$this->jsonArr[$i][$key],$key);
                    }else if(isset($this->regArr['callback'])){
                        $this->jsonArr[$i][$key] = call_user_func($this->regArr['callback'],$this->jsonArr[$i][$key],$key);
                    }

                    $i++;
                }
            }
        }
        if ($this->outputEncoding) {
            //编码转换
            $this->jsonArr = $this->_arrayConvertEncoding($this->jsonArr, $this->outputEncoding, $this->htmlEncoding);
        }
        phpQuery::$documents = array();
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
     * 转换数组值的编码格式
     * @param  array $arr           
     * @param  string $toEncoding   
     * @param  string $fromEncoding 
     * @return array                
     */
    private function _arrayConvertEncoding($arr, $toEncoding, $fromEncoding)
    {
        eval('$arr = '.iconv($fromEncoding, $toEncoding.'//IGNORE', var_export($arr,TRUE)).';');
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
     * @param  string $tags_str 多个标签名之间用空格隔开
     * @return string       
     */
    private function _stripTags($html,$tags_str)
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
    private function _allowTags($html,$tags_str)
    {
        $tagsArr = $this->_tag($tags_str);
        $html = $this->_removeTags($html,$tagsArr[1]);
        $allow = '';
        foreach ($tagsArr[0] as $tag) {
            $allow .= "<$tag> ";
        }
        return strip_tags(trim($html),$allow);
    }
    private function _tag($tags_str)
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
    private function _removeTags($html,$tags)
    {
        $tag_str = '';
        if(count($tags))
        {
            foreach ($tags as $tag) {
                $tag_str .= $tag_str?','.$tag:$tag;
            }
            phpQuery::$defaultCharset = $this->htmlEncoding;
            $doc = phpQuery::newDocumentHTML($html);
            pq($doc)->find($tag_str)->remove();
            $html = pq($doc)->htmlOuter();
            $doc->unloadDocument();
        }
        return $html;
    }
}



