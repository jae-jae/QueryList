<?php
/**
 * Searcher
 *
 * 一个基于QueryList的搜索引擎类
 *
 * @author      Jaeger
 * @email       734708094@qq.com
 * @link        http://git.oschina.net/jae/QueryList
 * @version     1.5.0  
 *
 *@example 
 *
 $hj = Searcher::S('site:pan.baidu.com torrent','sogou',20,2);
  print_r( $hj->jsonArr);

 $json = Searcher::S('QueryList交流')->getJSON();
  print_r($json);
 */
require '../QueryList.class.php';
   class Searcher
   {
       private $searcher;
       private $key;
       private $num;
       private $page;
       private $regArr ;
       private $regRange ;
       private $regZnum;
       public $jsonArr;
       private static $s;

       public static function S($key,$searcher = 'baidu',$num = 10,$page = 1)
       {
          if(!(self::$s instanceof self))
          {
            self::$s = new self();
          }
          self::$s->query($key,$searcher,$num,$page);
          return self::$s;
       }
       private function query($key,$searcher,$num,$page)
       {
           if($searcher=='baidu')
           {
               $this->regArr = array("title"=>array("h3.t a,#ting_singlesong_box a","text"),"tCon"=>array("div.c-abstract,font:slice(0,2),div#weibo,table tr:eq(0),div.c-abstract-size p:eq(0),div.vd_sitcom_new_tinfo","text"),"url"=>array("h3.t a,#ting_singlesong_box a","href"),"host"=>array("div.f13>span.g","text"));
               $this->regRange = '.result,.result-op';
               $this->regZnum=array("zNum"=>array("span.nums","text"));
           }
           else if($searcher=='google')
           {
               $this->regArr = array("title"=>array("h3.r a","text"),"tCon"=>array("span.st","text"),"url"=>array("h3.r a","href"));
               $this->regRange = 'li.g';
               $this->regZnum=array("zNum"=>array("div#resultStats","text"));
           }
           else if($searcher=='sogou')
           {
               $this->regArr = array("title"=>array("h3 a","text"),"tCon"=>array("div.ft","text"),"url"=>array("h3 a","href"));
               $this->regRange = '[id^=rb_]';
               $this->regZnum=array("zNum"=>array("div.mun","text"));
           }
           $this->searcher = $searcher;
           $this->key = $key;
           $this->num  = $num;
           $this->page = $page-1;
           $this->getList();
       }
       private function getList()
       {
           $s = urlencode($this->key);
           $num = $this->num;
           $getHtmlWay = 'get';
           $start = $this->num*$this->page;
           if($this->searcher=='baidu')
           {
               $url = "http://www.baidu.com/s?pn=$start&rn=$num&wd=$s";
               $reg_znum='/[\d,]+/';
           }
           else if($this->searcher=='google')
           {
               $url="https://www.google.com.hk/search?filter=0&lr=&newwindow=1&safe=images&hl=en&as_qdr=all&num=$num&start=$start&q=$s";
               $reg_znum='/([\d,]+) result(s)?/';
               $getHtmlWay = 'curl';
           }
           else if($this->searcher=='sogou')
           {
              $url="http://www.sogou.com/web?query=$s&num=$num&page=".$this->page;
              $reg_znum='/[\d,]+/';
           }
           $searcherObj = QueryList::Query($url,$this->regArr,$this->regRange,$getHtmlWay,false);
           for($i=0;$i<count($searcherObj->jsonArr);$i++)
           {
               if($this->searcher=='baidu')
               {
                   // $searcherObj->jsonArr[$i]['url'] = $this->getBaiduRealURL($searcherObj->jsonArr[$i]['url']);
               }
               else if($this->searcher=='google')
               {
                   $searcherObj->jsonArr[$i]['url'] = $this->getGoogleRealURL($searcherObj->jsonArr[$i]['url']);
               }
           }
           $this->jsonArr = $searcherObj->jsonArr ;

           //获取总共结果条数

           $searcherObj->setQuery($this->regZnum);
           $zNum = $searcherObj->jsonArr[0]['zNum'];
           preg_match($reg_znum,$zNum,$arr)?$zNum=$arr[0]:$zNum=0;
           $zNum = (int)str_replace(',','',$zNum);
           //计算总页数
           $zPage = ceil($zNum/$this->num);
           $this->jsonArr=array('num'=>$this->num,'page'=>((int)$this->page+1),'zNum'=>$zNum,'zPage'=>$zPage,"s"=>"$this->key",'other'=>array('author'=>'JAE','QQ'=>'734708094','blog'=>'http://blog.jaekj.com'),'data'=>$this->jsonArr);


       }
      public function getJSON()
       {
           return json_encode($this->jsonArr);
       }
       private	 function getBaiduRealURL($url)
       {
           //得到百度跳转的真正地址
           $header = get_headers($url,1);
           if (strpos($header[0],'301') || strpos($header[0],'302'))
           {
               if(is_array($header['Location']))
               {
                   //return $header['Location'][count($header['Location'])-1];
                   return $header['Location'][0];
               }
               else
               {
                   return $header['Location'];
               }
           }
           else
           {
               return $url;
           }
       }
       private function getGoogleRealURL($url)
       {
           $reg_url = '/q=(.+)&/U';
           return  preg_match($reg_url,$url,$arr)?urldecode($arr[1]):$url;

       }
   }
   

 
 $hj = Searcher::S('site:pan.baidu.com torrent','sogou',20,2);
  print_r( $hj->jsonArr);