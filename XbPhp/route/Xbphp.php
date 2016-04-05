<?php
/**
 * 静态公共类
 * @author wave
 */
class Xbphp  {
	//初始化
	public static $init = array();
	/**
	 * 解析服務器URL伪静态
	 * @return String
	 * @author wave
	 */
	public static function getServerUrl() {
		header("X-Powered-By:XbPHP");
		if(!empty($_SERVER['ORIG_PATH_INFO'])) {
			$url = $_SERVER['ORIG_PATH_INFO'];
		//windows or linux  nginx apache属性
		}elseif(!empty($_SERVER['PATH_INFO'])) {
			$url = $_SERVER['PATH_INFO'];
		}elseif(!empty($_SERVER['REQUEST_URI'])) {
			$url = $_SERVER['REQUEST_URI'];
		}

		$params = !empty($url) ? explode('/',ltrim(strip_tags($url),'/')) : '';

		if(count($params) == 1) {
			$url_arr = parse_url($url);
			$params[0] = isset($url_arr['path']) ?  ltrim($url_arr['path'],'/') : '';
			$params[1] = isset($url_arr['query']) ?  $url_arr['query'] : '';
		}

		if( isset($params[0]) && strtolower($params[0]) == strtolower(basename(ROOT))) {
			array_splice($params, 0,1);
		}
		if(!empty($params) && isset($params[count($params) - 1]) ) {
			$arr =array_filter(explode('/',$params[count($params) - 1]));
			if(count($arr) >=1  && strpos($params[count($params) - 1],'.') !== false) {
				$params = self::strposReplace($params,'.');
			}
			
			if(count($arr) >=1  && strpos($params[count($params) - 1],'?') !== false) {
				$params = self::strposReplace($params,'?');
			}
		}
		 //删除目录文件
		if(isset($params['0']) && strtolower($params['0']) == strtolower(APP_PATH)) {
				array_splice($params, 0,1);
				
		}

		$params = array_values(array_filter($params));
		return (is_array($params) && count($params) > 1)  ? $params : '';
	}

	/**
	 * 截取数组最后一个元素,并替换成空
	 * @param Array $params  要截取的数组
	 * @param int $str  要截取的字符穿
	 * @return Array
	 * @author wave
	 */
	public static function strposReplace($params,$str) {
		$str = substr($params[count($params) - 1],strpos($params[count($params) - 1],'.'));
		$params[count($params)- 1] = str_replace($str,'',$params[count($params) - 1]);
		return $params;
	}

	/**
	 * 缓存初始化对象
	 * @param string $obj 类名
	 * @return object
	 * @author wave
	 */
	public static function run_cache($obj) {
		if(!in_array($obj,self::$init) && class_exists($obj)){
			self::$init[$obj] = new $obj();
		}
		return isset(self::$init[$obj]) ? self::$init[$obj] : '';
	}

	/**
	 * 获取URL目录
	 * @author wave
	 */
	public static function getUrlPath() {
		if(isset($_SERVER['REDIRECT_URL'])) {
			//这个是linux或windows自动获取目录
			$pathinfo = $_SERVER['REDIRECT_URL']; 
			$arr = array_values(array_filter(explode('/',ltrim(strip_tags($pathinfo),'/'))));
			if(isset($arr['0']) && strtolower($arr['0']) == strtolower(ROOT_PATH)) {
				array_splice($arr,0,1);
			}
		}else { //单独LINUX动态记录目录
			$_SERVER['PHP_SELF'] = str_replace(array('/','index.php'), '', $_SERVER['PHP_SELF']);
			if(!empty($_SERVER['PHP_SELF'])) {
				$arr = array_values(array_filter(explode('/', $_SERVER['PHP_SELF'])));
				if(count($arr) >= 1) {
					if(strtolower($arr['0']) == strtolower(basename(ROOT))) {
						array_splice($arr, 0,1);
					}
				}
			}else { //windows nginx 偽靜態
				$arr =  array_values(array_filter(explode('/',ltrim(strip_tags($_SERVER['REQUEST_URI']),'/'))));
				if(isset($arr['0']) && strtolower($arr['0']) == strtolower(basename(ROOT))) {
					array_splice($arr,0,1);
				}
			}
		}

		if(isset($arr['0']) && file_exists(ROOT.DS.$arr['0'].DS)) {
			define('APP_PATH',$arr['0']);
		}
	}

	/**
	 * 获取框架加载完成的消耗的内存
	 * @return int
	 * @author wave
	 */
	public static function endMemory() {
		return memory_get_usage(true);
	}

	/**
	 * 获取框架消耗的内存
	 * @return string
	 * @author wave
	 */
	public static function memory($end = ''){
		if(!empty($end)) {
			$size = $end - START_MEMORY;
			$unit = array('b','kb','mb','gb','tb','pb'); 
			return round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i]; 
		} 
		
	}
	/**
	 * 转跳的URL
	 * @param string $url 要访问的URL
	 * @param int $type 类型 1为动态URL访问,2为伪静态访问
	 * @param Array $option 数组参数,下标为2开始,如果下标数字下于2则会失效
	 */
	public static function toUrl($url = '',$type = 1,$option = array()) {
		if(empty($url)) {
			return '';
		}
		if(strpos($url, '&') !== false && $type != 1){
			$url = (substr($url, 0,1) === '?') ? substr($url, 1) : $url;
		}
		switch ($type) {
			case 1: //为动态访问
				if(strpos($url, '/') !== false) {
					$urlArr = array_values(array_filter(explode('/', $url)));
					$dataArr = array();
					foreach ($urlArr as $key => $value) {
						switch ($key) {
							case 0:
								$dataArr[M] = $value;
								break;
							case 1:
								$dataArr[A] = $value;
								break;
							default:
								$key = (!empty($option) && isset($option[$key])) ? $option[$key] : $key;
								$dataArr[$key] = $value;
								break;
						}
					}
					$url = '?'.http_build_query($dataArr);
				}
				break;
			case 2:
				if(strpos($url, '&') !== false){
					$urlArr = explode('&', $url);
					$str  = '';
					foreach($urlArr as $key => $value) {
						$valArr = explode('=', $value);
						$str .= (!empty($option) && isset($option[$key])) ? '/'.$option[$key].'/'.$valArr[1] : '/'.$valArr[1];
					}
					$url = $str;
				}
				break;
		}
		return $url;
	}
}