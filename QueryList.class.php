<?php
	/**
	 * QueryList
	 *
	 * 一个基于phpQuery的通用列表采集类
	 * 
	 * @author 			Jaeger
	 * @email 			734708094@qq.com
	 * @link            http://git.oschina.net/jae/QueryList
	 * @version         1.6.0     
	 */
	require('phpQuery/phpQuery.php');
	class QueryList{
		
		 private $pageURL;
		 private $regArr = array();
		 public $jsonArr = array();
		 private $regRange;
		 private $html;
		 private $output_encoding;
		 private $html_encoding;
		 /**
		  * 构造函数
		  * @param string $page            要抓取的网页URL地址(支持https);或者是html源代码
		  * @param array  $regArr         【选择器数组】说明：格式array("名称"=>array("选择器","类型"),.......),【类型】说明：值 "text" ,"html" ,"属性" 
		  * @param string $regRange       【块选择器】：指 先按照规则 选出 几个大块 ，然后再分别再在块里面 进行相关的选择
		  * @param string $getHtmlWay     【源码获取方式】指是通过curl抓取源码，还是通过file_get_contents抓取源码
		  * @param string $output_encoding【输出编码格式】指要以什么编码输出(UTF-8,GB2312,.....)，防止出现乱码,如果设置为 假值 则不改变原字符串编码
		  */
		public function QueryList($page,$regArr,$regRange='',$getHtmlWay="curl",$output_encoding=false)
		 {
		 	
			$this->output_encoding = $output_encoding;
			if($this->isURL($page))
		 	{
				$this->pageURL = $page;
				if($getHtmlWay=="curl")
		        {
		       	 //为了能获取https://
				   $ch = curl_init();
					curl_setopt($ch, CURLOPT_URL,$this->pageURL);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
					curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
					$this->html = curl_exec($ch);
	               curl_close($ch);
		        }else{
		       		$this->html=file_get_contents($this->pageURL);
		        }
		 	}else{
		 		$this->html = $page;
		 	}
	       
		   //获取编码格式
		   $this->html_encoding = $this->get_encode($this->html);


			 if(!empty($regArr))
			 {
			
				  $this->regArr = $regArr;
				 $this->regRange = $regRange;
				 $this->getList();
			 }
			   
		 }
		public function setQuery($regArr,$regRange='')
		 {
			 $this->jsonArr=array();
			 $this->regArr = $regArr;
			 $this->regRange = $regRange;
			 $this->getList();
	     }
	    private function getList()
		 {
			 
             $hobj = phpQuery::newDocumentHTML($this->html);

			 if(!empty($this->regRange))
			 {
			 $robj = pq($hobj)->find($this->regRange);
			
			  $i=0;
			 foreach($robj as $item)
			 {
			     
				 while(list($key,$reg_value)=each($this->regArr))
				 {
					 $iobj = pq($item)->find($reg_value[0]);
					
					   switch($reg_value[1])
					   {
						   case 'text':
						   		 $this->jsonArr[$i][$key] = trim(pq($iobj)->text());
								 break;
				           case 'html':
						  		 $this->jsonArr[$i][$key] = trim(pq($iobj)->html());
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
			 }
			 else
			 {
		    while(list($key,$reg_value)=each($this->regArr))
			 {
				$lobj = pq($hobj)->find($reg_value[0]);
				   
				   
				   $i=0;
				   foreach($lobj as $item)
				   {
					   switch($reg_value[1])
					   {
						   case 'text':
						   		 $this->jsonArr[$i++][$key] = trim(pq($item)->text());
								 break;
				           case 'html':
						  		 $this->jsonArr[$i++][$key] = trim(pq($item)->html());
								 break;
						   default:
						   		$this->jsonArr[$i++][$key] = pq($item)->attr($reg_value[1]);
								break;
						   
						}
					  
					 
				   }
				 
		
			 }
		   }
		   if($this->output_encoding)
		   {
			   //编码转换
			   $this->jsonArr = $this->array_convert_encoding($this->jsonArr,$this->output_encoding,$this->html_encoding);
		   }
		 }	
		public function getJSON()
		 {
			 return json_encode($this->jsonArr);
		 } 
		/**
		 * 获取文件编码
		 * @param $string
		 * @return string
		 */
		private function get_encode($string){
		    return mb_detect_encoding($string, array('ASCII','GB2312','GBK','UTF-8')); 
		}
		/**
		 * 递归转换数组值得编码格式
		 * @param  array $arr           
		 * @param  string $to_encoding   
		 * @param  string $from_encoding 
		 * @return array                
		 */
		private function array_convert_encoding($arr,$to_encoding,$from_encoding)
		{
		    if(!is_array($arr))return $arr;
		    foreach ($arr as $key => $value) {
		        if (is_array($value)) {
		           $arr[$key] = $this->array_convert_encoding($value,$to_encoding,$from_encoding);
		        }else{
		           $arr[$key] = mb_convert_encoding($value, $to_encoding,$from_encoding);
		        }
		    }
		    return $arr;
		}
		/**
		 * 简单的判断一下参数是否为一个URL链接
		 * @param  string  $str 
		 * @return boolean      
		 */
		private function isURL($str)
		{
			if(preg_match('/^http(s)?:\/\/.+/', $str))
			{
				return true;
			}
			return false;
		}
		
}