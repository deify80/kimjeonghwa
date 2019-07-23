<?php
/**
 * 작성 : 2014.12.16
 * 수정 : 2015.02.27
 * 수정 : 2015.04.07
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Stats extends CI_Controller {
	var $output_mode;

	public function __construct() {
		parent::__construct();
		$this->load->model( array (
				'User_model',
				'Consulting_model',
				'Assign_model',
				'Manage_model'
		) );
		$this->yield = TRUE;
	}

	public function sales_main() {
		$this->page_title = "DB 실적";

		$srch_date = get_search_type_date();
		$data = array (
			'cfg'=>array(
				'date'=>$srch_date
			)
		);
		$this->load->view( 'stats/sales_main', $data );
	}

	private function _set_page_title($method) {
		$title_list = array (
				'sales'=>'DB 실적',
				'status'=>'DB 현황',
				'age'=>'연령별 통계',
				'category'=>'시술별 상담현황',
				'team'=>'상태별 분배현황',
				'item'=>'상담항목별 분배현황',
				'consulting'=>'상담팀 실적'
		);

		$exp_method = explode( '_', $method );
		$this->page_title = $title_list[$exp_method[0]];
	}

	public function excel_lists($method) {
		$this->yield = FALSE;

		if (method_exists( $this, $method )) {
			$this->output_mode = 'EXCEL';
			$this->_set_page_title( $method );
			output_excel( iconv( 'utf-8', 'euckr', str_replace( ' ', '', $this->page_title ) . '_' . date( 'Ymd' ) ) );
			$this->$method();
		}
	}

	public function sales_lists() {
		$srch_start_date = $this->input->post('srch_start_date');
		$srch_end_date = $this->input->post('srch_end_date');
		$rank_point_list = $_GET['rank_point'];
		$team_list = null;

		//사업장별 기본 조건
		$where = array();
		$where['A.biz_id'] = $this->session->userdata('ss_biz_id');

		if (! empty( $_POST['srch_start_date'] )) $where['reg_date >='] = str_replace( '-', '', $_POST['srch_start_date'] ) . '000000';
		if (! empty( $_POST['srch_end_date'] )) $where['reg_date <='] = str_replace( '-', '', $_POST['srch_end_date'] ) . '999999';


		$result = $this->Consulting_model->get_sales_total( $where, 'team_code' );

		//현재보유DB
		foreach ( $result as $i => $row ) {
			$sales[$row['team_code']]['cur_db'] = $row['total'];
			$team_list[$row['team_code']] = $row['team_name'];
		}

		//최초분배DB
		$result = $this->Consulting_model->get_sales_total( $where );
		foreach ( $result as $i => $row ) {
			$sales[$row['team_code']]['db'] = $row['total'];
		}

		$this->load->library('common_lib');
		$cfg_team = $this->common_lib->get_config('stats','team'); //각팀원수
		$cfg_weight = $this->common_lib->get_config('stats','weight'); //가중치

		$field = 'IF(media is null,9,1) as order_no, cst_status, team_code, count(*) as total';
		$order_field = 'order_no';
		$group_field = 'team_code, cst_status';
		$result = $this->Assign_model->get_status_by_option( $where, $field, $order_field, $group_field );
		foreach($result as $row) {
			$tc = $row['team_code']; //팀코드
			$sales[$tc]['status_'.$row['cst_status']] = $row['total']; //상태별갯수

			//내원 = 내원상담(52)+수술예정(98)+수술취소(90)+수술완료(99)
			// if(in_array($row['cst_status'], array('52','98','90','99'))) {
			// 	$sales[$tc]['come'] += $row['total'];
			// }
			//수술율 = 수술완료/분배DB
			// if($row['cst_status'] == '99') {
			// 	$sales[$tc]['oper_rate'] = round($sales[$tc]['status_99']/$sales[$tc]['db']*100,1);
			// }
		}

		//매출액(patient_project), 정상수가
		$this->load->model('Patient_Model');
		$amount = $this->Patient_Model->select_patient_all(array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, 'manager_team_code >'=>0), 'patient_project', 'manager_team_code, SUM(amount_basic) AS sum, SUM(cost) AS cost','','manager_team_code','manager_team_code');

		//예치금
		$deposit = $this->Patient_Model->select_patient_all(array('date_paid >='=>$srch_start_date, 'date_paid <= '=>$srch_end_date, 'sales_type'=>'예치', 'manager_team_code >'=>0), 'patient_pay', 'manager_team_code, SUM(amount_paid) AS sum','','manager_team_code','manager_team_code');

		//환불
		$refund = $this->Patient_Model->select_patient_all(array('date_paid >='=>$srch_start_date, 'date_paid <= '=>$srch_end_date, 'pay_type'=>'refund', 'manager_team_code >'=>0), 'patient_pay', 'manager_team_code, SUM(amount_refund) AS sum','','manager_team_code','manager_team_code');

		//내원
		$appointment =  $this->Patient_Model->select_patient_join(array('appointment_date >='=>$srch_start_date, 'appointment_date <= '=>$srch_end_date, 'p.manager_team_code >'=>0, 'is_delete'=>'N','status_code !='=>'07-012'), 'patient_appointment', 'p.manager_team_code, patient_no, COUNT(*)','','','p.manager_team_code, patient_no');
		foreach($appointment as $row) {
			$sales[$row['manager_team_code']]['come']++;
		}

		//수술완료
		$project =  $this->Patient_Model->select_patient_join(array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, 'p.manager_team_code >'=>0), 'patient_project', 'p.manager_team_code, patient_no, COUNT(*)','','','p.manager_team_code, patient_no');
		foreach($project as $row) {
			$sales[$row['manager_team_code']]['project']++;
		}

		//수납액
		$paid =  $this->Patient_Model->select_patient_join(array('date_paid >='=>$srch_start_date, 'date_paid <= '=>$srch_end_date, 'p.manager_team_code >'=>0), 'patient_pay', 'p.manager_team_code, patient_no, SUM(sub.amount_paid) AS amount_paid','','','p.manager_team_code, patient_no');
		foreach($paid as $row) {

			$sales[$row['manager_team_code']]['paid']+=$row['amount_paid'];
		}

		foreach($team_list as $tc=>$tn) {
			// $sales[$tc]['come_rate'] = round($sales[$tc]['come']/$sales[$tc]['db']*100,1); //내원율 = 내원/분배DB
			// $sales[$tc]['oper_come_rate'] = round($sales[$tc]['status_99']/$sales[$tc]['come']*100,1); //수술완료/내원
			$sales[$tc]['team_count'] = $cfg_team[$tc];
		}

		//각팀별 점수
		$score_field = array(
			'come'=>'내원',
			'project'=>'수술완료',
			'cost_rate'=>'수가율',
			'cost'=>'정상수가',
			'sales_price'=>'매출액(원)',
			'paid'=>'수납액(원)',
			'status_13'=>'예치금(원)',
			// 'status_51'=>'내원예약',
			// 'oper_rate'=>'수술율',
			// 'come_rate'=>'내원율',
			// 'oper_come_rate'=>'내원/수술율',
			'refund'=>'환불금액(원)',
			'team_count'=>'팀원수'
		);

		$field = array();
		foreach($score_field as $k=>$v) {
			$field[$k] = array(
				'name'=>$v,
				'weight'=>$cfg_weight[$k]
			);
		}

		foreach($team_list as $tc=>$tn) {
			foreach($score_field as $sk=>$sv) {
				switch($sk) {
					case 'sales_price':
						$sales[$tc][$sk] = $amount[$tc]['sum'];
					break;
					case 'status_13':
						$sales[$tc][$sk] = $deposit[$tc]['sum'];
					break;
					case 'refund':
						$sales[$tc][$sk] = $refund[$tc]['sum'];
					break;
					case 'cost':
						$sales[$tc][$sk] = $amount[$tc]['cost'];
					break;
				}

				if(!array_key_exists($sk,$sales[$tc])) {
					$team_sales[$tc][$sk] = 0;
				}
				else {
					$team_sales[$tc][$sk] = $sales[$tc][$sk];
					if(in_array($tc, array('90'))) {
						$team_total['etc'][$sk] += $sales[$tc][$sk];
					}
					else {
						$team_total['normal'][$sk] += $sales[$tc][$sk];
					}
				}
			}
		}

		$team = $team_etc = array();
		foreach($team_list as $code=>$name) {
			//수가율
			$team_sales[$code]['cost_rate'] = round(($team_sales[$code]['sales_price'] / $team_sales[$code]['cost'])*100,2);
			if(in_array($code, array('900000'))) {
				$team_etc[$code] = array(
					'name'=>$name,
					'rank'=>$rank[$code],
					'sales'=>$team_sales[$code],
					'cur_db'=>$sales[$code]['cur_db'],
					'db'=>$sales[$code]['db']
				);
				$team_total['etc']['cur_db'] += $sales[$code]['cur_db'];
				$team_total['etc']['db'] += $sales[$code]['db'];
			}
			else {
				$team[$code] = array(
					'name'=>$name,
					'rank'=>$rank[$code],
					'sales'=>$team_sales[$code],
					'cur_db'=>$sales[$code]['cur_db'],
					'db'=>$sales[$code]['db']
				);
				$team_total['normal']['cur_db'] += $sales[$code]['cur_db'];
				$team_total['normal']['db'] += $sales[$code]['db'];
			}
		}


		$total = array();
		foreach($team_list as $tc=>$tn) {

			$key = (in_array($tc, array('90')))?'etc':'normal';
			$sum = $team_code[$key];
			foreach($score_field as $sk=>$sv) {
				$score[$tc]+= ($sales[$tc][$sk]/$sum[$sk])*$cfg_weight[$sk];
				// echo "{$tc} {$sk} = (".$sales[$tc][$sk]."/".$sum.")*".$cfg_weight[$sk]." = ".($sales[$tc][$sk]/$sum)*$cfg_weight[$sk]."<Br />";
				$total[$key][$sk] = $team_total[$key][$sk];
			}
		}

		$total['normal']['cost_rate'] = round(($total['normal']['sales_price']/$total['normal']['cost'])*100,2);
		$total['etc']['cost_rate'] = round(($total['etc']['sales_price']/$total['etc']['cost'])*100,2);

		$point = $ranking = array('normal'=>0, 'etc'=>0);
		arsort($score);
		foreach ($score as $tc=>$sv) {

			if(in_array($tc, array('90'))) {
				if($point != $sv) $ranking['etc']++;
				$team_etc[$tc]['rank'] = $ranking['etc'];
				$point['etc'] = $sv;
			}
			else {
				if($point != $sv) $ranking['normal']++;
				$team[$tc]['rank'] = $ranking['normal'];
				$point['etc'] = $sv;
			}

		}

		$output_mode = $this->output_mode;

		$datum = array (
			'cfg'=>array(
				'field'=>$field
			),
			'team_list'=>$team,
			'team_etc_list'=>$team_etc,
			'total'=>$total,
			'team_total'=>$team_total
		);

		$this->_render('sales_list', $datum, 'inc');
	}

	/**
	 * DB실적 팀원수 설정
	 * @return [type] [description]
	 */
	function sales_team() {
		$this->load->library('common_lib');
		$team_count = $this->common_lib->get_config('stats','team');
		$team_list = $this->User_model->get_team_list( '90' );

		$data = array(
			'cfg'=>array(
				'team'=>$team_list
			),
			'set'=>$team_count
		);
		$this->load->view( 'stats/sales_team', $data );
	}

	/**
	 * DB실적 가중치 설정
	 * @return [type] [description]
	 */
	function sales_weight() {
		$field = array(
			'come'=>'내원',
			'project'=>'수술완료',
			'cost_rate'=>'수가율',
			'cost'=>'정상수가',
			'sales_price'=>'매출액(원)',
			'paid'=>'수납액(원)',
			'status_13'=>'예치금(원)',
			// 'status_51'=>'내원예약',
			// 'oper_rate'=>'수술율',
			// 'come_rate'=>'내원율',
			// 'oper_come_rate'=>'내원/수술율',
			'refund'=>'환불금액(원)',
			'team_count'=>'팀원수'
		);
		$this->load->library('common_lib');
		$set = $this->common_lib->get_config('stats','weight');
		$data = array(
			'cfg'=>array(
				'kind'=>$field
			),
			'set'=>$set
		);
		$this->load->view( 'stats/sales_weight', $data );
	}

	/**
	 * DB현황
	 * @return [type] [description]
	 */
	public function status_main() {
		$this->page_title = "DB 현황";

		$team_list = $this->User_model->get_team_list( '90' );
		$all_path_list = $this->config->item( 'all_path' );
		$srch_date = get_search_type_date();


		$datum = array (
			'cfg'=>array(
				'team'=>$team_list,
				'path'=>$all_path_list,
				'date'=>$srch_date
			),
			'auth'=>array(
				'db_share'=>$this->common_lib->check_auth_group('db_share')
			)
		);
		$this->_render('status_main', $datum);
		// $this->load->view( 'stats/status_main', $data );
	}



	public function status_lists() {
		$all_path_list = $this->config->item( 'all_path' );
		$team_list = $this->User_model->get_team_list( '90' , 'all');

		//사업장별 기본 조건
		$where = array(
			'biz_id'=>$this->session->userdata('ss_biz_id')
		);

		if (! empty( $_POST['srch_start_date'] )) {
			$search_date_s = str_replace( '-', '', $_POST['srch_start_date'] ) . '000000';
			$where['reg_date >='] = $search_date_s;
			$where_contact['contact_date >='] = $search_date_s;
			$where_work['C.reg_date >='] = $search_date_s;
		}
		if (! empty( $_POST['srch_end_date'] ))  {
			$search_date_e = str_replace( '-', '', $_POST['srch_end_date'] ) . '999999';
			$where['reg_date <='] = $search_date_e;
			$where_contact['contact_date <='] = $search_date_e;
			$where_work['C.reg_date <='] = $search_date_e;
		}
		if (! empty( $_POST['srch_team_code'] )) $where['org_team_code'] = $_POST['srch_team_code'];
		if (! empty( $_POST['srch_path'] )) $where['path'] = $_POST['srch_path'];
		$result = $this->Assign_model->get_status( $where );

		$path_list = null;
		$total_list = null;

		foreach ( $result as $i => $row ) {
			$path = $row['path'];

			$team_code = ($row['org_team_code'])?$row['org_team_code']:999;

			if($row['biz_id'] =='ezham_cn') {
				$biz_path =  array('L','B','S','T','W','N','P'); //랜딩(L), 커뮤니티(B), 검색엔진(S), 전화(T), 워킹(W), SNS툴(N), 실시간상담(P)
			}
			else {
				$biz_path = array('L','T','W','C','P','O', 'R');
			}


			$list[$team_code][$path] = $row['total'];
			$list[$team_code]['compensation'][$path] += $row['compensation'];

			$path_list[$path] = $all_path_list[$path];


			if($team_code == '900000') {
				if(!in_array($path, $biz_path)) {
					$list[$team_code]['ETC'] += $row['total'];
					$total_etc['path']['ETC'] += $row['total'];
				}

				$total_etc['team'][$team_code] += $row['total'];
				$total_etc['path'][$path] += $row['total'];
				$total_etc['sum'] += $row['total'];
			}
			else {
				if(!in_array($path, $biz_path)) {
					$list[$team_code]['ETC'] += $row['total'];
					$total_list['path']['ETC'] += $row['total'];
				}

				$total_list['team'][$team_code] += $row['total'];
				$total_list['path'][$path] += $row['total'];
				$total_list['sum'] += $row['total'];
			}


		}


		$team_valid = array_keys($list); //데이터 존재하는 팀

		//공동db컨택현황
		$contact_list = null;
		$result = $this->Consulting_model->get_contact_list('A.contact_user_id,  name, A.team_code, count(*) as total', $where_contact, 'contact_user_id');
		foreach ($result as $i=>$row) {
			$team_code = $row['team_code'];
			$contact_list[$team_code] += $row['total'];
			if(!in_array($team_code, $team_valid)) continue;

			if($team_code == '900000') {
				$total_etc['contact'] += $row['total'];
			}
			else {
				$total['contact'] += $row['total'];
			}

		}

		//업무이력
		$work_list = null;

		$result = $this->Consulting_model->get_log_count($where_work);

		foreach ($result as $i=>$row) {
			$team_code = $row['team_code'];
			$work_list[$team_code] += $row['cnt'];
			if(!in_array($team_code, $team_valid)) continue;
			if($team_code == '90') {
				$total_etc['work'] += $row['cnt'];
			}
			else {
				$total['work'] += $row['cnt'];
			}

		}

		asort( $path_list );

		$field = 'IF(media is null or media="","etc",media) as media, IF(media is null,9,1) as order_no, cst_status, count(*) as total';
		$order_field = 'order_no';
		$group_field = 'media, cst_status';
		$result = $this->Assign_model->get_status_by_option( $where, $field, $order_field, $group_field );

		$this->load->library('consulting_lib');

		foreach ( $result as $i => $row ) {
			//내원예약(51)
			//내원상담(52)
			//수술예정(98)
			//수술취소(90)
			//수술완료(99)
			$media_group = $this->consulting_lib->trans_media($row['media']);


			if(in_array($row['cst_status'], array('99', '98','52','51','90'))) {
				// $media['status'][$row['cst_status']][$media_group] = $row['total'];
				// $media['status'][$row['cst_status']]['total'] += $row['total'];
				$status[$row['cst_status']][$media_group] = $row['total'];
				// $media['status'][$row['cst_status']]['total'] += $row['total'];
				// $total[$media_group]['total'] += $row['total'];
				// $status['total'] += $row['total'];
			}


			$media[$media_group] += $row['total'];
			$total['media'] += $row['total'];
		}

		ksort($media);

		$rs_team = array();
		$total_sub = 0;
		foreach($team_list as $tc=>$tn) {
			$row = $list[$tc];
			if(empty($row)) continue;
			$sub = ($row['L']+$row['T']+$row['W']+$row['R']);

			if($tc == '-') { //피부과
				$rs_team_etc[$tc] = array_merge($row, array(
					'team_name' => $tn,
					'sub'=>$sub,
					'sum'=>$total_etc['team'][$tc],
					'work'=>$work_list[$tc],
					'contact'=>$contact_list[$tc]
				));
				$total_etc['sub'] += $sub;
			}
			else {
				$rs_team[$tc] = array_merge($row, array(
					'team_name' => $tn,
					'sub'=>$sub,
					'sum'=>$total_list['team'][$tc],
					'work'=>$work_list[$tc],
					'contact'=>$contact_list[$tc]
				));
				$total_sub += $sub;
			}
		}
		$total['sub'] = $total_sub;


		$rs_status = array();
		$status_cfg = $this->config->item( 'cst_status' );
		$status_label = array('99', '98','52','51','90');
		foreach($status_label as $status_code) {
			$media_arr = array();
			$media_total = 0;
			foreach($media as $media_code=>$v) {
				$media_arr[$media_code]=$status[$status_code][$media_code];
				$media_total += $media_arr[$media_code];
				$total['status'][$media_code] += $media_arr[$media_code];
				$total['status_total'] += $media_arr[$media_code];
			}
			$rs_status[$status_code] = array(
				'name'=>$status_cfg[$status_code],
				'total'=>$media_total,
				'media'=> $media_arr
			);
		}


		$datum = array (
			'cfg'=>array(
				'cst_status'=>$this->config->item('cst_status')
			),
			'rs'=>array(
				'team'=>$rs_team,
				'team_etc'=>$rs_team_etc,
				'media'=>$media,
				'status'=>$rs_status
			),
			'total'=>array(
				'team'=>$total_list,
				'team_etc'=>$total_etc,
				'sub'=>$total['sub'],
				'sum'=>$total_list['sum'],
				'work'=>$total['work'],
				'contact'=>$total['contact'],
				'media'=>$total['media'],
				'status'=>$total['status'],
				'status_total'=>$total['status_total']
			)
		);


		$this->_render('status_list',$datum, 'inc');
		// $this->load->view( 'stats/status_list', $data );
	}



	public function summary($type) {
		$this->_set_page_title( $type );
		$srch_date = get_search_type_date();
		$team_list = $this->User_model->get_team_list( '90' );
		$all_path_list = $this->config->item( 'all_path' );


		$data = array (
			'cfg'=>array(
				'date'=>$srch_date
			),
			'team_list'=>$team_list,
			'path_list'=>$all_path_list
		);


		$this->load->view( 'stats/' . $type . '_main', $data );
	}


	public function age_lists() {
		$category_list = null;

		$treat = $this->common_lib->get_treat_children();
		foreach($treat as $no=>$row) {

		}
		//pre($treat);

		$main_category = $this->Manage_model->get_code_item( '01' );

		$where = null;
		if (! empty( $_GET['srch_start_date'] )) $where['reg_date >='] = str_replace( '-', '', $_GET['srch_start_date'] ) . '000000';
		if (! empty( $_GET['srch_end_date'] )) $where['reg_date <='] = str_replace( '-', '', $_GET['srch_end_date'] ) . '999999';
		if (! empty( $_GET['srch_team_code'] )) $where['org_team_code'] = $_GET['srch_team_code'];
		if (! empty( $_GET['srch_path'] )) $where['path'] = $_GET['srch_path'];
		$where['age > '] = 0;
		$where['treat_cost_no !='] = 'null';
		$field = 'age, main_category, count(cst_seqno) as total';
		$order_field = 'age';
		$group_field = 'age, main_category';
		$result = $this->Assign_model->get_status_by_option( $where, $field, $order_field, $group_field );
		foreach ( $result as $i => $row ) {
			$list[$row['age']][$row['main_category']] = $row['total'];
			$category_list[$row['main_category']] = $main_category[$row['treat_cost_no']];
			$total_list[$row['age']]['all'] += $row['total'];
			$sum_list[$row['main_category']]['all'] += $row['total'];
		}

		$where['cst_status'] = '99';
		$result = $this->Assign_model->get_status_by_option( $where, $field, $order_field, $group_field );
		foreach ( $result as $i => $row ) {
			$success_list[$row['age']][$row['main_category']] = $row['total'];
			$total_list[$row['age']]['success'] += $row['total'];
			$sum_list[$row['main_category']]['success'] += $row['total'];
		}

		$data = array (
				'list'=>$list,
				'category_list'=>$category_list,
				'success_list'=>$success_list,
				'total_list'=>$total_list,
				'sum_list'=>$sum_list,
				'output_mode'=>$this->output_mode
		);
		$this->load->view( 'stats/age_list', $data );
	}



	public function category_lists() {
		$all_team_list = $this->User_model->get_team_list( '90', '' );
		$main_category = $this->Manage_model->get_code_item( '01' );

		$where = null;
		if (! empty( $_GET['srch_start_date'] )) $where['reg_date >='] = str_replace( '-', '', $_GET['srch_start_date'] ) . '000000';
		if (! empty( $_GET['srch_end_date'] )) $where['reg_date <='] = str_replace( '-', '', $_GET['srch_end_date'] ) . '999999';
		if (! empty( $_GET['srch_path'] )) $where['path'] = $_GET['srch_path'];

		$where['org_team_code !='] = 'null';
		$where['main_category !='] = 'null';
		$field = 'org_team_code as team_code, main_category, count(cst_seqno) as total, cst_status';
		$order_field = 'org_team_code';
		$group_field = 'org_team_code, main_category, cst_status';
		$result = $this->Assign_model->get_status_by_option( $where, $field, $order_field, $group_field );
		foreach ( $result as $i => $row ) {
			$team_list[$row['team_code']] = $all_team_list[$row['team_code']];
			$list[$row['team_code']][$row['main_category']] = $row['total'];
			$total_list['team'][$row['team_code']] += $row['total'];
			$total_list['main_category'][$row['main_category']] += $row['total'];
			$total_list['status'][$row['cst_status']] += $row['total'];
			$cst_status[$row['cst_status']][$row['main_category']] += $row['total']; //시술별 수술완료 건수
		}

		$data = array (
				'list'=>$list,
				'team_list'=>$team_list,
				'total_list'=>$total_list,
				'main_category'=>$main_category,
				'cst_status'=>$cst_status,
				'output_mode'=>$this->output_mode
		);

		$this->load->view( 'stats/category_list', $data );
	}


	/**
	 * 상태별분배현황
	 * @return [type] [description]
	 */
	public function team_lists() {
		$team_etc = array(); //피부팀
		$all_team_list = $this->User_model->get_team_list('90');
		$where = array(
			'biz_id'=>$this->session->userdata('ss_biz_id')
		);

		if (! empty( $_GET['srch_start_date'] )) $where['reg_date >='] = str_replace( '-', '', $_GET['srch_start_date'] ) . '000000';
		if (! empty( $_GET['srch_end_date'] )) $where['reg_date <='] = str_replace( '-', '', $_GET['srch_end_date'] ) . '999999';
		if (! empty( $_GET['srch_path'] )) $where['path'] = $_GET['srch_path'];

		$where['org_team_code !='] = 'null';
		$field = 'org_team_code as team_code, cst_status, count(cst_seqno) as total';
		$order_field = 'org_team_code';
		$group_field = 'org_team_code, cst_status';
		$result = $this->Assign_model->get_status_by_option( $where, $field, $order_field, $group_field );
		// pre($where);
		foreach ( $result as $i => $row ) {
			// $team_list[$row['team_code']] = $all_team_list[$row['team_code']];
			$list[$row['team_code']][$row['cst_status']] = $row['total'];
			if(in_array($row['team_code'], $team_etc)) {
				$total_list['etc']['team'][$row['team_code']] += $row['total'];
				$total_list['etc']['cst_status'][$row['cst_status']] += $row['total'];
			}
			else {
				$total_list['normal']['team'][$row['team_code']] += $row['total'];
				$total_list['normal']['cst_status'][$row['cst_status']] += $row['total'];
			}

		}


		foreach($all_team_list as $k=>$v) {
			if(in_array($k, $team_etc)) {
				$team_list['etc'][$k]=$v;
			}
			else {
				$team_list['normal'][$k]=$v;
			}
		}

		$data = array (
			'list'=>$list,
			'team_list'=>$team_list,
			'total_list'=>$total_list,
			'output_mode'=>$this->output_mode
		);
		$this->load->view( 'stats/team_list', $data );
	}

	/**
	 * 상담항목별 분배현황
	 * @return [type] [description]
	 */
	public function item_lists() {
		$all_team_list = $this->User_model->get_team_list( '90');
		$db_category = $this->config->item( 'category' );
		$db_category['0'] = '상담항목없음';

		$where = array(
			'biz_id'=>$this->session->userdata('ss_biz_id')
		);
		if (! empty( $_GET['srch_start_date'] )) $where['reg_date >='] = str_replace( '-', '', $_GET['srch_start_date'] ) . '000000';
		if (! empty( $_GET['srch_end_date'] )) $where['reg_date <='] = str_replace( '-', '', $_GET['srch_end_date'] ) . '999999';
		if (! empty( $_GET['srch_path'] )) $where['path'] = $_GET['srch_path'];

		$where['org_team_code !='] = 'null';
		// $where['db_category !='] = '';
		$field = 'org_team_code as team_code, db_category, count(cst_seqno) as total, cst_status';
		$order_field = 'org_team_code';
		$group_field = 'org_team_code, db_category, cst_status';
		$result = $this->Assign_model->get_status_by_option( $where, $field, $order_field, $group_field );
		$total_category = array();
		// pre($result);
		foreach ( $result as $i => $row ) {
			$category = ($row['db_category'])?$row['db_category']:'0';
			// $team_list[$row['team_code']] = $all_team_list[$row['team_code']];
			$list[$row['team_code']][$category] += $row['total'];
			$total_list['team'][$row['team_code']] += $row['total'];
			$total_category[$category] += $row['total'];
			$total_status[$category][$row['cst_status']] += $row['total'];
			$total['sum']+=$row['total'];
			$total[$row['cst_status']]+=$row['total'];
		}

		arsort($total_category);
		foreach($total_category as $category => $cnt) {
			$rate['assign'] = round(($cnt/$total['sum'])*100,2);
			$rate['99'] = round(($total_status[$category]['99']/$cnt)*100,2);
			$tc[$category] = array(
				'code'=>$category,
				'name'=>$db_category[$category],
				'rate'=>$rate,
				'status'=>array(
					'99'=>$total_status[$category]['99']
				),
				'cnt'=>$cnt
			);
		}


		// pre($tc);
		$data = array (
			'list'=>$list,
			'team_list'=>$all_team_list,
			'total_list'=>$total_list,

			'total'=>array(
				'sum'=>$total['sum'],
				'99'=>$total['99'],
				'rate_99'=>round(($total['99']/$total['sum'])*100,2),
				'category'=>$tc
			),
			'db_category'=>$db_category,
			'output_mode'=>$this->output_mode
		);

		$this->load->view( 'stats/item_list', $data );
	}

	//유입경로별 통계
	public function path() {
		$srch_date = get_search_type_date();
		$team_list = $this->User_model->get_team_list(90);				// 20170201 kruddo 팀별 검색 > 팀 목록 추가

		$datum = array (
			'cfg'=>array(
				'today'=>date('Y-m-d'),
				'date'=>$srch_date,
				'team_list'=>$team_list
			)
		);
		$this->_render('path', $datum);
	}

	public function path_inner() {
		$this->load->model('Patient_Model');

		if($_GET['mode']=='excel') $param = $this->input->get(NULL, true);
		else  $param = $this->input->post(NULL, true);
		$path_list = $this->config->item('all_path');
		$lists = array();
		$total = array();

		$start_date = ($param['date_s'])?$param['date_s']:date('Y-m-d');				// 20170131 kruddo : srch_start_date -> date_s
		$end_date = ($param['date_e'])?$param['date_e']:date('Y-m-d');					// 20170131 kruddo : srch_end_date -> date_e


		$where_crm = array('date_project >='=>$start_date, 'date_project <= '=>$end_date, 'db.cst_status!=0'=>NULL);		// 20170223 kruddo : op매출액, op총액
		//$where_op = array('pa.appointment_date>='=>$start_date, 'pa.appointment_date<='=>$end_date );
		$where_op = array('pa.appointment_date >='=>$start_date, 'pa.appointment_date <= '=>$end_date);
		$where_op['status_code']='07-014';

		// 20170131 kruddo : 미디어코드별 분배현황 수정 - 팀별, 코드그룹별 검색 추가
		// 20170131 kruddo : 팀별 검색 조건 추가
		$team_code = $param['team_code'];
		if($team_code != 'all'){
			$where = " AND c.team_code='".$team_code."'";
			//$where_op = " AND pa.manager_team_code='".$team_code."'";
			$where_op['p.manager_team_code'] = $team_code;

			$where_crm['db.team_code'] = $team_code;

			$reg_user_id = $param['reg_user_id'];
			if($reg_user_id != null){
				if($reg_user_id != 'all'){
					$where = $where. " AND db.reg_user_id='".$reg_user_id."'";
					//$where_op = $where_op. " AND pa.manager_id='".$reg_user_id."'";
					$where_op['p.manager_id'] = $reg_user_id;

					$where_crm['db.charge_user_id'] = $reg_user_id;
				}
			}
		}
		// 20170131 kruddo : 팀별 검색 조건 추가

		// 20170222 kruddo : CRM>OP명수
		$where_opcount = $where_op;
		$where_opcount['type_code in (select code from code_item where code_type="1")'] = NULL;
		$result =  $this->Patient_Model->select_consulting_info_patient_join($where_opcount, 'count(pa.no) as cnt, ci.path', 'ci.path', 'ci.path');

		foreach ( $result as $i => $row ) {
			$oplist[$row['path']]['opcount'] = $row['cnt'];
		}

		// 20170222 kruddo : CRM>시술명수
		$where_opcount = $where_op;
		$where_opcount['type_code in ("06-010")'] = NULL;
		$result =  $this->Patient_Model->select_consulting_info_patient_join($where_opcount, 'count(pa.no) as cnt, ci.path', 'ci.path', 'ci.path');

		foreach ( $result as $i => $row ) {
			$oplist[$row['path']]['skincount'] = $row['cnt'];
		}

		/*
		$query = $this->db->query("select count(pa.no) as cnt, p.path_code from patient_appointment pa join patient AS p
			on p.no = pa.patient_no

			where p.is_delete='N'
			and status_code='07-014' and type_code in (select code from code_item where code_type='1')
			and appointment_date>='{$start_date}' and appointment_date<='{$end_date}'
			".$where_op."
			group by path_code");
		$rs = $query->result_array();
		$oplist = array();

		foreach($rs as $row) {
			$oplist[$row['path_code']]['opcount'] = $row['cnt'];
		}
		*/
		// 20170222 kruddo : CRM>OP명수

		// 20170222 kruddo : CRM>시술명수
		/*
		$query = $this->db->query("select count(pa.no) as cnt, p.path_code from patient_appointment pa join patient AS p
			on p.no = pa.patient_no
			where p.is_delete='N'
			and status_code='07-014' and type_code in ('06-010')
			and appointment_date>='{$start_date}' and appointment_date<='{$end_date}'
			".$where_op."
			group by path_code");
		$rs = $query->result_array();
		//oplist = array();

		foreach($rs as $row) {
			$oplist[$row['path_code']]['skincount'] = $row['cnt'];
		}
		*/
		// 20170222 kruddo : CRM>OP명수



		// 20170222 kruddo : CRM>OP매출액
		$result =  $this->Patient_Model->select_consulting_info_project_join($where_crm, 'db.path, sum(pp.amount_basic) as amount_basic', 'db.path', 'db.path');
		foreach ( $result as $i => $row ) {
			$oplist[$row['path']]['amount_basic'] = $row['amount_basic'];
		}

		// 20170222 kruddo : CRM>OP총액
		$result =  $this->Patient_Model->select_consulting_info_pay_join($where_crm, 'db.path, sum(ppp.amount_paid) as amount_paid', 'db.path', 'db.path');
		foreach ( $result as $i => $row ) {
			$oplist[$row['path']]['amount_paid'] = $row['amount_paid'];
		}



		foreach($path_list as $code=>$name) {
			$query = $this->db->query("SELECT c.cst_status, db.db_status, COUNT(*) AS cnt, SUM(c.patient_sales) AS sales, SUM(c.patient_paid) AS paid FROM db_info AS db LEFT JOIN consulting_info AS c ON(db.db_seqno=c.db_seqno) WHERE db.path='{$code}' AND db.db_status!=9 AND date_insert>='{$start_date} 00:00:00' AND date_insert<='{$end_date} 23:59:59' ".$where." GROUP BY c.cst_status, db.db_status");
			$rs = $query->result_array();
			$status = array();
			$price_sales = $price_paid = 0;
			$cnt = array();

			foreach($rs as $row) {
				$price_sales += $row['sales']; //매출액
				$price_paid += $row['paid']; //수납액
				$status[$row['cst_status']] += $row['cnt'];
				$cnt[$row['db_status']] += $row['cnt'];
				$cnt['total'] += $row['cnt'];

				//합계
				$total['total']+=$row['cnt'];
				$total['cnt'][$row['db_status']]+=$row['cnt'];
				$total['status'][$row['cst_status']]+=$row['cnt'];

				$total['price']['paid']+=$row['paid'];
				$total['price']['sales']+=$row['sales'];
			}
			$total['op']['opcount']+=$oplist[$code]['opcount'];		// op명수
			$total['op']['skincount']+=$oplist[$code]['skincount'];		// 피부시술명수
			$total['op']['amount_basic']+=$oplist[$code]['amount_basic'];		// OP매출액
			$total['op']['amount_paid']+=$oplist[$code]['amount_paid'];		// OP총액

			$lists[] = array(
				'code'=>$code,
				'name'=>$name,
				'status'=>$status,
				'cnt'=>$cnt,
				'price'=> array(
					'sales'=>$price_sales,
					'paid'=>$price_paid
				),
				'op'=>array(
					'opcount'=>$oplist[$code]['opcount'],
					'skincount'=>$oplist[$code]['skincount'],
					'amount_basic'=>$oplist[$code]['amount_basic'],
					'amount_paid'=>$oplist[$code]['amount_paid'],
				)
			);
		}

		$datum = array(
			'cfg'=>array(
				'date'=>$srch_date
			),
			'total'=>$total,
			'lists'=>$lists
		);

		if($param['mode']=='excel') {
			$html = $this->_render('path_inner', $datum, 'inc', true);
			$this->_excel('유입경로별분배현황_',$html);
		}
		else $this->_render('path_inner', $datum, 'inc');
	}



	//미디어코드별 통계
	public function media() {
	

		//이하 deprecated
		$srch_date = get_search_type_date();

		$team_list = $this->User_model->get_team_list(90);

		$datum = array (
			'cfg'=>array(
				'today'=>date('Y-m-d'),
				'date'=>$srch_date,
				'team_list'=>$team_list
			)
		);
		$this->_render('media', $datum);
	}


	/**
	 * 온라인매체별 통계 - v2
	 *
	 * @return void
	 */
	public function media_v2 () {
		$srch_date = get_search_type_date();

		$team_list = $this->User_model->get_team_list(90);

		$datum = array (
			'cfg'=>array(
				'today'=>date('Y-m-d'),
				'date'=>$srch_date,
				'team_list'=>$team_list
			)
		);
		$this->_render('media_v2', $datum);
	}

	public function media_v2_inner() {
		$p = $this->input->post(NULL, true);

		$sql = "SELECT
					cst.media,
					pp.amount_basic AS basic,
					pp.paid_total AS paid,
					pp.amount_unpaid AS unpaid,
					pp.amount_refund AS refund

					
				FROM patient_project AS pp LEFT JOIN consulting_info AS cst  ON(pp.patient_no = cst.patient_no)
				WHERE
					pp.date_project>='".$p['date_s']." 00:00:00' AND
					pp.date_project<='".$p['date_e']." 23:59:59' AND
					pp.is_delete = 'N'";

		if($p['type'] !='all') {
			$sql .= " AND type='".$p['type']."'";
		}
		$rs = $this->adodb->getArray($sql);
		// echo $sql;

		foreach($rs as $row) {
			
			if($row['media']) {
				
				$nd[$row['media']]['basic'] += $row['basic'];
				$nd[$row['media']]['paid'] += $row['paid'];
				$nd[$row['media']]['unpaid'] += $row['unpaid'];
				$nd[$row['media']]['refund'] += $row['refund'];
				$nd[$row['media']]['cnt']++;

			}
			else {
				$nd['etc']['basic'] += $row['basic'];
				$nd['etc']['paid'] += $row['paid'];
				$nd['etc']['unpaid'] += $row['unpaid'];
				$nd['etc']['refund'] += $row['refund'];
				$nd['etc']['cnt']++;
			}


			//echo $row['manager_team_code'].'('.$row['consulting_sales_yn'].')='.$row['basic']."<br />";

			$total['basic'] += $row['basic'];
			$total['paid'] += $row['paid'];
			$total['unpaid'] += $row['unpaid'];
			$total['refund'] += $row['refund'];
			$total['cnt'] ++;
		}

//	pre($nd);exit;


		$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'depth'=>1, 'use_flag'=>'Y')); //미디어 그룹
		foreach($media_group as $key=>$value) {
			$items = $this->common_lib->get_code_item( '04' , $key, 'all');
			$group_total = array();
			foreach($items as $k=>$v) {
				$sales = $nd[$v['title']];
				$items[$k]['sales']=$sales;

				foreach($sales as $sk=>$sv) {
					$group_total[$sk]+=$sv;
				}
			}

			//pre($group_total);

			$items['total'] = array(
				'title'=>'합계',
				'sales'=>$group_total
			);

			$list[$key] = array(
				'name'=>$value,
				'children'=>$items,
				'count'=>count($items)
			);
		}

		$list['etc'] = array(
			'name'=>'기타',
			'children'=>array(
				'etc'=>array(
					'title'=>'미디어코드없음',
					'sales'=>$nd['etc']
				)
			),
			'count'=>1
		);

		$assign = array(
			'search'=>$p,
			'list'=>$list,
			'total'=>$total
		);

		$this->_render('media_v2.inner', $assign, 'inc');


	}

	public function media_v2_detail() {
		$p = $this->input->post(NULL, true);
		$this->load->library('patient_lib');

		switch($p['tab']) {
			case 'media' :
				$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'use_flag'=>'Y')); //미디어 그룹
				//pre($media_group);
				if($p['media'] == 'etc') {
					$where = " AND (cst.media IS NULL OR cst.media='')";
					$team_name = '기타';
				}
				else {

					$where = " AND cst.media='".$media_group[$p['media']]."'";
					$team_name = $media_group[$p['media']];
				}
				break;

			default:
				$cfg_manager = $this->common_lib->get_user('90');

				if($p['manager_id'] == 'etc') {
					$where = " AND consulting_sales_yn IS NULL";
					$team_name = '피부/기타';
				}
				else {
					$where = " AND pp.consulting_sales_yn='Y' AND pp.manager_id='".$p['manager_id']."'";
					$team_name = $cfg_manager[$p['manager_id']];
				}
				break;
		}

		if($p['type'] && $p['type']!='all') {
			$where .= " AND type='".$p['type']."'";
		}


		switch($p['pay_field']) {
			case 'basic':
				$field_name = 'OP매출액';
				$field =", amount_basic AS amount";
			break;
			case 'paid':
				$field_name = '수납액';
				$field = ", paid_total AS amount";
				$where .= " AND paid_total > 0";
			break;
			case 'unpaid':
				$field_name = '미수금';
				$field = ", amount_unpaid AS amount";
				$where .= " AND amount_unpaid != 0";
			break;
			case 'refund':
				$field_name = '환불액';
				$field = ", amount_refund AS amount";
				$where .= " AND amount_refund > 0";
			break;
		}

		$sql = "SELECT
					p.name, pp.patient_no, pp.type, pp.treat_cost_no, pp.date_project, pp.manager_team_code
					{$field}
				FROM patient_project AS pp LEFT JOIN patient AS p ON(p.no=pp.patient_no) LEFT JOIN consulting_info AS cst ON (cst.patient_no = pp.patient_no)
				WHERE
					date_project>='".$p['date_s']." 00:00:00' AND
					date_project<='".$p['date_e']." 23:59:59' AND
					pp.is_delete='N' {$where}
				ORDER BY date_project ASC";
		$rs = $this->adodb->getArray($sql);
		$total_amount = $total_count = 0;


		foreach($rs as $row) {
			$row['treat_nav'] = $this->patient_lib->treat_nav($row['treat_cost_no'], ' &gt ');
			$row['team_name'] = $cfg_team[$row['manager_team_code']];
			$list[] = $row;
			$total_amount+=$row['amount'];
			$total_count++;
		}



		$assign = array(
			'cfg'=>array(
				'field_name'=>$field_name,
				'manager_name'=>$team_name
			),
			'field'=>$field_name,
			'title'=>$title,
			'list'=>$list,
			'total'=>array(
				'amount'=>$total_amount,
				'count'=>$total_count
			)
		);


		$this->_render('media_v2.detail', $assign, 'inc');
	}

	//미디어코드별 통계 - deprecated 2019-02-16
	public function media_inner() {
		$this->load->model('Patient_Model');

		if($_GET['mode']=='excel') $param = $this->input->get(NULL, true);
		else  $param = $this->input->post(NULL, true);

		//$lists = array();
		$total = array();
		$start_date = ($param['date_s'])?$param['date_s']:date('Y-m-d');				// 20170131 kruddo : srch_start_date -> date_s
		$end_date = ($param['date_e'])?$param['date_e']:date('Y-m-d');					// 20170131 kruddo : srch_end_date -> date_e


		// 20170131 kruddo : 미디어코드별 분배현황 수정 - 팀별, 코드그룹별 검색 추가
		// 20170131 kruddo : 팀별 검색 조건 추가

		// 20170223 kruddo : op매출액, op총액
		$where_crm = array('date_project >='=>$start_date, 'date_project <= '=>$end_date, 'db.cst_status!=0'=>NULL);
		$where_op = array('pa.appointment_date >='=>$start_date, 'pa.appointment_date <= '=>$end_date);
		$where_op['status_code']='07-014';
		$where_op['ci.path']='L';
		// 20170223 kruddo : op매출액, op총액

		$team_code = $param['team_code'];
		if($team_code != 'all'){
			$where = " AND c.team_code='".$team_code."'";

			$where_op['p.manager_team_code'] = $team_code;
			$where_crm['db.team_code'] = $team_code;

			$reg_user_id = $param['reg_user_id'];
			if($reg_user_id != null){
				if($reg_user_id != 'all'){
					$where = $where. " AND db.reg_user_id='".$reg_user_id."'";

					$where_op['p.manager_id'] = $reg_user_id;
					$where_crm['db.charge_user_id'] = $reg_user_id;
				}
			}
		}
		// 20170131 kruddo : 팀별 검색 조건 추가
		////////////////////////////////////////////////////// 20170222



		// 20170222 kruddo : CRM>OP명수
		$where_opcount = $where_op;
		$where_opcount['type_code in (select code from code_item where code_type="1")'] = NULL;
		$result =  $this->Patient_Model->select_consulting_info_patient_join($where_opcount, 'count(pa.no) as cnt, ci.media', 'ci.media', 'ci.media');

		foreach ( $result as $i => $row ) {
			$oplist[$row['media']]['opcount'] = $row['cnt'];
		}

		// 20170222 kruddo : CRM>시술명수
		$where_opcount = $where_op;
		$where_opcount['type_code in ("06-010")'] = NULL;
		$result =  $this->Patient_Model->select_consulting_info_patient_join($where_opcount, 'count(pa.no) as cnt, ci.media', 'ci.media', 'ci.media');

		foreach ( $result as $i => $row ) {
			$oplist[$row['media']]['skincount'] = $row['cnt'];
		}

		// 20170222 kruddo : CRM>OP매출액
		$result =  $this->Patient_Model->select_consulting_info_project_join($where_crm, 'db.media, sum(pp.amount_basic) as amount_basic', 'db.media', 'db.media');
		foreach ( $result as $i => $row ) {
			$oplist[$row['media']]['amount_basic'] = $row['amount_basic'];
		}

		// 20170222 kruddo : CRM>OP총액
		$result =  $this->Patient_Model->select_consulting_info_pay_join($where_crm, 'db.media, sum(ppp.amount_paid) as amount_paid', 'db.media', 'db.media');
		foreach ( $result as $i => $row ) {
			$oplist[$row['media']]['amount_paid'] = $row['amount_paid'];
		}

		////////////////////////////////////////////////////// 20170222


		/////////////////////////////////////////////////////////////////////////////////////// NEW

		$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'depth'=>1, 'use_flag'=>'Y')); //미디어 그룹
		foreach($media_group as $key=>$value) {
			$media_list = $this->common_lib->get_code_item( '04' , $key, 'all');
			$group_code = $key;
			$cnttotal = 0;

			foreach($media_list as $code=>$name) {
				$query = $this->db->query("SELECT c.cst_status, db.db_status, COUNT(*) AS cnt, SUM(c.patient_sales) AS sales, SUM(c.patient_paid) AS paid FROM db_info AS db LEFT JOIN consulting_info AS c ON(db.db_seqno=c.db_seqno) WHERE db.media='{$name[title]}' AND db.db_status!=9 AND date_insert>='{$start_date} 00:00:00' AND date_insert<='{$end_date} 23:59:59' ".$where."  GROUP BY c.cst_status, db.db_status");

				$rs = $query->result_array();

				foreach($rs as $row) {
					$cnttotal += $row['cnt'];
				}

				$group[$key] = array(
					'name'=>$value,
					'cnttotal'=>$cnttotal,
					'group_code'=>$group_code
				);

			}
		}


		$group_lists = array();

		if($param['code']."" != ""){
			$code_where = 'code="'.$param["code"].'"';
		}

		$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'depth'=>1, 'use_flag'=>'Y', $code_where=>NULL)); //미디어 그룹
		foreach($media_group as $key=>$value) {
			$media_list = $this->common_lib->get_code_item( '04' , $key, 'all');

			$lists = array();

			$cval = array();
			$cnttotal = 0;
			$group_code = $key;

			foreach($media_list as $code=>$name) {
				$query = $this->db->query("SELECT c.cst_status, db.db_status, COUNT(*) AS cnt, SUM(c.patient_sales) AS sales, SUM(c.patient_paid) AS paid FROM db_info AS db LEFT JOIN consulting_info AS c ON(db.db_seqno=c.db_seqno) WHERE db.media='{$name[title]}' AND db.db_status!=9 AND date_insert>='{$start_date} 00:00:00' AND date_insert<='{$end_date} 23:59:59' ".$where."  GROUP BY c.cst_status, db.db_status");

				$rs = $query->result_array();

				$status = array();
				$price_sales = $price_paid = 0;
				$cnt = array();

				foreach($rs as $row) {

					$price_sales += $row['sales']; //매출액
					$price_paid += $row['paid']; //수납액
					$status[$row['cst_status']] += $row['cnt'];
					$cnt[$row['db_status']] += $row['cnt'];
					$cnt['total'] += $row['cnt'];

					//합계
					$total['total']+=$row['cnt'];
					$total['cnt'][$row['db_status']]+=$row['cnt'];
					$total['status'][$row['cst_status']]+=$row['cnt'];

					$total['price']['paid']+=$row['paid'];
					$total['price']['sales']+=$row['sales'];

					$cnttotal += $row['cnt'];
				}

				$total['op']['opcount']+=$oplist[$name['title']]['opcount'];		// op명수
				$total['op']['skincount']+=$oplist[$name['title']]['skincount'];		// 피부시술명수
				$total['op']['amount_basic']+=$oplist[$name['title']]['amount_basic'];		// OP매출액
				$total['op']['amount_paid']+=$oplist[$name['title']]['amount_paid'];		// OP총액

				$lists[] = array(
					'code'=>$name[code],
					'name'=>$name[title],
					'status'=>$status,
					'cnt'=>$cnt,
					'price'=> array(
						'sales'=>$price_sales,
						'paid'=>$price_paid
					),
					'op'=>array(
						'opcount'=>$oplist[$name[title]]['opcount'],
						'skincount'=>$oplist[$name[title]]['skincount'],
						'amount_basic'=>$oplist[$name[title]]['amount_basic'],
						'amount_paid'=>$oplist[$name[title]]['amount_paid'],
					)
				);
			}

			$list[$key] = array(
				'name'=>$value,
				'children'=>$lists,
				'count'=>count($lists)
			);


			foreach($lists as $glists){
				$list[$key]['key'] = $key;
				$list[$key]['name'] = $value;

				//print_r($glists['status']['52']."<br>");

				$list[$key]['cnt_total'] += $glists['cnt']['total'];

				$list[$key]['cnt_1'] += $glists['cnt']['1'];
				$list[$key]['cnt_8'] += $glists['cnt']['8'];

				$list[$key]['cnt_51'] += $glists['status']['51'];
				$list[$key]['cnt_52'] += $glists['status']['52'];
				$list[$key]['cnt_98'] += $glists['status']['98'];
				$list[$key]['cnt_99'] += $glists['status']['99'];

				$list[$key]['price_sales'] += $glists['price']['sales'];
				$list[$key]['price_paid'] += $glists['price']['paid'];

				$list[$key]['op_opcount'] += $glists['op']['opcount'];
				$list[$key]['op_skincount'] += $glists['op']['skincount'];
				$list[$key]['op_amount_basic'] += $glists['op']['amount_basic'];
				$list[$key]['op_amount_paid'] += $glists['op']['amount_paid'];
			}

		}

		$datum = array(
			'total'=>$total,
			'list'=>$list,
			'group' => $group,
			//'group_lists' => $group_lists
		);

		// 20170131 kruddo : 미디어코드별 분배현황 수정 - 팀별, 코드그룹별 검색 추가

		if($param['mode']=='excel') {
			$html = $this->_render('media_inner', $datum, 'inc', true);
			$this->_excel('미디어코드별분배현황_',$html);
		}
		else $this->_render('media_inner', $datum, 'inc');

		//return_json(true, '', $datum);



		/*
		$group_lists = array();

		if($param['code']."" != ""){
			$code_where = 'code="'.$param["code"].'"';
		}

		$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'depth'=>1, 'use_flag'=>'Y', $code_where=>NULL)); //미디어 그룹
		foreach($media_group as $key=>$value) {
			$media_list = $this->common_lib->get_code_item( '04' , $key, 'all');

			$lists = array();

			$cval = array();
			$cnttotal = 0;
			$group_code = $key;

			foreach($media_list as $code=>$name) {
				$query = $this->db->query("SELECT c.cst_status, db.db_status, COUNT(*) AS cnt, SUM(c.patient_sales) AS sales, SUM(c.patient_paid) AS paid FROM db_info AS db LEFT JOIN consulting_info AS c ON(db.db_seqno=c.db_seqno) WHERE db.media='{$name[title]}' AND db.db_status!=9 AND date_insert>='{$start_date} 00:00:00' AND date_insert<='{$end_date} 23:59:59' ".$where."  GROUP BY c.cst_status, db.db_status");

				$rs = $query->result_array();

				$status = array();
				$price_sales = $price_paid = 0;
				$cnt = array();

				foreach($rs as $row) {

					if($param['code']."" == ""){
						$price_sales += $row['sales']; //매출액
						$price_paid += $row['paid']; //수납액
						$status[$row['cst_status']] += $row['cnt'];
						$cnt[$row['db_status']] += $row['cnt'];
						$cnt['total'] += $row['cnt'];

						//합계
						$total['total']+=$row['cnt'];
						$total['cnt'][$row['db_status']]+=$row['cnt'];
						$total['status'][$row['cst_status']]+=$row['cnt'];

						$total['price']['paid']+=$row['paid'];
						$total['price']['sales']+=$row['sales'];
					}
					else{
						if($key == $param['code']){
							$price_sales += $row['sales']; //매출액
							$price_paid += $row['paid']; //수납액
							$status[$row['cst_status']] += $row['cnt'];
							$cnt[$row['db_status']] += $row['cnt'];
							$cnt['total'] += $row['cnt'];

							//합계
							$total['total']+=$row['cnt'];
							$total['cnt'][$row['db_status']]+=$row['cnt'];
							$total['status'][$row['cst_status']]+=$row['cnt'];

							$total['price']['paid']+=$row['paid'];
							$total['price']['sales']+=$row['sales'];
						}
					}

					$cnttotal += $row['cnt'];
				}



				if($param['code'] == ''){
					$total['op']['opcount']+=$oplist[$name['title']]['opcount'];		// op명수
					$total['op']['skincount']+=$oplist[$name['title']]['skincount'];		// 피부시술명수
					$total['op']['amount_basic']+=$oplist[$name['title']]['amount_basic'];		// OP매출액
					$total['op']['amount_paid']+=$oplist[$name['title']]['amount_paid'];		// OP총액
				}
				else{
					if($key == $param['code']){
						$total['op']['opcount']+=$oplist[$name['title']]['opcount'];		// op명수
						$total['op']['skincount']+=$oplist[$name['title']]['skincount'];		// 피부시술명수
						$total['op']['amount_basic']+=$oplist[$name['title']]['amount_basic'];		// OP매출액
						$total['op']['amount_paid']+=$oplist[$name['title']]['amount_paid'];		// OP총액
					}
				}

				$lists[] = array(
					'code'=>$name[code],
					'name'=>$name[title],
					'status'=>$status,
					'cnt'=>$cnt,
					'price'=> array(
						'sales'=>$price_sales,
						'paid'=>$price_paid
					),
					'op'=>array(
						'opcount'=>$oplist[$name[title]]['opcount'],
						'skincount'=>$oplist[$name[title]]['skincount'],
						'amount_basic'=>$oplist[$name[title]]['amount_basic'],
						'amount_paid'=>$oplist[$name[title]]['amount_paid'],
					)
				);
			}


			if($param['code']."" == ""){
				$list[$key] = array(
					'name'=>$value,
					'children'=>$lists,
					'count'=>count($lists)
				);
			}
			else{
				if($key == $param['code']){
					$list[$key] = array(
						'name'=>$value,
						'children'=>$lists,
						'count'=>count($lists)
					);
				}
			}


			foreach($lists as $glists){
				$list[$key]['key'] = $key;
				$list[$key]['name'] = $value;

				//print_r($glists['status']['52']."<br>");

				$list[$key]['cnt_total'] += $glists['cnt']['total'];

				$list[$key]['cnt_1'] += $glists['cnt']['1'];
				$list[$key]['cnt_8'] += $glists['cnt']['8'];

				$list[$key]['cnt_51'] += $glists['status']['51'];
				$list[$key]['cnt_52'] += $glists['status']['52'];
				$list[$key]['cnt_98'] += $glists['status']['98'];
				$list[$key]['cnt_99'] += $glists['status']['99'];

				$list[$key]['price_sales'] += $glists['price']['sales'];
				$list[$key]['price_paid'] += $glists['price']['paid'];

				$list[$key]['op_opcount'] += $glists['op']['opcount'];
				$list[$key]['op_skincount'] += $glists['op']['skincount'];
				$list[$key]['op_amount_basic'] += $glists['op']['amount_basic'];
				$list[$key]['op_amount_paid'] += $glists['op']['amount_paid'];
			}


			$group[$key] = array(
				'name'=>$value,
				'cnttotal'=>$cnttotal,
				'group_code'=>$group_code
			);
		}

		$datum = array(
			'total'=>$total,
			'list'=>$list,
			'group' => $group,
			'group_lists' => $group_lists
		);

		// 20170131 kruddo : 미디어코드별 분배현황 수정 - 팀별, 코드그룹별 검색 추가

		if($param['mode']=='excel') {
			$html = $this->_render('media_inner', $datum, 'inc', true);
			$this->_excel('미디어코드별분배현황_',$html);
		}
		else $this->_render('media_inner', $datum, 'inc');

		//return_json(true, '', $datum);
		*/

		/////////////////////////////////////////////////////////////////////////////////////// NEW

		/*
		$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'depth'=>1, 'use_flag'=>'Y')); //미디어 그룹
		foreach($media_group as $key=>$value) {
			$media_list = $this->common_lib->get_code_item( '04' , $key, 'all');

			$lists = array();

			$cval = array();
			$cnttotal = 0;
			$group_code = $key;

			foreach($media_list as $code=>$name) {
				$query = $this->db->query("SELECT c.cst_status, db.db_status, COUNT(*) AS cnt, SUM(c.patient_sales) AS sales, SUM(c.patient_paid) AS paid FROM db_info AS db LEFT JOIN consulting_info AS c ON(db.db_seqno=c.db_seqno) WHERE db.media='{$name[title]}' AND db.db_status!=9 AND date_insert>='{$start_date} 00:00:00' AND date_insert<='{$end_date} 23:59:59' ".$where."  GROUP BY c.cst_status, db.db_status");

				$rs = $query->result_array();

				$status = array();
				$price_sales = $price_paid = 0;
				$cnt = array();

				foreach($rs as $row) {

					if($param['code']."" == ""){
						$price_sales += $row['sales']; //매출액
						$price_paid += $row['paid']; //수납액
						$status[$row['cst_status']] += $row['cnt'];
						$cnt[$row['db_status']] += $row['cnt'];
						$cnt['total'] += $row['cnt'];

						//합계
						$total['total']+=$row['cnt'];
						$total['cnt'][$row['db_status']]+=$row['cnt'];
						$total['status'][$row['cst_status']]+=$row['cnt'];

						$total['price']['paid']+=$row['paid'];
						$total['price']['sales']+=$row['sales'];
					}
					else{
						if($key == $param['code']){
							$price_sales += $row['sales']; //매출액
							$price_paid += $row['paid']; //수납액
							$status[$row['cst_status']] += $row['cnt'];
							$cnt[$row['db_status']] += $row['cnt'];
							$cnt['total'] += $row['cnt'];

							//합계
							$total['total']+=$row['cnt'];
							$total['cnt'][$row['db_status']]+=$row['cnt'];
							$total['status'][$row['cst_status']]+=$row['cnt'];

							$total['price']['paid']+=$row['paid'];
							$total['price']['sales']+=$row['sales'];
						}
					}

					$cnttotal += $row['cnt'];
				}

				if($param['code'] == ''){
					$total['op']['opcount']+=$oplist[$name['title']]['opcount'];		// op명수
					$total['op']['skincount']+=$oplist[$name['title']]['skincount'];		// 피부시술명수
					$total['op']['amount_basic']+=$oplist[$name['title']]['amount_basic'];		// OP매출액
					$total['op']['amount_paid']+=$oplist[$name['title']]['amount_paid'];		// OP총액
				}
				else{
					if($key == $param['code']){
						$total['op']['opcount']+=$oplist[$name['title']]['opcount'];		// op명수
						$total['op']['skincount']+=$oplist[$name['title']]['skincount'];		// 피부시술명수
						$total['op']['amount_basic']+=$oplist[$name['title']]['amount_basic'];		// OP매출액
						$total['op']['amount_paid']+=$oplist[$name['title']]['amount_paid'];		// OP총액
					}
				}

				$lists[] = array(
					'code'=>$name[code],
					'name'=>$name[title],
					'status'=>$status,
					'cnt'=>$cnt,
					'price'=> array(
						'sales'=>$price_sales,
						'paid'=>$price_paid
					),
					'op'=>array(
						'opcount'=>$oplist[$name[title]]['opcount'],
						'skincount'=>$oplist[$name[title]]['skincount'],
						'amount_basic'=>$oplist[$name[title]]['amount_basic'],
						'amount_paid'=>$oplist[$name[title]]['amount_paid'],
					)
				);
			}


			if($param['code']."" == ""){
				$list[$key] = array(
					'name'=>$value,
					'children'=>$lists,
					'count'=>count($lists)
				);
			}
			else{
				if($key == $param['code']){
					$list[$key] = array(
						'name'=>$value,
						'children'=>$lists,
						'count'=>count($lists)
					);
				}
			}

			$group[$key] = array(
				'name'=>$value,
				'cnttotal'=>$cnttotal,
				'group_code'=>$group_code
			);
		}

		$datum = array(
			'total'=>$total,
			'list'=>$list,
			'group' => $group,
			'group_lists' => $group_lists
		);

		// 20170131 kruddo : 미디어코드별 분배현황 수정 - 팀별, 코드그룹별 검색 추가

		if($param['mode']=='excel') {
			$html = $this->_render('media_inner', $datum, 'inc', true);
			$this->_excel('미디어코드별분배현황_',$html);
		}
		else $this->_render('media_inner', $datum, 'inc');

		return_json(true, '', $datum);

		*/


	}

	//연령별별 통계
	public function age() {
		$srch_date = get_search_type_date();
		$team_list = $this->User_model->get_team_list( '90' );
		$all_path = $this->config->item('all_path');
		$datum = array (
			'cfg'=>array(
				'today'=>date('Y-m-d'),
				'date'=>$srch_date,
				'team'=>$team_list,
				'path'=>$all_path,
				'media'=>$this->common_lib->get_cfg('media')
			)
		);
		$this->_render('age', $datum);
	}

	public function age_inner() {
		if($_GET['mode']=='excel') $param = $this->input->get(NULL, true);
		else  $param = $this->input->post(NULL, true);

		$media_list = $this->common_lib->get_cfg('media');

		$lists = array();
		$total = array();
		$where = array();

		foreach($param['search'] as $k=>$v) {
			if(!$v) continue;
			switch($k) {
				case 'start_date':
					$where[]="date_insert >='{$v} 00:00:00'";
				break;
				case 'end_date':
					$where[]="date_insert <= '{$v} 23:59:59'";
				break;
				case 'team_code':
				case 'path':
				case 'media':
					$where[] = "c.{$k} = '{$v}'";
				break;
			}
		}

		$where_sql = implode(' AND ', $where);
		//$start_date = ($param['srch_start_date'])?$param['srch_start_date']:date('Y-m-d');
		//$end_date = ($param['srch_end_date'])?$param['srch_end_date']:date('Y-m-d');


		$range = range(1, 67, 5);
		foreach($range as $k=>$v) {
			if($v == 61) {
				$end = date('Y')+100;
				$start = $v+1;
				$where_age = "AND LEFT(c.birth, 4)>='{$start}' AND LEFT(c.birth, 4)<='{$end}'";
				$name = '61~';
			}
			else if($v == 66) {
				$where_age = "AND (c.birth IS NULL or LEFT(c.birth, 4)='0000')" ;
				$name = '정보없음';
			}
			else {
				$end = date('Y')-$v+1;
				$start = $end-4;
				$where_age = "AND LEFT(c.birth, 4)>='{$start}' AND LEFT(c.birth, 4)<='{$end}'";
				$name = $v.'~'.($v+4);
			}

			$sql = "SELECT c.cst_status, db.db_status, COUNT(*) AS cnt, SUM(c.patient_sales) AS sales, SUM(c.patient_paid) AS paid, LEFT(c.birth, 4) AS byear FROM db_info AS db LEFT JOIN consulting_info AS c ON(db.db_seqno=c.db_seqno) WHERE db.db_status!=9 AND {$where_sql} {$where_age} GROUP BY c.cst_status, db.db_status";

			//echo $sql."<br />";
			$query = $this->db->query($sql);


			$rs = $query->result_array();

			$status = array();
			$price_sales = $price_paid = 0;
			$cnt = array();



			foreach($rs as $row) {
				$price_sales += $row['sales']; //매출액
				$price_paid += $row['paid']; //수납액
				$status[$row['cst_status']] += $row['cnt'];
				$cnt[$row['db_status']] += $row['cnt'];
				$cnt['total'] += $row['cnt'];

				//합계
				$total['total']+=$row['cnt'];
				$total['cnt'][$row['db_status']]+=$row['cnt'];
				$total['status'][$row['cst_status']]+=$row['cnt'];

				$total['price']['paid']+=$row['paid'];
				$total['price']['sales']+=$row['sales'];
			}

			$lists[] = array(
				'code'=>$code,
				'name'=>$name,
				'status'=>$status,
				'cnt'=>$cnt,
				'price'=> array(
					'sales'=>$price_sales,
					'paid'=>$price_paid
				)
			);
		}


		$datum = array(
			'total'=>$total,
			'lists'=>$lists
		);

		if($param['mode']=='excel') {
			$html = $this->_render('age_inner', $datum, 'inc', true);
			$this->_excel('연령별상담현황_',$html);
		}
		else $this->_render('age_inner', $datum, 'inc');
	}

	private function _excel($filename, $html) {
		$file_name = $filename.'_'.date('ymd').'.xls';

		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Pragma: no-cache'); // HTTP/1.0
		header('Content-type: application/octet-stream; name='.$file_name);
		header('Content-Disposition: attachment; filename='.$file_name);

		echo '<html>';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="application/vnd.ms-excel;charset=utf-8">';
		echo '<style>table {border-collapse:collapse;} th, td {border:solid thin #000;border-collapse:collapse;}</style>';
		echo '</head>';
		echo '<body>';
		echo $html;
		echo '</body>';
		echo '</html>';
	}

	private function _render($tmpl, $datum, $layout='default', $fetch=false) {
		$tpl = "stats/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum, $layout);
		if($fetch) {
			return $this->layout_lib->print_(true);
		}
		else {
			$this->layout_lib->print_();
		}

	}



	// 20170208 kruddo : DB실적->상담팀실적으로 수정
	/**
	 * 상담팀 실적
	 * @return [type] [description]
	 */
	public function consulting_team_main() {
		$this->page_title = "상담팀 실적";

		// 유입경로
		$all_path_list = $this->config->item( 'all_path' );

		$srch_date = get_search_type_date();
		$data = array (
			'cfg'=>array(
				'path'=>$all_path_list,
				'date'=>$srch_date
			)
		);
		$this->load->view( 'stats/consulting_team_main', $data );
	}



	public function consulting_team_lists() {

		// 신규 상담 실적
		$where_path = 'db.path != "E"';
		$team = $this->get_consulting_result($where_path, '');

		$new = array (
			'team_list' => $team
		);


		// 기존 상담 실적
		$where_path = 'db.path = "E"';
		$team = $this->get_consulting_result($where_path, '');

		$old = array (
			'team_list' => $team
		);


		$output_mode = $this->output_mode;

		$datum = array (
			'cfg'=>array(
				'path'=>$this->config->item( 'all_path' ),				// 유입경로
				'media'=>$this->common_lib->get_cfg('media'),			// 미디어
				'team'=>$this->User_model->get_team_list( '90' )				// 팀목록
			),
			'new'=>$new,
			'old'=>$old

		);

		$this->_render('consulting_team_list', $datum, 'inc');
		//$this->consulting_team_path_lists();
	}


	public function consulting_team_path_lists() {

		// 내원경로별 상담팀 실적
		$where_path = '';
		$where_media = '';


		if($this->input->post('srch_path_txt') != ""){
			$where_path = 'db.path="'.$this->input->post('srch_path_txt').'"';

			if($this->input->post('srch_media_txt') != ""){
				$where_media = 'db.media="'.$this->input->post('srch_media_txt').'"';
			}
		}

		$team = $this->get_consulting_result($where_path, $where_media);
		$path = array (
			'team_list' => $team
		);

		$output_mode = $this->output_mode;

		$datum = array (
			'path'=>$path
		);

	//	pre($path);


		$this->_render('consulting_team_path_list', $datum, 'inc');
		//return_json(true, '', $datum);
	}


	public function consulting_doctor_lists() {

		$team = $this->get_consulting_doctor_result($where_path, $where_media);
		$path = array (
			'team_list' => $team
		);

		$output_mode = $this->output_mode;

		$datum = array (
			'path'=>$path
		);


		$this->_render('consulting_doctor_list', $datum, 'inc');
		//return_json(true, '', $datum);
	}

	private function get_consulting_doctor_result($where_path, $where_media){
		$this->load->model('Patient_Model');



		$srch_start_date = $this->input->post('srch_start_date');
		$srch_end_date = $this->input->post('srch_end_date');

		// 원장 목록
		$team_list = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'), $this->session->userdata('ss_biz_id'));


		// 매출액
		$where = array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, $where_path=>NULL, $where_media=>NULL, 'db.cst_status!=0'=>NULL);
		$result =  $this->Patient_Model->select_consulting_info_project_join($where, 'pp.doctor_id as doctor_id, sum(pp.amount_basic) as amount_basic', 'pp.doctor_id', 'pp.doctor_id');

		foreach ( $result as $i => $row ) {
			$sales[$row['doctor_id']]['amount_basic'] = $row['amount_basic'];
		}

		// op수납, 환불금액
		//$where = array('reg_date >='=>$srch_start_date, 'reg_date <= '=>$srch_end_date, $where_path=>NULL, $where_media=>NULL, 'ci.cst_status!=0'=>NULL);
		$where = array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, $where_path=>NULL, $where_media=>NULL, 'db.cst_status!=0'=>NULL);
		$result =  $this->Patient_Model->select_consulting_info_pay_join($where, 'pp.doctor_id as doctor_id, sum(ppp.amount_refund) as amount_refund, sum(ppp.amount_paid) as amount_paid'
		, 'pp.doctor_id', 'pp.doctor_id');

		foreach ( $result as $i => $row ) {
			$sales[$row['doctor_id']]['amount_refund'] = $row['amount_refund'];
			$sales[$row['doctor_id']]['amount_paid'] = $row['amount_paid'];
		}

		// 예치금
		$where = array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, $where_path=>NULL, $where_media=>NULL, 'sales_type'=>'예치', 'db.cst_status!=0'=>NULL);
		$result =  $this->Patient_Model->select_consulting_info_pay_join($where, 'pp.doctor_id as doctor_id, sum(ppp.amount_paid) as amount_paid'
		, 'pp.doctor_id', 'pp.doctor_id');

		foreach ( $result as $i => $row ) {
			$sales[$row['doctor_id']]['amount_deposit'] = $row['amount_paid'];
		}


		//사업장별 기본 조건
		$where = array();
		$where['db.biz_id'] = $this->session->userdata('ss_biz_id');
		$where['db.db_status=1'] = NULL;
		//$where['db.db_status!=9'] = NULL;


		if (! empty( $srch_start_date )) $where['pa.appointment_date >='] = $srch_start_date;//.' 00:00:00';
		if (! empty( $srch_end_date )) $where['pa.appointment_date <='] = $srch_end_date;//.' 23:59:59';

		//$db_list = array();



		if ($where_path != ''){
			$where[$where_path]=NULL;
			//$where['db.db_status=1'] = NULL;
			if ($where_media != '')		$where[$where_media]=NULL;
		}


		//'98'=>'수술예정',		//'90'=>'수술취소',		//'99'=>'수술완료',		//'52'=>'내원상담',
		//'07-001'=>'취소',		//'07-015'=>'재수술',		//'07-014'=>'귀가(수술완료)',		//'52'=>'내원상담'
		$sales = $this->get_doctor_cst_status($sales, $where, '015');		// 재수술
		$sales = $this->get_doctor_cst_status($sales, $where, '012');		// 취소
		$sales = $this->get_doctor_cst_status($sales, $where, '024');		// 수술완료
		$sales = $this->get_doctor_cst_status($sales, $where, '022');		// 수술 예정
		$sales = $this->get_doctor_cst_status($sales, $where, '');			// 내원


		$team = array();
		foreach($team_list as $code=>$name) {
			if($sales[$code]['status_code_015'] > 0 || $sales[$code]['status_code_012'] > 0 || $sales[$code]['status_code_024'] > 0 || $sales[$code]['status_code_022'] > 0 || $sales[$code]['status_code_'] > 0 ||
				$sales[$code]['amount_refund']>0 || $sales[$code]['amount_paid']>0  || $sales[$code]['amount_deposit']>0 || $sales[$code]['amount_basic']>0){

				$team[$code] = array(
					'team_code'=>$code,
					'name'=>$name,
					//'cur_db'=>$sales[$code]['cur_db'],

					'status_code_'=>$sales[$code]['status_code_'],				// 재수술
					'status_code_015'=>$sales[$code]['status_code_015'],				// 재수술
					'status_code_022'=>$sales[$code]['status_code_022'],		// 수술예정
					'status_code_012'=>$sales[$code]['status_code_012'],		// 수술취소
					'status_code_024'=>$sales[$code]['status_code_024'],		// 수술완료

					'amount_refund'=>$sales[$code]['amount_refund'],
					'amount_paid'=>$sales[$code]['amount_paid'],
					'amount_deposit'=>$sales[$code]['amount_deposit'],
					'amount_basic'=>$sales[$code]['amount_basic'],

					//'status_code_015_rate'=>$sales[$code]['status_code_015']/1*100,		// 내원
					//'status_code_022_rate'=>$sales[$code]['status_code_022']/1*100,		// 수술예정
					//'status_code_012_rate'=>$sales[$code]['status_code_012']/1*100,		// 수술취소
					//'status_code_024_rate'=>$sales[$code]['status_code_024']/1*100,		// 수술완료
				);
				//$team_total['cur_db'] += $sales[$code]['cur_db'];

				$team_total['status_code_'] += $sales[$code]['status_code_'];
				$team_total['status_code_015'] += $sales[$code]['status_code_015'];
				$team_total['status_code_022'] += $sales[$code]['status_code_022'];
				$team_total['status_code_012'] += $sales[$code]['status_code_012'];
				$team_total['status_code_024'] += $sales[$code]['status_code_024'];

				$team_total['amount_refund'] += $sales[$code]['amount_refund'];
				$team_total['amount_paid'] += $sales[$code]['amount_paid'];
				$team_total['amount_deposit'] += $sales[$code]['amount_deposit'];
				$team_total['amount_basic'] += $sales[$code]['amount_basic'];
			}

		}

		//$team_total['status_code_015_rate'] = $team_total['status_code_015']/1*100;
		//$team_total['status_code_022_rate'] = $team_total['status_code_022']/1*100;
		//$team_total['status_code_012_rate'] = $team_total['status_code_012']/1*100;
		//$team_total['status_code_024_rate'] = $team_total['status_code_024']/1*100;

		$data = array(
			'team' => $team,
			'team_total' => $team_total
		);
		//return_json(true, '', $data);

		return $data;

	}



	private function get_consulting_result($where_path, $where_media){
		$this->load->model('Patient_Model');



		$srch_start_date = $this->input->post('srch_start_date');
		$srch_end_date = $this->input->post('srch_end_date');


		// 상담팀은 자기 팀 데이터만
		if($this->session->userdata( 'ss_dept_code' ) == '90'){
			$team_list = $this->User_model->get_team_code_list($this->session->userdata( 'ss_team_code' ) );
		}
		else{
			$team_list = $this->User_model->get_team_list( '90' );
		}



		// 매출액
		$where = array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, $where_path=>NULL, $where_media=>NULL, 'db.cst_status!=0'=>NULL, 'pp.consulting_sales_yn'=>'Y');
		$result =  $this->Patient_Model->select_consulting_info_project_join($where, 'db.team_code as team_code, sum(pp.amount_basic) as amount_basic', 'db.team_code', 'db.team_code');

		foreach ( $result as $i => $row ) {
			$sales[$row['team_code']]['amount_basic'] = $row['amount_basic'];
		}

		// op수납, 환불금액
		//$where = array('reg_date >='=>$srch_start_date, 'reg_date <= '=>$srch_end_date, $where_path=>NULL, $where_media=>NULL, 'ci.cst_status!=0'=>NULL);
		$where = array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, $where_path=>NULL, $where_media=>NULL, 'db.cst_status!=0'=>NULL, 'pp.consulting_sales_yn'=>'Y');
		$result =  $this->Patient_Model->select_consulting_info_pay_join($where, 'db.team_code as team_code, sum(ppp.amount_refund) as amount_refund, sum(ppp.amount_paid) as amount_paid'
		, 'db.team_code', 'db.team_code');

		foreach ( $result as $i => $row ) {
			$sales[$row['team_code']]['amount_refund'] = $row['amount_refund'];
			$sales[$row['team_code']]['amount_paid'] = $row['amount_paid'];
		}

		// 예치금
		$where = array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, $where_path=>NULL, $where_media=>NULL, 'sales_type'=>'예치', 'db.cst_status!=0'=>NULL, 'pp.consulting_sales_yn'=>'Y');
		$result =  $this->Patient_Model->select_consulting_info_pay_join($where, 'db.team_code as team_code, sum(ppp.amount_paid) as amount_paid'
		, 'db.team_code', 'db.team_code');

		foreach ( $result as $i => $row ) {
			$sales[$row['team_code']]['amount_deposit'] = $row['amount_paid'];
		}


		//사업장별 기본 조건
		$where = array();
		$where['db.biz_id'] = $this->session->userdata('ss_biz_id');
		$where['db.db_status=1'] = NULL;
		//$where['db.db_status!=9'] = NULL;


		if (! empty( $srch_start_date )) $where['date_insert >='] = $srch_start_date.' 00:00:00';
		if (! empty( $srch_end_date )) $where['date_insert <='] = $srch_end_date.' 23:59:59';

		//$db_list = array();



		if ($where_path != ''){
			$where[$where_path]=NULL;
			//$where['db.db_status=1'] = NULL;
			if ($where_media != '')		$where[$where_media]=NULL;
		}


		//'98'=>'수술예정',
		//'90'=>'수술취소',
		//'99'=>'수술완료',
		//'52'=>'내원상담',
		//'51'=>'내원예약',
		$sales = $this->get_cst_status($sales, $where, '');
		$sales = $this->get_cst_status($sales, $where, '51');
		$sales = $this->get_cst_status($sales, $where, '52');
		$sales = $this->get_cst_status($sales, $where, '98');
		$sales = $this->get_cst_status($sales, $where, '90');
		$sales = $this->get_cst_status($sales, $where, '99');



		$result = $this->Patient_Model->select_consulting_info_join($where, 'c.team_code as team_code, count(team_code) as total');
		//현재보유DB
		foreach ( $result as $i => $row ) {
			$sales[$row['team_code']]['cur_db'] = $row['total'];
		}


		$where = array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, $where_path=>NULL, $where_media=>NULL, 'db.cst_status!=0'=>NULL);
		$result =  $this->Patient_Model->select_consulting_info_pay_join($where, 'db.team_code as team_code, ppp.sales_type, sum(ppp.amount_refund) as amount_refund, sum(ppp.amount_paid) as amount_paid, sum(pp.amount_basic) as amount_basic'
		, 'db.team_code, ppp.sales_type', 'db.team_code ASC');

		//echo $this->db->last_query();
		foreach ( $result as $i => $row ) {
			if($row['sales_type'] == '예치') $sales[$row['team_code']]['amount_deposit'] = $row['amount_paid'];

			$sales[$row['team_code']]['amount_refund'] += $row['amount_refund'];
			$sales[$row['team_code']]['amount_paid'] += $row['amount_paid'];
			$sales[$row['team_code']]['amount_basic'] += $row['amount_basic'];
		}



	//	pre($sales);



		$team = array();
		foreach($team_list as $code=>$name) {
			if($sales[$code]['cur_db'] > 0 || $sales[$code]['cst_status_'] > 0 || $sales[$code]['cst_status_98'] > 0 || $sales[$code]['cst_status_90'] > 0 || $sales[$code]['cst_status_99'] > 0 ||
				$sales[$code]['amount_refund']>0 || $sales[$code]['amount_paid']>0  || $sales[$code]['amount_deposit']>0 || $sales[$code]['amount_basic']>0){

				$team[$code] = array(
					'team_code'=>$code,
					'name'=>$name,
					'cur_db'=>$sales[$code]['cur_db'],
					'cst_status_51'=>$sales[$code]['cst_status_51'],				// 내원예약
					'cst_status_52'=>$sales[$code]['cst_status_52'],				// 내원상담
					'cst_status_98'=>$sales[$code]['cst_status_98'],		// 수술예정
					'cst_status_90'=>$sales[$code]['cst_status_90'],		// 수술취소
					'cst_status_99'=>$sales[$code]['cst_status_99'],		// 수술완료

					'amount_refund'=>$sales[$code]['amount_refund'],
					'amount_paid'=>$sales[$code]['amount_paid'],
					'amount_deposit'=>$sales[$code]['amount_deposit'],
					'amount_basic'=>$sales[$code]['amount_basic'],

					'cst_status_51_rate'=>$sales[$code]['cst_status_51']/$sales[$code]['cur_db']*100,				// 내원예약
					'cst_status_52_rate'=>$sales[$code]['cst_status_52']/$sales[$code]['cur_db']*100,				// 내원상담
					'cst_status_98_rate'=>$sales[$code]['cst_status_98']/$sales[$code]['cur_db']*100,		// 수술예정
					'cst_status_90_rate'=>$sales[$code]['cst_status_90']/$sales[$code]['cur_db']*100,		// 수술취소
					'cst_status_99_rate'=>$sales[$code]['cst_status_99']/$sales[$code]['cur_db']*100,		// 수술완료
				);
				$team_total['cur_db'] += $sales[$code]['cur_db'];

				$team_total['cst_status_51'] += $sales[$code]['cst_status_51'];
				$team_total['cst_status_52'] += $sales[$code]['cst_status_52'];
				$team_total['cst_status_98'] += $sales[$code]['cst_status_98'];
				$team_total['cst_status_90'] += $sales[$code]['cst_status_90'];
				$team_total['cst_status_99'] += $sales[$code]['cst_status_99'];

				$team_total['amount_refund'] += $sales[$code]['amount_refund'];
				$team_total['amount_paid'] += $sales[$code]['amount_paid'];
				$team_total['amount_deposit'] += $sales[$code]['amount_deposit'];
				$team_total['amount_basic'] += $sales[$code]['amount_basic'];
			}

		}

		$team_total['cst_status_rate'] = $team_total['cst_status']/$team_total['cur_db']*100;
		$team_total['cst_status_51_rate'] = $team_total['cst_status_51']/$team_total['cur_db']*100;
		$team_total['cst_status_52_rate'] = $team_total['cst_status_52']/$team_total['cur_db']*100;
		$team_total['cst_status_98_rate'] = $team_total['cst_status_98']/$team_total['cur_db']*100;
		$team_total['cst_status_90_rate'] = $team_total['cst_status_90']/$team_total['cur_db']*100;
		$team_total['cst_status_99_rate'] = $team_total['cst_status_99']/$team_total['cur_db']*100;

		$data = array(
			'team' => $team,
			'team_total' => $team_total
		);

		//pre($data);
		return $data;

	}

	private function get_cst_status($sales, $where, $status) {
		$whereadd = array();
		$whereadd = $where;
		if($status == ''){
			$whereadd["( cst_status = '99' OR cst_status = '98' OR cst_status = '90' OR cst_status = '51' OR cst_status = '52' )"]=NULL;
			$where_paid["( cst_status = '99' OR cst_status = '98' OR cst_status = '90' OR cst_status = '51' OR cst_status = '52' )"]=NULL;
		}
		else{
			$whereadd['cst_status'] = $status;
			$where_paid['cst_status'] = $status;
		}

		$result = $this->Patient_Model->select_consulting_info_join($whereadd, 'c.team_code as team_code, count(team_code) as total');
		foreach ( $result as $i => $row ) {
			$sales[$row['team_code']]['cst_status_'.$status] = $row['total'];
		}

		$res = $this->Patient_Model->select_consulting_info_pay_join($where_paid,
			'team_code,
			sum(ppp.amount_refund) as amount_refund,
			sum(ppp.amount_paid) as amount_paid,
			sum(ppp.amount_paid_cash) as amount_paid_cash,
			sum(ppp.amount_paid_card) as amount_paid_card,
			sum(ppp.amount_paid_bank) as amount_paid_bank',
			'team_code, cst_status', 'pp.date_project ASC');
		foreach ( $res as $i => $v ) {
			if($key == 'product_no') continue;
			//$sales[$row['team_code']][$i] = $v;
		}


		//echo $this->db->last_query()."<Br /><br />";



		//pre($res);

		return $sales;
	}


	private function get_doctor_cst_status($sales, $where, $status) {
		$whereadd = array();
		$whereadd = $where;
		//if($status == ''){
		//	$whereadd["( status_code = '07-001' OR status_code = '98' OR cst_status = '90' OR cst_status = '52' )"]=NULL;
			//'07-001'=>'취소',		//'07-015'=>'재수술',		//'07-014'=>'귀가(수술완료)',		//'52'=>'내원상담',
		//}
		//else{
		//	$whereadd['status_code'] = '07-'.$status;
		//}
		if($status == ''){
			$whereadd['(visit="초진" or visit="재진" or type_code="06-010" or type_code="06-011")'] = NULL;
		}
		else if($status == '024'){
			$whereadd['(status_code="07-024" or status_code="07-014")'] = NULL;
		}
		else{
			$whereadd['status_code'] = '07-'.$status;
		}

		$result = $this->Patient_Model->select_consulting_info_doctor_join($whereadd, 'pa.doctor_id as doctor_id, count(pa.doctor_id) as total');
		foreach ( $result as $i => $row ) {
			$sales[$row['doctor_id']]['status_code_'.$status] = $row['total'];
		}


		return $sales;
	}


	// 고객(op매출)내역
	public function consulting_team_detail_list(){
		$datum = array (
			'cfg'=>array(
				'team'=>$this->User_model->get_team_list( '90' )				// 팀목록
			),
		);

		$this->_render('consulting_team_detail_list', $datum, 'inc');
	}

	// 수술예정고객내역
	public function consulting_team_detail_unpaid_list(){
		$datum = array (
			'cfg'=>array(
				'team'=>$this->User_model->get_team_list( '90' )				// 팀목록
			),
		);

		$this->_render('consulting_team_detail_unpaid_list', $datum, 'inc');
	}

	public function consulting_team_detail_lists_paging(){


		$this->load->library('Patient_lib');
		$this->load->library('common_lib');
		$this->load->model('Patient_Model');

		//$p = $this->param;


		$where_offset = array(
			'ci.biz_id'=>$this->session->userdata('ss_biz_id'),
			'pp.is_delete'=>'N'
		);

		parse_str($this->input->post('search'), $assoc);
		$where = array();

		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'srch_start_date':
					$where['date_project >=']="{$v}";
					$srch_start_date = "{$v}";
				break;
				case 'srch_end_date':
					$where['date_project <=']="{$v}";
					$srch_end_date = "{$v}";
				break;
				case 'team_code':
					$where['ci.team_code']="{$v}";
					$team_code = "{$v}";
				break;
				case 'page':
					$page = "{$v}"?"{$v}":1;
				break;
				case 'srch_path':
					$path = "{$v}"?"{$v}":"";
				break;
				case 'srch_media':
					$media = "{$v}"?"{$v}":"";
				break;
				case 'unpaid':							// 미수금
					$unpaid = "{$v}"?"{$v}":"";
				break;
				case 'limit':							// 페이징 갯수
					$limit = "{$v}";
				break;

			}
		}

		// 상담팀은 자기 팀 데이터만
		if($this->session->userdata( 'ss_dept_code' ) == '90'){
			$where['ci.team_code']=$this->session->userdata( 'ss_team_code' );
			$team_code = $this->session->userdata( 'ss_team_code' );
		}
		// 상담팀은 자기 팀 데이터만

		//$paid_field = 'ppp.amount_paid';
		if($unpaid=="Y"){
			$where['pp.amount_unpaid>0']=NULL;
			$unpaid_where = 'pp.amount_unpaid>0';
			//$paid_field = 'pp.amount_unpaid';
		}

		//$limit = ($p['limit'])?$p['limit']:PER_PAGE;
		$limit = ($limit)?$limit:PER_PAGE;
		$offset = ($page-1)*$limit;

		$doctor = $this->common_lib->get_cfg('doctor');						// 담당의
		$team_list = $this->User_model->get_team_list( '90' );				// 팀목록
		$all_path_list = $this->config->item( 'all_path' );					// 유입경로

		// op수납, 환불, 예치금, 현금, 카드, 계좌
		$paid_list = array();

		$where_paid = array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, 'ci.cst_status!=0'=>NULL, $unpaid_where=>NULL);
		$res = $this->Patient_Model->select_consulting_info_join_project($where_paid,
			'group_concat(distinct pp.no order by pp.no) project_no,
			sum(ppp.amount_refund) as amount_refund,
			sum(ppp.amount_paid) as amount_paid,
			sum(ppp.amount_paid_cash) as amount_paid_cash,
			sum(ppp.amount_paid_card) as amount_paid_card,
			sum(ppp.amount_paid_bank) as amount_paid_bank',
			'pp.patient_no, pp.date_project', 'pp.date_project ASC');			// group_by, order_by
