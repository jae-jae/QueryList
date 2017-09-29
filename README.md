# QueryList V4 简介
`QueryList`是一套简洁、优雅的PHP采集工具，基于phpQuery。

## 特性
- 拥有与jQuery完全相同的CSS3 DOM选择器
- 拥有与jQuery完全相同的DOM操作API
- 拥有通用的列表采集方案
- 拥有强大的HTTP请求套件，轻松实现如：模拟登陆、伪造浏览器、HTTP代理等意复杂的网络请求
- 拥有乱码解决方案
- 拥有强大的内容过滤功能，可使用jQuey选择器来过滤内容
- 拥有高度的模块化设计，扩展性强
- 拥有富有表现力的API
- 拥有高质量文档
- 拥有丰富的插件
- 拥有专业的问答社区和交流群

通过插件可以轻松实现诸如：
- 多线程采集
- 图片本地化
- 模拟浏览器行为，如：提交Form表单
- 网络爬虫
- .....

## 环境要求
- PHP >= 7.0

> 如果你的PHP版本还停留在PHP5，或者不会使用Composer,你可以选择使用QueryList3,QueryList3支持php5.3以及手动安装。
QueryList3 文档:http://v3.querylist.cc

## 安装
通过Composer安装:
```
composer require jaeger/querylist:dev-master
```

## 使用

#### 元素操作
-  采集「昵图网」所有图片地址

```php
QueryList::get('http://www.nipic.com')->find('img')->attrs('src');
```
- 采集百度搜索结果

```php
$ql = QueryList::get('http://www.baidu.com/s?wd=QueryList');

$ql->find('title')->text(); // 获取网站标题
$ql->find('meta[name=keywords]')->content; // 获取网站头部关键词

$ql->find('h3>a')->texts(); //获取搜索结果标题列表
$ql->find('h3>a')->attrs('href'); //获取搜索结果链接列表

$ql->find('img')->src; //获取第一张图片的链接地址
$ql->find('img:eq(1)')->src; //获取第二张图片的链接地址
$ql->find('img')->eq(2)->src; //获取第三张图片的链接地址
// 遍历所有图片
$ql->find('img')->map(function($img){
	echo $img->alt;  //打印图片的alt属性
});
```
- 更多用法

```php
$ql->find('#head')->append('<div>追加内容</div>')->find('div')->htmls();
$ql->find('.two')->children('img')->attrs('alt'); //获取class为two元素下的所有img孩子节点
//遍历class为two元素下的所有孩子节点
$data = $ql->find('.two')->children()->map(function ($item){
    //用is判断节点类型
    if($item->is('a')){
        return $item->text();
    }elseif($item->is('img'))
    {
        return $item->alt;
    }
});

$ql->find('a')->attr('href', 'newVal')->removeClass('className')->html('newHtml')->...
$ql->find('div > p')->add('div > ul')->filter(':has(a)')->find('p:first')->nextAll()->andSelf()->...
$ql->find('div.old')->replaceWith( $ql->find('div.new')->clone() )->appendTo('.trash')->prepend('Deleted')->...
```
#### 列表采集
采集百度搜索结果列表的标题和链接:
```php
$data = QueryList::get('http://www.baidu.com/s?wd=QueryList')
	// 设置采集规则
    ->rules([ 
	    'title'=>array('h3','text'),
	    'link'=>array('h3>a','href')
	])
	->query()->getData();

print_r($data->all());
```
采集结果:
```
Array
(
    [0] => Array
        (
            [title] => QueryList|基于phpQuery的无比强大的PHP采集工具
            [link] => http://www.baidu.com/link?url=GU_YbDT2IHk4ns1tjG2I8_vjmH0SCJEAPuuZNirb4pOqoQ_ekNXilZhbcbKCfkhf
        )
    [1] => Array
        (
            [title] => PHP 用QueryList抓取网页内容 - wb145230 - 博客园
            [link] => http://www.baidu.com/link?url=zn0DXBnrvIF2ibRVW34KcRVFG1_bCdZvqvwIhUqiXaS
        )
    [2] => Array
        (
            [title] => 介绍- QueryList指导文档
            [link] => http://www.baidu.com/link?url=pSypvMovqS4v2sWeQo5fDBJ4EoYhXYi0Lxx-_Yeb8eUj82NZpHTSotC1Uh9hgCQy
        )
        //...
)
```

#### HTTP网络操作

#### Form表单操作

## 寻求帮助?
- QueryList交流社区: [http://querylist.cc/](http://querylist.cc/)
- QueryList文档: [http://doc.querylist.cc/](http://doc.querylist.cc/)
- QueryList交流QQ群:123266961 <a target="_blank" href="http://shang.qq.com/wpa/qunwpa?idkey=a1b248ae30b3f711bdab4f799df839300dc7fed54331177035efa0513da027f6"><img border="0" src="http://pub.idqqimg.com/wpa/images/group.png" alt="╰☆邪恶 魔方☆" title="╰☆邪恶 魔方☆"></a>

- Git@OSC:http://git.oschina.net/jae/QueryList
- GitHub:https://github.com/jae-jae/QueryList

## Author
Jaeger <JaegerCode@gmail.com>

## Lisence
QueryList is licensed under the license of MIT. See the LICENSE for more details.