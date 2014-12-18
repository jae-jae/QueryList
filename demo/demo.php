<?php
require '../QueryList.class.php';
header('Content-type:text/html;charset=utf-8');
//采集OSC的代码分享列表，标题 链接 作者
$url = "http://www.oschina.net/code/list";
$reg = array("title"=>array(".code_title a:eq(0)","text"),"url"=>array(".code_title a:eq(0)","href"),"author"=>array("img","title"));
$rang = ".code_list li";
//使用curl抓取源码并以GBK编码格式输出
$hj = QueryList::Query($url,$reg,$rang,'curl','GBK');
$arr = $hj->jsonArr;
echo "<pre>";
print_r($arr);
echo "</pre><hr/>";

echo '上面的是GBK格式输出的，而页面是UTF-8格式的，所以会看到输出是乱码！';
echo '<hr/>';

//如果还想采当前页面右边的 TOP40活跃贡献者 图像，得到JSON数据,可以这样写
$reg = array("portrait"=>array(".hot_top img","src"));
$hj->setQuery($reg);
$json = $hj->getJSON();
echo $json . "<hr/>";

//采OSC内容页内容
$url = "http://www.oschina.net/code/snippet_186288_23816";
$reg = array("title"=>array(".QTitle h1","text"),"con"=>array(".Content","html"));
$hj = QueryList::Query($url,$reg);
$arr = $hj->jsonArr;
echo "<pre>";
print_r($arr);
echo "</pre><hr/>";

//抓取网站基本信息
//设置规则
$reg = array(
    //抓取网站keywords
    "kw" => array("meta[name=keywords]","content"),
    //抓取网站描述
    "desc" => array("meta[name=description]","content"),
    //抓取网站标题
    "title" => array("title","text"),
    //抓取网站第一个css link的链接
    "css1" => array("link:eq(0)","href"),
    //抓取网站第二个js link的链接
    "js2" => array("script[src]:eq(1)","src")
  );
//抓取的目标站
$url = 'http://x.44i.cc/';
//抓取
$data = QueryList::Query($url,$reg)->jsonArr;
print_r($data);

//下面单独演示回调函数的用法
//抓取网站keywords并分离每个关键词
$reg = array(
        //抓取网站keywords,并调用自定义函数fun
        "kw" => array("meta[name=keywords]","content",'','fun')
    );
//自定义回调函数
function fun($content,$key){
    //分离关键词
    return explode(',', $content);
}
//抓取的目标站
$url = 'http://x.44i.cc/';
//抓取
$data = QueryList::Query($url,$reg)->jsonArr;
print_r($data);