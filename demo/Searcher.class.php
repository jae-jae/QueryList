<?php
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

       function Searcher($searcher,$key,$num,$page)
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
           $searcherObj = new QueryList($url,$this->regArr,$this->regRange,$getHtmlWay,false);
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
       function getJSON()
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
   
$hj = new Searcher('baidu','site:pan.baidu.com torrent',20,2);
 print_r( $hj->jsonArr);