//			(select sum(amount_paid) from patient_pay pppp where pppp.project_no in (group_concat(distinct pp.no order by pp.no)) and pppp.is_delete="N" and pppp.sales_type="예치") as amount_deposit',

		foreach($res['list'] as $row) {
			//$paid_list[$row['project_no']]['project_no'] = $row['project_no'];
			$paid_list[$row['project_no']]['amount_refund'] = $row['amount_refund'];		// 환불
			$paid_list[$row['project_no']]['amount_paid'] = $row['amount_paid'];			// 수납
			//$paid_list[$row['project_no']]['amount_unpaid'] = $row['amount_unpaid'];			// 미수금
			//$paid_list[$row['project_no']]['amount_deposit'] = $row['amount_deposit'];			// 예치

			$where_deposit = array('ci.cst_status!=0'=>NULL, 'ppp.sales_type="예치"'=>NULL, 'pp.no in ('.$row['project_no'].')'=>NULL);
			$res_deposit = $this->Patient_Model->select_consulting_info_join_project($where_deposit,
				'sum(ppp.amount_paid) as amount_deposit',
				'', '');
			foreach($res_deposit['list'] as $row_deposit) {
				$paid_list[$row['project_no']]['amount_deposit'] = $row_deposit['amount_deposit'];			// 예치
			}



			$paid_list[$row['project_no']]['amount_paid_cash'] = $row['amount_paid_cash'];	// 현금
			$paid_list[$row['project_no']]['amount_paid_card'] = $row['amount_paid_card'];	// 카드
			$paid_list[$row['project_no']]['amount_paid_bank'] = $row['amount_paid_bank'];	// 계좌
		}

		$res = $this->Patient_Model->select_consulting_info_join_unpaid_project($where_paid,
			'group_concat(distinct pp.no order by pp.no) project_no,
			sum(pp.amount_unpaid) as amount_unpaid ',
			'pp.patient_no, pp.date_project', 'pp.date_project ASC');			// group_by, order_by

		foreach($res['list'] as $row) {
			//$paid_list[$row['project_no']]['project_no'] = $row['project_no'];
			$paid_list[$row['project_no']]['amount_unpaid'] = $row['amount_unpaid'];			// 미수금
		}


		// 고객 목록
		$field = 'ci.team_code, ci.patient_no , ci.name, ci.path, ci.media, group_concat(distinct pp.no order by pp.no) project_no, pp.doctor_id, pp.date_project, group_concat(distinct pp.treat_cost_no) as treat_cost_no, sum(pp.amount_basic) amount_basic';
		$rs = $this->Patient_Model->select_consulting_team_detail_paging($where, $offset, $limit, $where_offset, $field);

		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {

				if(date( "Y-m-d", strtotime('-1 day')) >= $row['date_project']){
					$list[$row['project_no']]['bgcolor'] = '#ccffcc';
				}
				else{
					$list[$row['project_no']]['bgcolor'] = '';
				}

				$list[$row['project_no']]['idx'] = $idx;

				$list[$row['project_no']]['patient_no'] = $row['patient_no'];

				$list[$row['project_no']]['team_code'] = $team_list[$row['team_code']];

				$list[$row['project_no']]['name'] = $row['name'];
				$list[$row['project_no']]['path'] = $row['path'];
				$list[$row['project_no']]['media'] = $row['media'];
				$list[$row['project_no']]['project_no'] = $row['project_no'];
				$list[$row['project_no']]['doctor_id'] = $row['doctor_id'];
				$list[$row['project_no']]['date_project'] = $row['date_project'];
				$list[$row['project_no']]['treat_cost_no'] = $row['treat_cost_no'];
				$list[$row['project_no']]['amount_basic'] = $row['amount_basic'];					// 매출총액

				$list[$row['project_no']]['team_name'] = $team_list[$row['team_code']];
				$list[$row['project_no']]['doctor_name'] = $doctor[$row['doctor_id']];
				$list[$row['project_no']]['path_name'] = $all_path_list[$row['path']];

				$treat_str = '';
				//foreach($row['treat_cost_no'] as $treat){
				$treat = explode(',', $row['treat_cost_no']);

				for($i=0; $i<count($treat); $i++){

					if($i > 0){
						if($treat[$i].trim()!=0){
							$treat_str .= '<br>';
						}
					}
					$treat_str .= $this->patient_lib->treat_nav($treat[$i], ' &gt ').trim();
				}


				$list[$row['project_no']]['treat_nav'] = $treat_str;

				$list[$row['project_no']]['amount_refund'] = $paid_list[$row['project_no']]['amount_refund'];
				$list[$row['project_no']]['amount_paid'] = $paid_list[$row['project_no']]['amount_paid'];
				$list[$row['project_no']]['amount_unpaid'] = $paid_list[$row['project_no']]['amount_unpaid'];			// 미수금
				$list[$row['project_no']]['amount_deposit'] = $paid_list[$row['project_no']]['amount_deposit'];

				$list[$row['project_no']]['amount_paid_cash'] = $paid_list[$row['project_no']]['amount_paid_cash'];
				$list[$row['project_no']]['amount_paid_card'] = $paid_list[$row['project_no']]['amount_paid_card'];
				$list[$row['project_no']]['amount_paid_bank'] = $paid_list[$row['project_no']]['amount_paid_bank'];

				$idx--;

			}
		}
		rsort($list);


		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();



		// op수납, 환불, 예치금, 현금, 카드, 계좌
		$paid_total_list = array();
		$team_code_where = '';
		if($team_code)		$team_code_where = 'team_code='.$team_code;
		$where_paid = array('date_project >='=>$srch_start_date, 'date_project <= '=>$srch_end_date, 'ci.cst_status!=0'=>NULL, $team_code_where=>NULL, $unpaid_where=>NULL);
		$res = $this->Patient_Model->select_consulting_info_join_project($where_paid,
			'group_concat(distinct pp.no order by pp.no) project_no,
			sum(ppp.amount_refund) as amount_refund,
			sum(ppp.amount_paid) as amount_paid,
			sum(ppp.amount_paid_cash) as amount_paid_cash,
			sum(ppp.amount_paid_card) as amount_paid_card,
			sum(ppp.amount_paid_bank) as amount_paid_bank',
			'pp.patient_no, pp.date_project', 'pp.date_project ASC');			// group_by, order_by
			//	(select sum(amount_paid) from patient_pay pppp where pppp.project_no in (group_concat(distinct pp.no order by pp.no)) and pppp.is_delete="N" and pppp.sales_type="예치") as amount_deposit',

		foreach($res['list'] as $row) {
			//$paid_list[$row['project_no']]['project_no'] = $row['project_no'];
			$paid_total_list['total']['amount_refund'] += $row['amount_refund'];		// 환불
			$paid_total_list['total']['amount_paid'] += $row['amount_paid'];			// 수납
			//$paid_total_list['total']['amount_deposit'] += $row['amount_deposit'];			// 예치

			$where_deposit = array('ci.cst_status!=0'=>NULL, 'ppp.sales_type="예치"'=>NULL, 'pp.no in ('.$row['project_no'].')'=>NULL);
			$res_deposit = $this->Patient_Model->select_consulting_info_join_project($where_deposit,
				'sum(ppp.amount_paid) as amount_deposit',
				'', '');
			foreach($res_deposit['list'] as $row_deposit) {
				$paid_total_list['total']['amount_deposit'] += $row_deposit['amount_deposit'];			// 예치
			}


			$paid_total_list['total']['amount_paid_cash'] += $row['amount_paid_cash'];	// 현금
			$paid_total_list['total']['amount_paid_card'] += $row['amount_paid_card'];	// 카드
			$paid_total_list['total']['amount_paid_bank'] += $row['amount_paid_bank'];	// 계좌
		}

		$res = $this->Patient_Model->select_consulting_info_join_unpaid_project($where_paid,
			'group_concat(distinct pp.no order by pp.no) project_no,
			sum(pp.amount_unpaid) as amount_unpaid, sum(pp.amount_basic) as amount_basic ',
			'', '');			// group_by, order_by

		foreach($res['list'] as $row) {
			//$paid_list[$row['project_no']]['project_no'] = $row['project_no'];
			$paid_total_list['total']['amount_unpaid'] = $row['amount_unpaid'];			// 미수금
			$paid_total_list['total']['amount_basic'] = $row['amount_basic'];			// 미수금
		}


		$return = array(
			//'sum'=>$sum,
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging,
			'paid_total_list'=>$paid_total_list,
				'paid_list'=>$paid_list,
				//'paid_deposit'=>$paid_deposit
		);


		/*if($param['mode']=='excel') {
			$html = $this->_render('path_inner', $datum, 'inc', true);
			$this->_excel('유입경로별분배현황_',$html);
		}
		else $this->_render('path_inner', $datum, 'inc');

		*/

		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}


	}
	// 20170208 kruddo : DB실적->상담팀실적으로 수정


	public function pay() {
		$srch_date = get_search_type_date();
		$data = array (
			'cfg'=>array(
				'path'=>$all_path_list,
				'date'=>$srch_date,
				'tab'=>array(
					'team'=>'상담실장별매출',
					'media'=>'미디어별매출',
					'dr'=>'원장별매출',
					'unpaid'=>'미수목록'
				)
			)
		);
		$this->_render('pay', $data);

	}

	/**
	 * 팀별 매출액
	 */
	public function pay_team() {
		$p = $this->input->post(NULL, true);

		//OP매출, 수납액, 미수금, 환불액 - 수술일기준
		$sql = "SELECT
					consulting_sales_yn,
					manager_id,
					SUM(amount_basic+amount_addition) AS basic,
					SUM(paid_total) AS paid,
					SUM(amount_unpaid) AS unpaid,
					SUM(amount_refund) AS refund
				FROM patient_project
				WHERE
					date_project>='".$p['date_s']." 00:00:00' AND
					date_project<='".$p['date_e']." 23:59:59' AND
					is_delete = 'N'
				GROUP BY  manager_id, consulting_sales_yn";
		$rs = $this->adodb->getArray($sql);

		foreach($rs as $row) {
			if($row['consulting_sales_yn'] == 'Y' && $row['manager_id']) {
				$nd[$row['manager_id']]['basic'] = $row['basic'];
				$nd[$row['manager_id']]['paid'] = $row['paid'];
				$nd[$row['manager_id']]['unpaid'] = $row['unpaid'];
				$nd[$row['manager_id']]['refund'] = $row['refund'];
			}
			else {
				$nd['etc']['basic'] += $row['basic'];
				$nd['etc']['paid'] += $row['paid'];
				$nd['etc']['unpaid'] += $row['unpaid'];
				$nd['etc']['refund'] += $row['refund'];
			}

			//echo $row['manager_team_code'].'('.$row['consulting_sales_yn'].')='.$row['basic']."<br />";

			$total['basic'] += $row['basic'];
			$total['paid'] += $row['paid'];
			$total['unpaid'] += $row['unpaid'];
			$total['refund'] += $row['refund'];
		}


		//$team_list = $this->User_model->get_team_list('90');
		$manager_list = $this->common_lib->get_user('90');

		$d = array();
		foreach($manager_list as $uid=>$uname) {
			$d[$uid] = array(
				'name'=>$uname,
				'sales'=>$nd[$uid]
			);
		}

		$d['etc']= array(
			'name'=>'피부/기타',
			'sales'=>$nd['etc']
		);


		$assign = array(
			'search'=>$p,
			'team'=>$d,
			'total'=>$total
		);

		//pre($assign);


		$this->_render('pay.team', $assign, 'inc');
	}

	//미디어별매출
	public function pay_media() {
		$p = $this->input->post(NULL, true);
		$sql = "SELECT
					p.media,
					SUM(pp.amount_basic+pp.amount_addition) AS basic,
					SUM(pp.paid_total) AS paid,
					SUM(pp.amount_unpaid) AS unpaid,
					SUM(pp.amount_refund) AS refund
				FROM patient_project AS pp LEFT JOIN patient AS p  ON(pp.patient_no = p.no)
				WHERE
					pp.date_project>='".$p['date_s']." 00:00:00' AND
					pp.date_project<='".$p['date_e']." 23:59:59' AND
					pp.is_delete = 'N'
				GROUP BY p.media";
		$rs = $this->adodb->getArray($sql);


		foreach($rs as $row) {
			if($row['media']) {
				$nd[$row['media']]['basic'] = $row['basic'];
				$nd[$row['media']]['paid'] = $row['paid'];
				$nd[$row['media']]['unpaid'] = $row['unpaid'];
				$nd[$row['media']]['refund'] = $row['refund'];
			}
			else {
				$nd['etc']['basic'] += $row['basic'];
				$nd['etc']['paid'] += $row['paid'];
				$nd['etc']['unpaid'] += $row['unpaid'];
				$nd['etc']['refund'] += $row['refund'];
			}


			//echo $row['manager_team_code'].'('.$row['consulting_sales_yn'].')='.$row['basic']."<br />";

			$total['basic'] += $row['basic'];
			$total['paid'] += $row['paid'];
			$total['unpaid'] += $row['unpaid'];
			$total['refund'] += $row['refund'];
		}

	//	pre($nd);exit;


		$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'depth'=>1, 'use_flag'=>'Y')); //미디어 그룹
		foreach($media_group as $key=>$value) {
			$items = $this->common_lib->get_code_item( '04' , $key, 'all');
			$group_total = array();
			foreach($items as $k=>$v) {
				$sales = $nd[$v['title']];
				$items[$k]['sales']=$sales;

				foreach($sales as $sk=>$sv) {
					$group_total[$sk]+=$sv;
				}
			}

			//pre($group_total);

			$items['total'] = array(
				'title'=>'합계',
				'sales'=>$group_total
			);

			$list[$key] = array(
				'name'=>$value,
				'children'=>$items,
				'count'=>count($items)
			);
		}

		$list['etc'] = array(
			'name'=>'기타',
			'children'=>array(
				'etc'=>array(
					'title'=>'미디어코드없음',
					'sales'=>$nd['etc']
				)
			),
			'count'=>1
		);

		$assign = array(
			'search'=>$p,
			'list'=>$list,
			'total'=>$total
		);

		//pre($list['etc']);

		$this->_render('pay.media', $assign, 'inc');
	}

	public function pay_team_detail() {
		$p = $this->input->post(NULL, true);
		$this->load->library('patient_lib');


		$cfg_team = $this->common_lib->get_cfg('manager_team');
		if($p['team_no'] == 'etc') {
			$where = " AND consulting_sales_yn IS NULL";
			$team_name = '피부/기타';
		}
		else {
			$where = " AND pp.consulting_sales_yn='Y' AND p.manager_team_code='".$p['team_no']."'";
			$team_name = $cfg_team[$p['team_no']];
		}


		switch($p['pay_field']) {
			case 'basic':
				$field_name = 'OP매출액';
				$field =", amount_basic AS amount";
			break;
			case 'paid':
				$field_name = '수납액';
				$field = ", paid_total AS amount";
				$where .= " AND paid_total > 0";
			break;
			case 'unpaid':
				$field_name = '미수금';
				$field = ", amount_unpaid AS amount";
				$where .= " AND amount_unpaid != 0";
			break;
			case 'refund':
				$field_name = '환불액';
				$field = ", amount_refund AS amount";
				$where .= " AND amount_refund > 0";
			break;
		}

		$sql = "SELECT
					p.name, pp.type, pp.treat_cost_no, pp.date_project, pp.manager_team_code
					{$field}
				FROM patient_project AS pp LEFT JOIN patient AS p ON(p.no=pp.patient_no)
				WHERE
					date_project>='".$p['date_s']." 00:00:00' AND
					date_project<='".$p['date_e']." 23:59:59' AND
					pp.is_delete='N' {$where}
				ORDER BY date_project ASC";
		//echo $sql;
		$rs = $this->adodb->getArray($sql);
		$total_amount = $total_count = 0;


		foreach($rs as $row) {
			$row['treat_nav'] = $this->patient_lib->treat_nav($row['treat_cost_no'], ' &gt ');
			$row['team_name'] = $cfg_team[$row['manager_team_code']];
			$list[] = $row;
			$total_amount+=$row['amount'];
			$total_count++;
		}



		$assign = array(
			'cfg'=>array(
				'field_name'=>$field_name,
				'team_name'=>$team_name
			),
			'field'=>$field_name,
			'title'=>$title,
			'list'=>$list,
			'total'=>array(
				'amount'=>$total_amount,
				'count'=>$total_count
			)
		);


		$this->_render('pay.team.detail', $assign, 'inc');
	}

	public function pay_detail() {
		$p = $this->input->post(NULL, true);
		$this->load->library('patient_lib');

		switch($p['tab']) {
			case 'media' :
				$media_group = $this->common_lib->get_code(array('group_code'=>'04', 'use_flag'=>'Y')); //미디어 그룹
				//pre($media_group);
				if($p['media'] == 'etc') {
					$where = " AND (p.media IS NULL OR p.media='')";
					$team_name = '기타';
				}
				else {

					$where = " AND p.media='".$media_group[$p['media']]."'";
					$team_name = $media_group[$p['media']];
				}
				break;

			default:
				$cfg_manager = $this->common_lib->get_user('90');

				if($p['manager_id'] == 'etc') {
					$where = " AND consulting_sales_yn IS NULL";
					$team_name = '피부/기타';
				}
				else {
					$where = " AND pp.consulting_sales_yn='Y' AND pp.manager_id='".$p['manager_id']."'";
					$team_name = $cfg_manager[$p['manager_id']];
				}
				break;
			}


		switch($p['pay_field']) {
			case 'basic':
				$field_name = 'OP매출액';
				$field =", amount_basic AS amount";
			break;
			case 'paid':
				$field_name = '수납액';
				$field = ", paid_total AS amount";
				$where .= " AND paid_total > 0";
			break;
			case 'unpaid':
				$field_name = '미수금';
				$field = ", amount_unpaid AS amount";
				$where .= " AND amount_unpaid != 0";
			break;
			case 'refund':
				$field_name = '환불액';
				$field = ", amount_refund AS amount";
				$where .= " AND amount_refund > 0";
			break;
		}

		$sql = "SELECT
					p.name, pp.patient_no, pp.type, pp.treat_cost_no, pp.date_project, pp.manager_team_code
					{$field}
				FROM patient_project AS pp LEFT JOIN patient AS p ON(p.no=pp.patient_no)
				WHERE
					date_project>='".$p['date_s']." 00:00:00' AND
					date_project<='".$p['date_e']." 23:59:59' AND
					pp.is_delete='N' {$where}
				ORDER BY date_project ASC";
		$rs = $this->adodb->getArray($sql);
		// echo $sql;
		$total_amount = $total_count = 0;


		foreach($rs as $row) {
			$row['treat_nav'] = $this->patient_lib->treat_nav($row['treat_cost_no'], ' &gt ');
			$row['team_name'] = $cfg_team[$row['manager_team_code']];
			$list[] = $row;
			$total_amount+=$row['amount'];
			$total_count++;
		}



		$assign = array(
			'cfg'=>array(
				'field_name'=>$field_name,
				'manager_name'=>$team_name
			),
			'field'=>$field_name,
			'title'=>$title,
			'list'=>$list,
			'total'=>array(
				'amount'=>$total_amount,
				'count'=>$total_count
			)
		);


		$this->_render('pay.team.detail', $assign, 'inc');
	}



	public function pay_manager_detail() {
		$p = $this->input->post(NULL, true);
		$this->load->library('patient_lib');


		$cfg_manager = $this->common_lib->get_user('90');

		if($p['manager_id'] == 'etc') {
			$where = " AND consulting_sales_yn IS NULL";
			$manager_name = '피부/기타';
		}
		else {
			$where = " AND pp.consulting_sales_yn='Y' AND pp.manager_id='".$p['manager_id']."'";
			$manager_name = $cfg_manager[$p['manager_id']];
		}


		switch($p['pay_field']) {
			case 'basic':
				$field_name = 'OP매출액';
				$field =", (amount_basic+amount_addition) AS amount";
			break;
			case 'paid':
				$field_name = '수납액';
				$field = ", paid_total AS amount";
				$where .= " AND paid_total > 0";
			break;
			case 'unpaid':
				$field_name = '미수금';
				$field = ", amount_unpaid AS amount";
				$where .= " AND amount_unpaid != 0";
			break;
			case 'refund':
				$field_name = '환불액';
				$field = ", amount_refund AS amount";
				$where .= " AND amount_refund > 0";
			break;
		}

		$sql = "SELECT
					p.name, pp.patient_no, pp.type, pp.treat_cost_no, pp.date_project, pp.manager_team_code
					{$field}
				FROM patient_project AS pp LEFT JOIN patient AS p ON(p.no=pp.patient_no)
				WHERE
					date_project>='".$p['date_s']." 00:00:00' AND
					date_project<='".$p['date_e']." 23:59:59' AND
					pp.is_delete='N' {$where}
				ORDER BY date_project ASC";
		//echo $sql;
		$rs = $this->adodb->getArray($sql);
		$total_amount = $total_count = 0;


		foreach($rs as $row) {
			$row['treat_nav'] = $this->patient_lib->treat_nav($row['treat_cost_no'], ' &gt ');
			$row['manager_name'] = $cfg_manager[$row['manager_id']];
			$list[] = $row;
			$total_amount+=$row['amount'];
			$total_count++;
		}



		$assign = array(
			'cfg'=>array(
				'field_name'=>$field_name,
				'manager_name'=>$manager_name
			),
			'field'=>$field_name,
			'title'=>$title,
			'list'=>$list,
			'total'=>array(
				'amount'=>$total_amount,
				'count'=>$total_count
			)
		);


		$this->_render('pay.team.detail', $assign, 'inc');
	}


	public function pay_dr() {
		echo '개발중입니다..';
	}

	public function pay_unpaid() {
		echo '개발중입니다..';
	}
}
