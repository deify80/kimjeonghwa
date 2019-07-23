<?php
/**
 * 수납관리
 * 작성 : 2015.05.20
 * @author 이혜진
 */

if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Pay extends CI_Controller {

	var $dataset;
	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->yield = TRUE;

		$this->load->model( array (
			'Patient_Model'
			// 'Attendance_model'
		) );
	}

	/**
	 * 수납내역
	 * @return [type] [description]
	 */
	public function paid() {
		$sum_field = "SUM(amount_paid) AS paid, SUM(amount_paid_cash) AS paid_cash, SUM(amount_paid_card) AS paid_card, SUM(amount_paid_bank) AS paid_bank, SUM(amount_paid_cash) AS paid_refund, SUM(IF(receipt_type!='미발행', amount_paid, 0)) AS receipt, SUM(amount_refund) AS amount_refund";
		$sum = $this->Patient_Model->select_widget_row('patient_pay',array('is_delete'=>'N'),$sum_field);

		$srch_date = get_search_type_date();
		$doctor = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'), $this->session->userdata('ss_biz_id'));//의사
		$team = $this->common_lib->get_team( '90' ); //팀

		$datum = array(
			'cfg'=>$this->common_lib->get_cfg(array('date','doctor','team')),
			'sum'=>$sum,
			'auth'=>array(
				'paid_excel'=>$this->common_lib->check_auth_group('paid_excel'),
				'paid_all'=>$this->common_lib->check_auth_group('paid_all')
			)
		);
		$this->_render('paid', $datum );
	}

	function paid_list_paging($p) {
		
		
		if($_SERVER['REMOTE_ADDR'] == '218.234.32.14') {
//			echo '$auth_paid_all : '.$auth_paid_all;
//			pre($p);
		}
		$p = (!$p)?$this->input->post(NULL,FALSE):$p;


		$page = ($p['page'])?$p['page']:1;
		$limit = $p['limit']?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		//parse_str($this->input->post('search'), $assoc);
		$search = $p['search']; //$this->input->post('search',FALSE);
		parse_str($search, $assoc);

		$auth_paid_all = $this->common_lib->check_auth_group('paid_all'); //전체확인권한여부 add 2018-11-26
		$where_offset = array('p.biz_id'=>$this->session->userdata('ss_biz_id'), 'pp.is_delete'=>'N','p.is_delete'=>'N');
		if(!$auth_paid_all ) {
			$where_offset['pp.date_insert >= '] = date('Y-m-d 00:00:00'); 
		}

		//권한
		if(!$this->common_lib->check_auth_group('ex_paylist')) {
			$where_offset['pp.manager_team_code'] = $this->session->userdata('ss_team_code');
		}

		$where = array();
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block', 'limit', 'page'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["( p.name LIKE '%{$v}%' OR p.mobile LIKE '%{$v}%')"]=NULL;
				break;
				case 'date_s':
					$where['pp.date_paid >=']="{$v}";
				break;
				case 'date_e':
					$where['pp.date_paid <=']="{$v}";
				break;
				case 'manager_id':
					if($assoc['manager_team_code']!='all') {
						$where['pp.'.$k] = $v;
					}
				break;
				default :
					$where['pp.'.$k] = $v;
					break;
			}
		}

		//매출합산
		$sum_field = "SUM(pp.amount_paid-pp.amount_refund) AS paid, SUM(pp.amount_paid_cash-pp.amount_refund_cash) AS paid_cash, SUM(pp.amount_paid_card-pp.amount_refund_card) AS paid_card, SUM(pp.amount_paid_bank-pp.amount_refund_bank) AS paid_bank, SUM(pp.amount_refund) AS refund, SUM(IF(receipt_type!='미발행' AND pay_type='paid', amount_paid_cash_o+amount_paid_bank, 0)) AS receipt, SUM(IF(receipt_type='차감', amount_refund_cash_o+amount_refund_bank, 0)) AS receipt_refund";
		$sum = $this->Patient_Model->select_pay_row(array_merge($where, $where_offset),$sum_field);
		//echo $this->db->last_query();
		$sum['receipt'] = $sum['receipt']-$sum['receipt_refund'];
	//	pre($sum);

		$doctor = $this->common_lib->get_doctor($this->session->userdata('ss_hst_code'), $this->session->userdata('ss_biz_id'));//의사
		$manager = $this->common_lib->get_user();


		$rs = $this->Patient_Model->select_pay_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['doctor_name'] = $doctor[$row['doctor_id']];
				$row['manager_name'] = $manager[$row['manager_id']];
				$row['date_insert'] = substr($row['date_insert'],0,10);

				$row['acceptor_name'] = $manager[$row['acceptor_id']];
				$row['mobile'] = $this->common_lib->manufacture_mobile($row['mobile'], $row['manager_team_code']);

				$row['idx'] = $idx;
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
			'sum'=>$sum,
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);

		if($this->output=='excel') {
			return $return;
		}
		else {
			if($rs['count']['search']>0) {
				return_json(true, '', $return);
			}
			else {
				return_json(false, '', $return);
			}
		}
	}

	public function project() {

		$datum = array(
			'cfg'=>$this->common_lib->get_cfg(array('date', 'team')),
			'auth'=>array(
				'paid_all'=>$this->common_lib->check_auth_group('paid_all')
			)
		);
		// pre($datum);
		$this->_render('project', $datum );
	}


	function project_list_paging() {
		$p = $this->param;
		$page = $p['page'];
		$limit = ($p['limit'])?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);
		$where = array();
		$where_offset = array(
			'p.biz_id'=>$this->session->userdata('ss_biz_id'),
			'p.is_delete'=>'N',
			'pp.is_delete'=>'N'
		);
		
		$auth_paid_all = $this->common_lib->check_auth_group('paid_all'); //전체확인권한여부 add 2018-11-26
		$where_offset = array('p.biz_id'=>$this->session->userdata('ss_biz_id'), 'pp.is_delete'=>'N','p.is_delete'=>'N');
		if(!$auth_paid_all ) {
			$where_offset['pp.date_insert >= '] = date('Y-m-d 00:00:00'); 
		}


		//권한
		if(!$this->common_lib->check_auth_group('ex_paylist')) {
			$where_offset['pp.manager_team_code'] = $this->session->userdata('ss_team_code');
		}


		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'word':
					$where["( p.name LIKE '%{$v}%' OR p.mobile LIKE '%{$v}%')"]=NULL;
				break;
				case 'date_s':
					$where['pp.date_project >=']="{$v}";
				break;
				case 'date_e':
					$where['pp.date_project <=']="{$v}";
				break;
				case 'pay_status':
					if($v == 'paid') {
						$where['pp.amount_unpaid < ']="1";
					}
					else {
						$where['pp.amount_unpaid > ']="0";
					}
				break;
				default :
					$where['pp.'.$k] = $v;
					break;
			}
		}

		// pre($where);

		//매출합산
		$sum_field = "SUM(pp.amount_basic) AS amount_basic, SUM(pp.amount_addition) AS amount_addition, SUM(pp.paid_total) AS paid_total, SUM(pp.discount_amount) AS discount_amount, SUM(pp.amount_unpaid) AS amount_unpaid, SUM(pp.amount_refund) AS amount_refund, SUM(pp.amount_material) AS amount_material";
		//l, SUM(pp.amount_paid_cash) AS paid_cash, SUM(pp.amount_paid_card) AS paid_card, SUM(pp.amount_paid_bank) AS paid_bank, SUM(pp.amount_refund) AS amount_refund, SUM(IF(receipt_type!='미발행', amount_paid_cash+amount_paid_bank, 0)) AS receipt";
		$sum = $this->Patient_Model->select_project_row(array_merge($where, $where_offset),$sum_field);
		$sum['amount_total'] = $sum['amount_basic'] + $sum['amount_addition'];
		// pre($sum);

		$doctor = $this->common_lib->get_cfg('doctor');
		$manager = $this->common_lib->get_user();
		$treat_region = $this->common_lib->get_cfg('treat_region');
		$treat_item = $this->common_lib->get_cfg('treat_item');
		$op_type = $this->common_lib->get_cfg('op_type');

		$this->load->library('patient_lib');

		$rs = $this->Patient_Model->select_project_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['doctor_name'] = $doctor[$row['doctor_id']];
				$row['manager_name'] = $manager[$row['manager_id']];
				$row['date_insert'] = substr($row['date_insert'],0,10);

				$row['op_type_txt'] = $op_type[$row['op_type']];
				$row['treat_nav'] = $this->patient_lib->treat_nav($row['treat_cost_no'], ' &gt ');
				//나이

				if($row['discount_amount']>0) $row['contrast']='-';
				else if($row['discount_amount']<0) $row['contrast']='+';
				else $row['contrast']='';
				//$row['contrast'] = ($row['discount_amount']>0)?'-':'+';
				$row['contrast_rate'] = ($row['discount_rate']==100)?$row['contrast'].'100':$row['contrast'].abs($row['discount_rate']);
				$row['amount_total'] = $row['amount_basic']+$row['amount_addition'];
				$row['mobile'] = $this->common_lib->manufacture_mobile($row['mobile'], $row['manager_team_code']);
				$row['idx'] = $idx;
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
			'sum'=>$sum,
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


	/**
	 * 원가관리
	 * @return [type] [description]
	 */
	public function cost() {
		$datum = array(
			'cfg'=>$this->common_lib->get_cfg(array('doctor', 'date'))
		);

		$this->_render('cost', $datum);
	}

	public function cost_lists_paging() {
		$p = $this->param;
		$page = $p['page'];
		$limit = ($p['limit'])?$p['limit']:PER_PAGE;
		$offset = ($page-1)*$limit;

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);
		$where = array();
		$where_offset = array(
			'p.biz_id'=>$this->session->userdata('ss_biz_id'),
			'pp.is_delete'=>'N'
		);
		// $where_offset['amount_unpaid >=']='1';
		$where = $where_offset;
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','block'))) continue;
			if($v == 'all' || (!$v && $v!=='0')) continue;

			switch($k) {
				case 'date_s':
					$where['pp.date_project >=']="{$v}";
				break;
				case 'date_e':
					$where['pp.date_project <=']="{$v}";
				break;
				default :
					$where['pp.'.$k] = $v;
					break;
			}
		}

		//매출합산
		$sum_field = "SUM(pp.amount_basic) AS amount_basic, SUM(pp.amount_addition) AS amount_addition, SUM(pp.amount_material) AS amount_material";
		$sum = $this->Patient_Model->select_project_row($where,$sum_field);
		$sum['amount_total'] = $sum['amount_basic'] + $sum['amount_addition'];

		//의료진(의사,간호사) 인건비
		$this->load->model('User_Model');
		$medical = $this->User_Model->select_user_all(array('occupy_code'=>array('03-001','03-002')), 'user_id, income, occupy_code'); //직군 정보로 검색

		foreach($medical as $row) {
			if(!$row['income']) continue;
			$field = ($row['occupy_code'] == '03-001')?'pp.doctor_id':'pp.nurse_id';
			$where_medical = array_merge($where, array("{$field}" => array($row['user_id'])));
			$cnt = $this->Patient_Model->select_project_row($where_medical, "SUM(1) AS cnt");
			// pre($row);
			// pre($cnt);
			if($row['occupy_code'] == '03-001') {
				$sum['amount_doctor']+=($row['income']/12/25)*$cnt['cnt'];
			}
			else {
				$sum['amount_nurse']+=ceil($row['income']/12/25)*$cnt['cnt'];
			}
		}

		$sum['amount_income'] = $sum['amount_total'] - ($sum['amount_material'] + $sum['amount_doctor'] + $sum['amount_nurse']);

		// pre($sum);



		$doctor = $this->common_lib->get_cfg('doctor');
		$manager = $this->common_lib->get_user();
		$treat_region = $this->common_lib->get_cfg('treat_region');
		$treat_item = $this->common_lib->get_cfg('treat_item');


		$rs = $this->Patient_Model->select_project_paging($where, $offset, $limit, $where_offset);
		if($rs['count']['search'] > 0) {

			$list = array();
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['doctor_name'] = $doctor[$row['doctor_id']];
				$row['manager_name'] = $manager[$row['manager_id']];
				$row['date_insert'] = substr($row['date_insert'],0,10);

				// $row['treat_info'] = ($treat_region[$row['treat_region_code']])?$treat_region[$row['treat_region_code']].' > '.$treat_item[$row['treat_item_code']]:''; //진료정보
				$row['treat_region_name'] = $treat_region[$row['treat_region_code']];
				$row['treat_item_name'] = $treat_item[$row['treat_item_code']];

				//비용계산
				$row['amount_total'] = ($row['amount_basic']+$row['amount_addition']); //총매출
				$expense = array();
				$expense['doctor'] = ceil($medical[$row['doctor_id']]['income']/12/25); //의사인건비
				$expense['material'] = $row['amount_material']; //재료비
				$expense['nurse'] = 0;//간호사인건비

				$nurse_list  = array_filter(explode(',',$row['nurse_id']));
				foreach($nurse_list as $nurse) {
					$expense['nurse'] += ceil($medical[$nurse]['income']/12/25); //간호사인건비
				}
				$row['nurse_cnt'] = count($nurse_list);

				//손익
				$row['income'] = $row['amount_total'] -  array_sum($expense);
				$row['idx'] = $idx;
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
			'sum'=>$sum,
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);

		if($this->output=='excel') {
			return $return;
		}
		else {
			if($rs['count']['search']>0) {
				return_json(true, '', $return);
			}
			else {
				return_json(false, '', $return);
			}
		}
	}

	/**
	 * 엑셀다운로드
	 * @return [type] [description]
	 */
	public function paid_excel() {
		$p = $this->input->get(NULL, true);

		$this->param['limit'] = 100000;
		$this->param['page'] = 1;
		$this->output="excel";

		$search =  array(
			'date_s'=>$p['date_s'],
			'date_e'=>$p['date_e']
		);

		$p['search'] = http_build_query($search);

		$list = $this->paid_list_paging($p);
		//exit;




		$this->load->dbutil();
		// $this->load->helper('file');
		$this->load->helper('download');
		$filename = "수납내역_".date('Ymd').".xls";
		$body = $this->layout_lib->fetch_('/hospital/pay/paid_excel.html', $list);
		force_download($filename, $body);

	}

	private function _display($tmpl, $datum) {
		$this->load->view('/hospital/pay/'.$tmpl, $datum );
	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "hospital/pay/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}
}
