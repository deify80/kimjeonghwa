<?php
/**
 * 작성 : 2014.10.17
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Assign extends CI_Controller {


	public function __construct() {
		parent::__construct();
		$this->load->model( array (
				'User_model',
				'Assign_model',
				'Consulting_model',
				'Manage_model'
		) );

		$this->yield = TRUE;
	}


	public function main() {
		// 팀정보
		$team_list = $this->User_model->get_team_list( '90' );

		$datum = array (
			'biz_list'=>$this->session->userdata( 'ss_biz_list' ),
			'team_list'=>$team_list,
			'auth'=>array(
				'db_share'=>$this->common_lib->check_auth_group('db_share')
			)
		);

		$this->_render('index', $datum);
	}

	function input() {
		$team_list = $this->User_model->get_team_list( '90' );
		$biz_list = $this->session->userdata( 'ss_biz_list' );
		$path_list = $this->config->item( 'all_path' );
		$p = $this->input->post(NULL, true);


		$seqno = $p['seqno'];
		if($seqno) {
			$old = array_shift($this->Assign_model->select_rule(array('o.seqno'=>$seqno)));

			$team_arr = explode(',',$old['team']);

			foreach($team_arr as $t) {

				list($team_code, $order) = explode('_',$t);
				$team[] = array(
					'code'=>$team_code,
					'name'=>$team_list[$team_code],
					'order'=>($order == $old['order_no'])?'yes':'no'
				);
			}

		}
		else $team = array();

		$old['sort'] = $team;
		$datum = array(
			'cfg'=>array(
				'team'=>$team_list,
				'biz'=>array(
					'name'=>$this->session->userdata('ss_biz_name'),
					'id'=>$this->session->userdata('ss_biz_id')
				),
				'path'=>$path_list
			),
			'old'=> $old
		);

		$this->_render('input', $datum, 'inc');
		// $this->load->view( 'assign/input', $data );
	}


	function lists() {
		$page = $_GET['page'];
		$limit = $_GET['rows'];
		$sidx = $_GET['sidx'];
		$sord = $_GET['sord'];

		$path_list = $this->config->item( 'path' );

		$first = ($page - 1) * $limit;

		$team_list = $this->User_model->get_team_list( '90' );

		$result = $this->Assign_model->get_list( $first, $limit );
		foreach ( $result as $i => $row ) {
			$list->rows[$i]['no'] = $row['seqno'];

			$team_order = $this->_set_team_name( $team_list, $row['team_code'] );

			$list->rows[$i]['cell'] = array (
					$row['seqno'],
					$row['biz_name'],
					$path_list[$row['path']],
					set_long_date_format( '-', $row['reg_date'] ),
					$team_order,
					$row['memo'],
					$row['status']
			);
		}
		$total = $this->Assign_model->get_total();
		$list->page = $page;
		$list->total = intval( ($total - 1) / $limit ) + 1;
		$list->records = $total;
		echo json_encode( $list );
	}

	function rule_list() {
		$where = array();
		$where['status'] = '1';
		$where['biz_id'] = $this->session->userdata('ss_biz_id');
		$rs = $this->Assign_model->select_rule($where);

		if(!$rs) return_json(false);

		$biz_list = $this->session->userdata('ss_biz_list');
		$path_list =  $this->config->item( 'all_path' ) ;
		$team_list = $this->User_model->get_team_list( '90' );


		$list = array();
		$idx =  count($rs);
		foreach($rs as $row) {
			$row['biz_name'] = $biz_list[$row['biz_id']];
			$row['path_name'] = $path_list[$row['path']];
			$teams = explode(',',$row['team']);
			unset($rule);
			foreach($teams as $team) {
				list($team_code,$order_no) = explode('_',$team);
				$turn = ($order_no == $row['order_no'])?'Y':'N';
				$rule[] = array('team'=>$team_list[$team_code], 'turn'=>$turn, 'order_no'=>$order_no, 'team_code'=>$team_code);		// 20170307 kruddo : DB규칙 - 자동분배규칙 수정, order_no, team_code 추가
			}
			// pre($rule);
			$row['rule'] = $rule;

			$row['date_insert'] = substr($row['date_insert'],0,16);
			$row['date_update'] = substr($row['date_update'],0,16);
			$row['idx'] = $idx;
			$list[] = $row;
			$idx--;
		}
		return_json(true, '', $list);
	}


	private function _set_team_name($team_list, $team_code) {
		$exp_team_code = explode( ',', $team_code );
		foreach ( $exp_team_code as $i => $val ) {
			$team_name[] = $team_list[$val];
		}
		$team_order = implode( ', ', $team_name );
		return $team_order;
	}

	public function ing() {

		//현재 디비 분배 현황
		$team_list = $this->User_model->get_team_list( '90' );

		$result = $this->Assign_model->get_ing_status();
		foreach ( $result as $i => $row ) {

			$key = $row['path'];
			$list[$key][$i]->team_code = $row['team_code'];
			$list[$key][$i]->team_name = $team_list[$row['team_code']];
			$list[$key][$i]->turn_status = $row['turn_status'];
		}


		//공동DB 컨택
		$contact_list = null;
		$where['SUBSTRING(contact_date,1,8)'] = date('Ymd');
		$result = $this->Consulting_model->get_contact_list('A.contact_user_id,  name, A.team_code, count(*) as total', $where, 'contact_user_id');
		foreach ($result as $i=>$row) {
			$key = $row['team_code'];
			$contact_list[$key][$i]->name = $row['name'];
			$contact_list[$key][$i]->total = $row['total'];
		}

		$data = array('list'=>$list, 'contact_list'=>$contact_list, 'team_list'=>$team_list);
		$this->load->view( 'assign/ing', $data);
	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "assign/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}
}
