# QueryList简介
***
`QueryList`是一个基于`phpQuery`的通用列表采集类,是一个简单、 灵活、强大的采集工具，采集任何复杂的页面     基本上就一句话就能搞定了。

# QueryList 安装
通过`composer`安装:
```
composer require jaeger/querylist
```
更多安装方法:[QueryList多种安装方式](https://doc.querylist.cc/site/index/doc/7)

# QueryList 使用
下面演示`QueryList`用一句代码采集百度搜索结果：
```php
//获取采集对象
$hj = QueryList::Query('http://www.baidu.com/s?wd=QueryList',array(
        'title'=>array('h3','text'),
        'link'=>array('h3>a','href')
    ));
//输出结果：二维关联数组
print_r($hj->data);
```
上面的代码实现的功能是采集百度搜索结果页面的所有搜索结果的`标题`和`链接`,然后分别以二维关联数组的格式输出。

采集结果:
```
Array
(
    [0] => Array
        (
            [title] => QueryList|基于phpQuery的无比强大的PHP采集工具
            [link] => http://www.baidu.com/link?url=IIsMhpzI2PylnmW8vPALcwIfJgHhKFu2SWXEj7yQ-6o7KStbLfmuoWGmalpx1xYE
        )

    [1] => Array
        (
            [title] => 介绍- QueryList指导文档
            [link] => http://www.baidu.com/link?url=edktLqt6f9KwYJ6oip1EDXvwIXh-nHcFImVJeqRm56-VU3zIcqLRYeM83VyYQE_X
        )

  //省略....

)
```
## Query() 静态方法
返回值:`QueryList对象`

Query方法为`QueryList`唯一的主方法，用静态的方式调用。

**原型:**
> QueryList::Query($page,array $rules, $range = '', $outputEncoding = null, $inputEncoding = null,$removeHead = false)

**中文解释:**
```
QueryList::Query(采集的目标页面,采集规则[,区域选择器][，输出编码][，输入编码][，是否移除头部])
//采集规则
$rules = array(
   '规则名' => array('jQuery选择器','要采集的属性'[,"标签过滤列表"][,"回调函数"]),
   '规则名2' => array('jQuery选择器','要采集的属性'[,"标签过滤列表"][,"回调函数"]),
    ..........
    [,"callback"=>"全局回调函数"]
);

//注:方括号括起来的参数可选
```
### 参数解释:
查看文档:http://doc.querylist.cc/site/index/doc/11

## QueryList 扩展

**Request 网络操作扩展**

    可以实现如携带cookie、伪造来路等任意复杂的网络请求，再也不用担心QueryList内置的抓取功能太弱了。
    
**Login 模拟登陆扩展**

    可以实现模拟登陆然后采集。

**Multi 多线程插件**

    多线程（多进程）采集扩展。
    
**DImage图片下载扩展**

    可实现简单的图片下载需求。

扩展安装以及使用教程:[QueryList扩展文档](https://doc.querylist.cc/site/index/doc/19)，获取更多扩展可以关注`QueryList`社区和交流群。

## 其它说明
1.`QueryList`内置的只是简单的源码抓取方法，遇到更复杂的抓取情况，如：需要登陆
身份验证 时，请配合其它的PHP的HTTP工具(推荐使用[Guzzle](http://guzzle-cn.readthedocs.io/zh_CN/latest/))来使用，通过将辅助的HTTP类抓取到的网页源码传给`QueryList`即可。

2.采集程序请在PHP命令行模式(PHP CLI)下运行。

3.`QueryList`依赖`phpQuery`,`phpQuery`项目主页:[phpQuery文档](https://code.google.com/p/phpquery/)

## 寻求帮助?
- QueryList交流社区: [http://querylist.cc/](http://querylist.cc/)
- QueryList文档: [http://doc.querylist.cc/](http://doc.querylist.cc/)
- QueryList交流QQ群:123266961 <a target="_blank" href="http://shang.qq.com/wpa/qunwpa?idkey=a1b248ae30b3f711bdab4f799df839300dc7fed54331177035efa0513da027f6"><img border="0" src="http://pub.idqqimg.com/wpa/images/group.png" alt="╰☆邪恶 魔方☆" title="╰☆邪恶 魔方☆"></a>

- Git@OSC:http://git.oschina.net/jae/QueryList
- GitHub:https://github.com/jae-jae/QueryList

## Author
Jaeger <JaegerCode@gmail.com>

## Lisence
QueryList is licensed under the license of MIT. See the LICENSE for more details.