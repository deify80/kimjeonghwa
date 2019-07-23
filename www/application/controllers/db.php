<?php
/**
 * 작성 : 2014.10.20
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Db extends CI_Controller {


	public function __construct() {
		parent::__construct();
		$this->load->model( array (
				'User_model',
				'Consulting_model',
				'Assign_model',
				'Manage_model'
		) );

		$this->load->library(array('assign_lib','consulting_lib'));

		$this->yield = TRUE;
	}


	public function main() {
		$team_list = $this->User_model->get_team_list( '90' );

		// 유입경로 중국
		$path =  $this->common_lib->get_cfg('path');
		if ($this->session->userdata( 'ss_biz_id' ) != 'ezham_cn') {
			unset($path['A']);
		}

		//자동분배여부
		$is_auto = $this->common_lib->get_config('db','auto');

		//db분배현황 버튼 노출 권한
		$auth_db_share = $this->common_lib->check_auth_group('db_share');

		//분배기능 버튼
		$auth_db_share_tools = $this->common_lib->check_auth_group('db_share_tools');

		$team_list = $this->User_model->get_team_list(90);				// 20170202 kruddo 팀별 검색 > 팀 목록 추가

		$biz_group = $this->common_lib->get_biz_group();
		$crm_status = $this->Manage_model->get_code_item('07', '', 'all',array('biz_id'=>$biz_group) );//예약상태

		$datum = array(
			'auto'=>$is_auto,
			'team_list'=>$team_list ,
			'path_list_value'=>$path_list_value,
			'cfg'=>array(
				'dept'=> $this->User_model->get_dept_list(),
				'team'=> $this->common_lib->get_cfg('team'),
				'path'=>$path,
				'db_status'=>$this->config->item('db_status'),
				'category'=>$this->config->item('category'),
				'cst_status'=>$this->config->item('cst_status'),
				'date'=>$this->common_lib->get_cfg('date'),
				'media'=>$this->common_lib->get_cfg('media'),
				'team_list'=>$team_list,
				'crm_status'=>$crm_status
			),
			'auth'=>array(
				'db_share'=>$auth_db_share,
				'ex_path'=>$this->common_lib->check_auth_group('ex_path'),
				'web_check'=>$this->common_lib->check_auth_group('web_check'),
				'm_check'=>$this->common_lib->check_auth_group('m_check'),
				'auth_db_share_tools'=>$auth_db_share_tools
			)
		);

		$this->_render('index', $datum);
	}


	public function lists() {
		$this->load->model('patient_model');
		$page = $this->input->post('page');
		$limit = ($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);

		$where_offset = array(
			'db.hst_code'=>$this->session->userdata('ss_hst_code'),
			'db.biz_id'=>$this->session->userdata('ss_biz_id'),
		);

		if(X) {
			$where_offset['c.is_x'] = 'N';
		}


		$where = array();
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["( db.name LIKE '%{$v}%' OR db.tel LIKE '%{$v}%' OR db.messenger LIKE '%{$v}%' OR db.reg_user_id LIKE '%{$v}%' )"]=NULL;
					break;
				case 'date_s':
					$where['db.date_insert >=']=$v.' 00:00:00';
					break;
				case 'date_e':
					$where['db.date_insert <=']=$v.' 23:59:59';
					break;
				case 'cst_status':
					$where['c.cst_status'] = $v;
					break;
				case 'media':
				case 'path':
				case 'reg_user_id':
				case 'db_status':
				case 'assign_type':
					$where['db.'.$k] = $v;
					break;
				case 'team_code_search':
					$team_code_search = $v;
					break;
				case 'charge_user_id':
					$charge_user_id = $v;
					break;
				default :
					//$where[$k] = $v;
					break;
			}
		}

		//pre($assoc);


		list($reg_dept, $reg_team, $reg_user) = $assoc['reg_id'];
		if($reg_dept != 'all' && $reg_dept > 0) {
			$this->load->model('user_model');
			if($reg_team == 'all') {
				$user_id = $this->user_model->get_user_all(array('dept_code'=>$reg_dept), 'user_id'); //해당부서의 모든인원
			}
			else {
				if($reg_user == 'all') { //해당팀의 모든인원
					$user_id = $this->user_model->get_user_all(array('team_code'=>$reg_team), 'user_id');		// 20170202 kruddo : 팀별 검색 오류 array('team_code'=>$reg_dept) -> array('team_code'=>$reg_team)
				}
				else {
					$user_id = array($reg_user=>'');
				}
			}

			$where['db.reg_user_id'] = array_keys($user_id);
		}


		// 20170202 kruddo : 상담팀별 검색 조건 추가
		//$team_code_search = 90;
		if($team_code_search != 'all' && $team_code_search > 0) {
			$this->load->model('user_model');

			if($charge_user_id == 'alll') {		//해당팀의 모든인원
				//$user_id = $this->user_model->get_user_all(array('team_code'=>$team_code_search), 'user_id'); //해당부서의 모든인원
				$where['c.team_code'] = $team_code_search;
			}
			else {
				$user_id = array($charge_user_id=>'');
				$where['c.charge_user_id'] = array_keys($user_id);
			}
		}

		// 20170202 kruddo : 상담팀별 검색 조건 추가


		$rs = $this->Consulting_model->select_db_paging($where, $offset, $limit, $where_offset);

		if($rs['count']['search'] > 0) {

			$appointment_status = $this->common_lib->get_cfg('appointment_status'); //예약상태
			$path =  $this->common_lib->get_cfg('path');
			$status_arr = array('-2'=>'검토', '0'=>'대기', '1'=>'완료', '8'=>'중복', '9'=>'보류');
			$category_list = $this->config->item( 'category' );

			$list = array();
			$idx =  $rs['count']['search']-$offset;

			$cst_status_list = $this->config->item('cst_status');
			$team_list = $this->User_model->get_team_list('90');

			//pre($rs['list']);
			foreach($rs['list'] as $row) {

				// 20170215 kruddo : DB유입에서 고객 상세 정보 보기 추가
				//db보기권한 -  팀DB에서 공동DB로 최초 이동된후 +7일간 초기분배팀에 보기권한없음 by 이혜진 20150511
				if($type == 'share') {
					$grant_view = $this->consulting_lib->check_grant_share($row['cst_seqno'], $row['charge_date']);
				}
				else {
					$grant_view = 'Y';
				}
				// 20170215 kruddo : DB유입에서 고객 상세 정보 보기 추가


				// $row['treat_info'] = ($treat_region[$row['treat_region_code']])?$treat_region[$row['treat_region_code']].' > '.$treat_item[$row['treat_item_code']]:''; //진료정보
				$row['treat_region_name'] = $treat_region[$row['treat_region_code']];
				$row['treat_item_name'] = $treat_item[$row['treat_item_code']];

				$row['db_status_txt'] = $status_arr[$row['db_status']];

				//CRM상태
				$crm = $this->patient_model->select_patient_join(array('p.cst_seqno'=>$row['cst_seqno']),'patient_appointment','sub.*','sub.appointment_date DESC');
				$crm_row = array_shift($crm);

				//pre($crm_row);
				//echo $crm_row['status_code'];
				$row['crm_status'] = $crm_row['status_code'];
				$row['crm_status_txt'] = $appointment_status[$crm_row['status_code']];

				$row['tel'] = preg_replace("/(0(?:2|[0-9]{2}))([0-9]+)([0-9]{4}$)/", "\\1-\\2-\\3", $row['tel']);

				$row['tel_org'] = $row['tel'];
				$row['tel'] = $this->common_lib->manufacture_mobile(trim($row['tel']), $row['manager_team_code']);

				$row['date_insert'] = substr($row['date_insert'], 5,11);
				$row['path_txt'] = $path[$row['path']];
				//$row['media'] = $this->consulting_lib->trans_media($row['media']);

				$categories = explode(',',$row['category']);
				if(count($categories) > 1) {
					$row['category'] = $category_list[$categories[0]].'외 '.(count($categories)-1).'건';
				}
				else {
					$row['category'] = $category_list[$row['category']];
				}


				$row['other_cnt'] = '';


				//팀정보
				$row['team_name'] = $team_list[$row['team_code']];
				//상담상태
				$row['cst_status'] = $row['cst_status'];
				$row['cst_status_text'] = $cst_status_list[$row['cst_status']];

				$row['patient'] = $this->patient_model->select_patient_row(array('cst_seqno'=>$row['cst_seqno']));

				if($row['reg_user_id']) {
					$reg_user = $this->User_model->get_info(array('user_id'=>$row['reg_user_id']),'name');
					$row['reg_user_name'] = $reg_user['name'];
				}
				else {
					$row['reg_user_name'] = '';
				}

				$row['idx'] = $idx;

				$row['grant_view']=$grant_view;					// 20170215 kruddo : DB유입에서 고객 상세 정보 보기 추가
				$row['db_no']=$row['cst_seqno'];					// 20170215 kruddo : DB유입에서 고객 상세 정보 보기 추가

				$list[] = $row;
				$idx--;
			}
		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();


		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);

		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}

	}

	public function register() {

		$all_path = $this->config->item('all_path');
		$ss = $this->session->userdata;

		$ss_team_code = $this->session->userdata('ss_team_code');

		/* 팀별 유입경로 제한
		코디팀 - 전화,워킹,소개(박원장),소개(진원장),소개(신원장),소개(실장),소개(고객),소개(직원),기존고객
		콜센터 - 전화,소개(박원장),소개(진원장),소개(신원장),소개(실장),소개(고객),소개(직원),기존고객
		상담팀 - 실시간상담,온라인상담,온라인,카카오톡,소개(실장),소개(고객)
		*/
		switch($ss['ss_team_code']) {
			//case '51'://코디팀인경우 유입경로 입력제한
				//$path_key = array('T','W','1','2','3','4','5','6','E','G');
			//break;
			//case '52'://콜센터
				//$path_key = array('T','1','2','3','4','5','6','E','G');
			//break;
			case '51':
			case '52':
			case '90': //상담
			case '91':
			case '92':
			case '93':
			case '94':
			case '95':
			case '96':
			case '97':
			case '98':
			case '99':
				$path_key = array_keys($all_path);
				//$path_key = array('P','O','L','K','4','6','G');
			break;
			default:
				$path_key = array_keys($all_path);
			break;
		}

		foreach($all_path as $k=>$v) {
			if(in_array($k, $path_key)) continue;
			unset($all_path[$k]);
		}

		//상담팀인경우직접분배
		if ($ss['ss_dept_code'] == '90') {
			$team_code = $ss['ss_team_code'];
			$mode = 'DIRECT'; //입력자소속팀으로 분배
		}
		else {
			$mode = 'ORDER'; //순서대로 분배
		}

		$datum = array (
			'mode'=>$mode,
			'cfg'=>array(
				'category'=>$this->config->item('category'),
				'path'=>$all_path,
				'media'=>$this->common_lib->get_cfg('media'),
				'team'=>$this->common_lib->get_cfg('team')
			),
			'reg_user_id'=>$this->session->userdata('ss_user_id'),
			'type'=>$type,
			'team_code'=>$team_code,
			'assign_type'=>$assign_type
		);
		$this->_render('register', $datum);
	}



	public function input() {
		$type = $this->input->post('type');
		$all_path = $this->config->item('all_path');

		// echo $this->session->userdata( 'ss_team_code' ) ;
		if ($this->session->userdata( 'ss_dept_code' ) == '90') {
			$team_code = $this->session->userdata( 'ss_team_code' ) ;
			$assign_type = 'F';
			$mode = 'DIRECT';
		}
		else {
			$assign_type = "F";
		}

		$datum = array (
			'mode'=>$mode,
			'cfg'=>array(
				'category'=>$this->config->item('category'),
				'path'=>$all_path,
				'media'=>$this->common_lib->get_cfg('media'),
				'team'=>$this->common_lib->get_cfg('team')
			),
			'type'=>$type,
			// 'path'=>$path,
			// 'team_code'=>$team_code,
			'assign_type'=>$assign_type
		);

		$this->_render('input', $datum, 'inc');
	}

	public function search() {
		$datum = array();
		$this->_render('search', $datum, 'inc');
	}


	public function input2($type) {

		$all_path = $this->config->item( 'all_path' );

		if ($type == "db") {

			$list = array('L', 'T','W','A','X','J','E','Y','Z','R','C');
			if ($this->session->userdata( 'ss_biz_id' ) == 'ezham_cn') {
				$list[] = 'A';
				$list[] = 'M';
			}
		} else {

			//상담
			if ($this->session->userdata( 'ss_dept_code' ) == '90') {
				$list = array('C', 'P', 'O','Y');
			} else if ($this->session->userdata( 'ss_team_code' ) == '51') {
				$list = array('T', 'W', 'A', 'X','J', 'Y' ,'C');
			}
		}

		foreach ($list as $i=>$val) {
			if (array_key_exists($val, $all_path)) $path[$val] = $all_path[$val];
		}

		//중국
		if ($this->session->userdata( 'ss_biz_id' ) != 'ezham_cn') {
			unset($path['A']);
		}

		$team_list = $this->User_model->get_team_list( '90' );

		$assign_type = "O";
		if ($this->session->userdata( 'ss_dept_code' ) == '90') {
			$team_code = $this->session->userdata( 'ss_team_code' ) ;
			$assign_type = 'F';
			$MODE = 'DIRECT';
		}

		$this->load->view( 'db/input', array (
				'type'=>$type,
				'path'=>$path,
				'team_code'=>$team_code,
				'assign_type'=>$assign_type,
				'MODE'=>$MODE,
				'team_list'=>$team_list,
				'select_mode'=>$select_mode
		) );
	}

	public function landing() {
		$place = $this->uri->segment(3);
		$categories = $this->config->item('category');
		$datum = array(
			'cfg'=>array('category'=>$categories)
		);
		$this->load->view( 'db/landing_'.$place, $datum);
	}

	function landing_list_paging() {
		$page = $this->input->post('page');
		$limit = ($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);
		// pre($assoc);
		$where = array();
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'search':
					$where["title LIKE '%{$v}%'"]=NULL;
				break;
				default :
					$where[$k] = $v;
					break;
			}
		}

		$rs = $this->Consulting_model->select_landing_paging($where, $offset, $limit);
		if($rs['count']['search'] > 0) {
			$list = array();
			$idx =  $rs['count']['search']-$offset;
			$url = 'http://www.nvshenps.com/landing/';
			$categories = $this->config->item('category');
			foreach($rs['list'] as $row) {
				$code = str_pad($row['no'], '3','0', STR_PAD_LEFT);
				$row['code'] = $code;
				$row['landing_mobile'] = $url.'M'.$code;
				$row['landing_web'] = $url.'W'.$code;
				$row['category_text'] = $categories[$row['category_code']];
				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}

			// pre($rs);

		}

		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();

		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);

		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}

	function landing_cn_input() {
		$p = $this->input->post(NULL, true);
		$landing_no = $this->input->post('landing_no');
		if($landing_no > 0) {
			$mode = 'update';
			$landing_info = $this->Consulting_model->select_landing_row(array('no'=>$landing_no));
		}
		else {
			$mode = 'insert';
			$landing_info = array();
		}

		$categories = $this->config->item('category');
		$datum = array(
			'cfg'=>array('category'=>$categories),
			'mode'=>$mode,
			'rs'=>$landing_info
		);
		$this->load->view( 'db/landing_cn_input'.$place, $datum);
	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "db/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}

	/**
	 * 기존고객 insert용 임시 함수
	 * @return [type] [description]
	 */
	function temp() {
		return false;

		$this->db->order_by('date_update asc');
		$query = $this->db->get('old');

		$rs = $query->result_array();
		foreach($rs as $row) {
			//pre($row);
			$query = $this->db->query("SELECT * FROM db_info WHERE tel='{$row[mobile]}'");
			$count = $query->num_rows();

			if($count>0) continue;
			if(strlen($row['birth']) == 6) {
				$birth = (substr($row['birth'],0,2) > 16)?'19'.$row['birth']:'20'.$row['brith'];
			}
			else {
				$birth = '';
			}

			$input = array(
				'biz_id'=>'HBPS',
				'hst_code'=>'H000',
				'name'=>$row['name'],
				'birth'=>$birth,
				'sex'=>$row['sex'],
				'tel'=>$row['mobile'],
				'email'=>$row['email'],
				'path'=>'E', //기존고객
				'assign_type'=>'F',
				'date_insert'=>$row['date_insert'],
				'date_update'=>$row['date_update'],
			);

			//echo $this->db->insert_string('db_info',$input)."<br />";
			$rs = $this->db->insert( 'db_info', $input );
			if(!$rs) {
				echo 'error : '.$this->db->last_query();
				exit;
			}
			else {
				$db_no = $this->db->insert_id();
				echo 'success : '.$db_no."<br />";
			}

			//$db_no = $this->db->insert_id();
		}

		exit;
	}


	// 20170321 kruddo : DB유입 중복 명단 목록
	public function duplicate_list() {
		$datum = array();

		$where_offset = array(
			'db.hst_code'=>$this->session->userdata('ss_hst_code'),
			'db.biz_id'=>$this->session->userdata('ss_biz_id')
		);

		$tel = $this->input->post('tel');

		$where = array();
		$where["( replace(db.tel, '-', '') = '".str_replace('-', '', $tel)."')"]=NULL;

		$rs = $this->Consulting_model->select_db_paging($where, $offset, $limit, $where_offset);
		$category_list = $this->config->item( 'category' );

		if($rs['count']['search'] > 0) {

			$path =  $this->common_lib->get_cfg('path');
			$status_arr = array('-2'=>'검토', '0'=>'대기', '1'=>'완료', '8'=>'중복', '9'=>'보류');
			$category_list = $this->config->item( 'category' );

			$list = array();
			$idx =  $rs['count']['search']-$offset;

			$cst_status_list = $this->config->item('cst_status');
			$team_list = $this->User_model->get_team_list('90');

			foreach($rs['list'] as $row) {

				$row['treat_region_name'] = $treat_region[$row['treat_region_code']];
				$row['treat_item_name'] = $treat_item[$row['treat_item_code']];

				$row['db_status_txt'] = $status_arr[$row['db_status']];
				$row['tel'] = preg_replace("/(0(?:2|[0-9]{2}))([0-9]+)([0-9]{4}$)/", "\\1-\\2-\\3", $row['tel']);

				$row['tel'] = $this->common_lib->manufacture_mobile(trim($row['tel']), $row['manager_team_code']);

				$row['date_insert'] = substr($row['date_insert'], 5,11);
				$row['path_txt'] = $path[$row['path']];


				//팀정보
				$row['team_name'] = $team_list[$row['team_code']];
				//상담상태
				$row['cst_status'] = $row['cst_status'];
				$row['cst_status_text'] = $cst_status_list[$row['cst_status']];

				if($row['reg_user_id']) {
					$reg_user = $this->User_model->get_info(array('user_id'=>$row['reg_user_id']),'name');
					$row['reg_user_name'] = $reg_user['name'];
				}
				else {
					$row['reg_user_name'] = '';
				}

				$categories = explode(',',$row['category']);
				if(count($categories) > 1) {
					$row['category'] = $category_list[$categories[0]].'외 '.(count($categories)-1).'건';
				}
				else {
					$row['category'] = $category_list[$row['category']];
				}

				$row['idx'] = $idx;

				if($row['cst_seqno']) {
					$cst_seqno = $row['cst_seqno'];
				}

				$list[] = $row;
				$idx--;
			}
		}

		$datum = array(
			'cst_seqno'=>$cst_seqno,
			'list'=>$list,
		);

		//return_json(true, '', $datum);

		$this->_render('duplicate_list', $datum, 'inc');


	}
	// 20170321 kruddo : DB유입 중복 명단 목록
}
