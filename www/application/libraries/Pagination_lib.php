<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Pagination_lib
{
	var $block_size; //출력블럭사이즈
	var $list_size; //한페이지 row수

	var $total_row = 1; //전체
	var $total_page;

	var $block = array(); //블럭정보
	var $href = array(); //블럭이동정보
	var $page = array(); //페이지 정보(페이지번호, href, 현재페이지 여부)
	var $page_current; //현재출력페이지

	var $url;
	var $url_type = 'php';
	var $query_string;

	var $async = true;

	var $param;

	// function __construct($total, $block_size=10, $list_size=20, $thispage='', $url='', $query_string='', $url_type='php') {
	function __construct($param) {
		$this->param = $param;
		//변수세팅
		$this->total_row = ($param['total']==0)?1:$param['total'];
		$this->block_size = $param['block_size'];
		$this->list_size = $param['list_size'];
		$this->url_type = (isset($param['url_type']))?$param['url_type']:$this->url_type;
		$page_current = ($param['page_current'])?$param['page_current']:1;
		$this->page_current = ($page_current)?$page_current:1;
		// $this->async = $paramp

		// $this->setQueryString();

		//페이지 계산
		$this->total_page = ceil($this->total_row/$this->list_size);

		//페이징 정보 계산
		$this->setUrl();
	}

	function setUrl() {
		if($this->url_type == 'php') {
			if(isset($this->param['url'])) {
				$url = $this->param['url'];
				if(strpos($url,'?')) {
					list($this->url, $query_string) = explode('?',$url);
				}
				else  {
					$this->url=$url;
					$query_string = '';
				}
			}
			else {
				$this->url = ($_SERVER['REDIRECT_URL'])?$_SERVER['REDIRECT_URL']:$_SERVER['PHP_SELF'];
				$query_string = $_SERVER['QUERY_STRING'];
			}


			parse_str($query_string, $query_data);
			unset($query_data['page']);
			$this->query_string = http_build_query($query_data);
		}
		else {
			$this->url = "javascript:".$this->param['url'];
		}

		$this->setPaging();
	}

	private function setPaging() {
		$block_this = ceil($this->page_current/$this->block_size);
		$block_start = (($block_this-1) * $this->block_size) +1;
		$block_end_tmp = $block_start+$this->block_size-1;
		$block_end = ($this->total_page<$block_end_tmp)?$this->total_page:$block_end_tmp;
		$this->query_string = ($this->query_string)?$this->query_string."&page=":"page=";
		for ($i=$block_start;$i<=$block_end;$i++){
			$block_arr[] = array(
				'num'=>$i,
				'href'=>$this->setHref($i),
				'equal'=>($i==$this->page_current)?true:false
			);
		}

		$this->block = $block_arr;
		$this->page['first'] = 1;
		$this->page['last'] = $this->total_page;
		$this->page['next']=($block_end == $this->total_page)?$this->total_page:$block_end+1;
		$this->page['prev'] = ($block_start==1)?1:$block_start-1;

		if($this->url_type == 'php') {
			$href = array(
				'first'=>$this->page['first'],
				'prev'=>$this->page['prev'],
				'next'=>$this->page['next'],
				'last'=>$this->page['last']
			);
		}
		else {
			$href = array(
				'first'=>array(
					'href'=>$this->setHref($this->page['first']),
					'num'=>$this->page['first']
				),
				'prev'=>array(
					'href'=>$this->setHref($this->page['prev']),
					'num'=>$this->page['prev']
				),
				'next'=>array(
					'href'=>$this->setHref($this->page['next']),
					'num'=>$this->page['next']
				),
				'last'=>array(
					'href'=>$this->setHref($this->page['last']),
					'num'=>$this->page['last']
				)
			);
		}

		$this->href = $href;
	}

	private function setHref($i) {
		return ($this->url_type == 'php') ? "HJUtil.href('".$this->url."?".$this->query_string.$i."')" : $this->url."(".$i.")";
	}

	function setQueryString() {
		parse_str($_SERVER['QUERY_STRING'], $parse_arr);
		if (is_array($parse_arr)) {
			foreach ($parse_arr as $k => $v) {
				if ($k == 'page')	continue;
				else if ($k == 'route_path')	continue;
				$param[$k] = $v;
			}
		}

		if (isset($param)) {
			$this->query_string = str_replace("%40","@",http_build_query($param));
		}
	}

	public function publish($size="", $print=true) {
		$page_set = $this->getPageSet();
		if ($size) $cls_size = "pagination-".$size;
		if ($print) {
			include PATH_ENGINE."/manage/template/include/paging.php";
		}
		else {
			ob_start();
			include PATH_ENGINE."/manage/template/include/paging.php";
			$html = ob_get_contents();
			ob_end_clean();
			return $html;
		}
	}

	public function getPageSet() {
		$pageSet = array(
			'block'=>$this->block,
			'first'=>$this->href['first'],
			'prev'=>$this->href['prev'],
			'next'=>$this->href['next'],
			'last'=>$this->href['last'],
			'href'=>$this->href
			// 'num'=> array(
			// 	'total'=>$this->total_page,
			// 	'current'=>$this->page_current
			// ),
			// 'href'=>$this->url.'?'.$this->query_string
		);
		return $pageSet;
	}

}

?>
