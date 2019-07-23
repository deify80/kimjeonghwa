<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Menu_lib {
	private $ci;
	public $top;

	function __construct()
	{
		$this->ci =& get_instance();
		session_start();
		//$this->ci->config->load('menu', TRUE);

		// $menu = $config['menu'];
		// $depth = $config['depth'];
		// $leaf = $config['leaf'];
		// $children = $config['children'];
	}

	private function _set_config() {
		$this->cfg['cmltd_menu'] = $this->ci->config->item('cmltd_menu');
		$this->cfg['cmltd_depth'] = $this->ci->config->item('cmltd_depth');
		// $this->cfg['cmltd_leaf'] = $this->ci->config->item('cmltd_leaf');
		$this->cfg['cmltd_all'] = $this->ci->config->item('cmltd_all');
		$this->cfg['cmltd_href'] = $this->ci->config->item('cmltd_href');
	}

	function get_config($mode) {
		$this->_set_config();
		return $this->cfg['cmltd_'.$mode];
	}

	function set_mk(){
		$menu_href = $this->cfg['cmltd_href'];
			// $this_href = '/'.$CI->uri->uri_string();
			// $route = $menu_href[$this_href];

			// if($route) {
			// 	$mk = $route[2];
			// 	$CI->session->set_userdata('mk',$mk);



			// 	//권한체크
			// 	$grant = explode(',',$CI->menu_lib->cfg_menu[$mk]['sync_grant']);
			// 	array_push($grant, $mk);
			// 	foreach($grant as $k) {
			// 		$grant_view = (in_array($k, explode(',',$CI->session->userdata('ss_menu_grant'))))?true:false; //권한체크
			// 		if($grant_view) break;
			// 	}
			// }
	}

	function set_tree($leaf_allowed) {
		$this->_set_config();

		// $this->_set_mk();

		$cfg_menu = $this->cfg['cmltd_menu'];
		$this->cfg_menu = $cfg_menu;
		$cfg_depth = $this->cfg['cmltd_depth'];
		$cfg_all = $this->cfg['cmltd_all'];
		$cfg_href = $this->cfg['cmltd_href'];
		$leaf = ($leaf_allowed=='all')?$cfg_all:$leaf_allowed;

		if (!$leaf) return false;

		$leaf_arr = explode(',',$leaf);
		$allowed = array();

		$me_href = '/'.$this->ci->uri->uri_string();

		//접속경로로 권한설정이 없는경우
		if(!array_key_exists($me_href, $cfg_href)){
			$me_href = substr($me_href, 0, strripos($me_href,'/'));
			$rs = $this->get_menu_row(array("href LIKE '{$me_href}%'"=>null, 'has_parameter'=>'Y'), 'href, sync_grant');
			$sync_grant = explode(',',$rs['sync_grant']); //동기화 처리 메뉴키를 가져옴

			// $mk = $_SESSION['mk'];
			$mk = $this->ci->session->userdata("mk");

			if(!$rs) $me_href = '';
			else {
				$href = $rs['href'];
				$grant_href = $this->ci->session->userdata("grant_href");
				if(in_array($mk, $sync_grant)) { //이전경로(리퍼러) 메뉴키가 동기화되어 있는경우
					if(is_array($grant_href)) { //권한있는 메뉴로 이미 지정된경우
						if(array_key_exists($href, $grant_href)) {
							$grant_href[$href] = $mk;
						}
						else {
							$grant_href = array_merge($grant_href, array($href=>$mk));
						}
					}
					else {
						$grant_href = array($href=>$mk);
					}
					$this->ci->session->set_userdata('grant_href', array_filter($grant_href));
				}
				else {
					$mk = $grant_href[$href];
				}
			}
			if(in_array($mk, $sync_grant)) {
				$me_route = $cfg_menu[$mk]['route'];
			}
		}
		else {
			$me_route = $cfg_href[$me_href];
		}

		foreach ($me_route as $idx=>$k){
			define('MK_'.($idx+1), $k);
			$cfg_menu[$k]['me'] = true;
		}

		if(defined('MK_3')) {
			// $_SESSION['mk'] = MK_3;
			$this->ci->session->set_userdata('mk',MK_3);
		}



		foreach ($leaf_arr as $l) {
			if(empty($cfg_menu[$l]['route'])) continue;
			$allowed = array_merge($allowed, $cfg_menu[$l]['route']);
		}

		$allowed = array_unique($allowed);
		$tmp = array();
		krsort($cfg_depth);
		foreach ($cfg_depth as $d =>$keys) {
			$mk_arr = explode(',', $keys);
			foreach($mk_arr as $mk) {
				if (!in_array($mk, $allowed)) continue;
				$menu = $cfg_menu[$mk];
				if($menu['is_view']=='N') continue;
				$menu['route_txt'] = $this->get_navigation($mk);

				if ($menu['parent'] > 0) {
					// $tmp[$parent_key]['me'] = ($menu['href'] == $this_href)?true:false;
					$parent_key = $menu['parent'];
					if (!isset($tmp[$parent_key])) $tmp[$parent_key] = $cfg_menu[$parent_key];

					if (isset($tmp[$mk])) {
						$tmp[$parent_key]['children'][$mk] = $tmp[$mk];
					}
					else {
						$tmp[$parent_key]['children'][$mk] = $menu;
					}
				}
				else {
					$tree[$mk] = $tmp[$mk];
				}
			}
		}


		$tree = array_sort($tree, 'sort');

		$top = array();
		foreach($tree as $d1) {
			$d2_first = array_shift($d1['children']);
			$d3_first = array_shift($d2_first['children']);
			$top[] = array(
				'name'=>$d1['name'],
				'href'=>$d3_first['href'],
				'me' => $d1['me']
			);
		}
		$this->top=$top;

		return $tree;
	}

	function get_grant() {
		if($this->ci->uri->uri_string() == 'main/msg') {
			$grant_view = true;
		}
		else {
			$cfg_menu = $this->cfg['cmltd_menu'];
			$grant = $cfg_menu[MK_3]['sync_grant'];

			$grant_view = (in_array(MK_3, explode(',',$this->ci->session->userdata('ss_menu_grant'))))?true:false; //권한체크
		}
		return $grant_view;
	}


	function get_menu_detail($mk='') {
		// $mk = ($mk)?$mk:MK;

		if (!$mk) {
			$menu = array(
				'name'=>'홈',
				'comment'=>'',
				'route_txt' => array(array('name'=>'홈'))
			);
		}
		else {
			$cfg_menu = $this->get_config('menu');
			$menu = $cfg_menu[$mk];
			$route_txt = array();
			foreach ($menu['route'] as $r) {
				$route_txt[] = array(
					'no'=>$r,
					'comment'=>$cfg_menu[$r]['comment'],
					'name'=>$cfg_menu[$r]['name']
				);
			}

			$menu['route_txt']  = $route_txt;
			// $menu['relation'] = $relation;
		}

		return $menu;
	}

	function get_navigation($mk, $return_type='array') {
		$cfg_menu = $this->get_config('menu');
		$menu = $cfg_menu[$mk];
		$route_txt = array();
		foreach ($menu['route'] as $r) {
			$route_txt[] = array(
				'no'=>$r,
				// 'comment'=>$cfg_menu[$r]['comment'],
				'name'=>$cfg_menu[$r]['name']
			);

			$route_string[] = $cfg_menu[$r]['name'];
		}

		if($return_type == 'array') return $route_txt;
		else return implode(' > ',$route_string);
	}

	function get_grantkey($mk) {
		$grant_row = $this->get_menu_row(array('no'=>$mk), 'grant_dept');

		$grant_dept = array_filter(explode(',',$grant_row['grant_dept']));
		$key = null;
		$session = $this->ci->session->userdata;

		foreach($grant_dept as $code) {
			$depth = substr_count($code,"_");
			switch ($depth) {
				case '0':
				 	$compare = $session['ss_dept_code'];
				break;
				case '1':
					$compare = $session['ss_dept_code'].'_'.$session['ss_team_code'];
				break;
				case '2':
					$compare = $session['ss_dept_code'].'_'.$session['ss_team_code'].'_'.$session['ss_user_no'];
				break;
			}

			if($compare == $code) {
				$key = $code;
				continue;
			}
		}
		return $key;
	}

	function get_biz($mk, $grant_key) {
		$row = $this->get_menu_row(array('no'=>$mk));
		$biz = unserialize($row['grant_dept_biz']);

		if($biz[$grant_key]) {
			return $biz[$grant_key];
		}
		else {
			return $this->ci->session->userdata('ss_biz_id');
		}

	}

	function get_menu_row($where, $field) {
		$this->ci->load->model( 'Manage_model' );
		$menu = $this->ci->Manage_model->get_menu_row($where, $field);
		return $menu;
	}

}
?>
