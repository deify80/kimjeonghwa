<?php
/**
 * 작성 : 2015.03.09
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Work_proc extends CI_Controller {



	public function __construct() {
		parent::__construct();
		$this->load->library( 'upload' );
		$this->load->model( array (
				'Work_model'
		) );

		$this->upload_path = './DATA/work/';
	}



	public function add_biz_log() {
		$input = null;
		$input['type'] = $this->input->post( 'type' );
		$input['cur_work'] = $this->input->post( 'cur_work' );
		$input['work_date'] = $this->input->post( 'work_date' );
		$input['status'] = $this->input->post( 'status' );
		$input['owner_command'] = $this->input->post( 'owner_command' );
		$input['next_work'] = $this->input->post( 'next_work' );
		$input['title'] = $this->input->post( 'title' );

		$this->Work_model->table = 'work_biz';

		if ($this->input->post( 'mode' ) == 'MODIFY') {

			$input['mod_date'] = TIME_YMDHIS;

			$this->Work_model->update( 'biz_seqno', $this->input->post( 'biz_seqno' ), $input );
			$biz_seqno = $this->input->post( 'biz_seqno' );
		} else {
			$input['reg_date'] = TIME_YMDHIS;
			$input['date_insert'] = NOW;
			$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
			$input['hst_code'] = $this->session->userdata( 'ss_hst_code' );
			$input['biz_id'] = $this->session->userdata( 'ss_biz_id' );

			$this->Work_model->insert( $input );
			$biz_seqno = $this->Work_model->get_insert_id();
		}

		$this->_add_biz_url( $biz_seqno );

		$json = null;
		$json['msg'] = $msg;
		$json['is_valid'] = $is_valid;
		$json['biz_seqno'] = $biz_seqno;

		echo json_encode( $json );
	}



	private function _add_biz_url($biz_seqno) {
		$this->Work_model->table = 'work_biz_url';

		$files = $_FILES;
		$count = count( $_FILES['userfile']['name'] );
		for($i = 0; $i < $count; $i ++) {

			$_FILES['userfile']['name'] = $files['userfile']['name'][$i];
			$_FILES['userfile']['type'] = $files['userfile']['type'][$i];
			$_FILES['userfile']['tmp_name'] = $files['userfile']['tmp_name'][$i];
			$_FILES['userfile']['error'] = $files['userfile']['error'][$i];
			$_FILES['userfile']['size'] = $files['userfile']['size'][$i];

			$this->upload->initialize( $this->_set_upload_options( 'biz', $biz_seqno, $i ) );
			$this->upload->do_upload();

			$input = null;
			$input['url_type'] = $_POST['url_type'][$i];
			$input['url'] = $_POST['url'][$i];
			$input['file_name'] = $files['userfile']['name'][$i];
			$input['new_name'] = $this->upload->file_name;
			$input['biz_seqno'] = $biz_seqno;

			$this->Work_model->insert( $input );
		}
	}



	private function _set_upload_options($type, $biz_seqno, $i) {
		$config = array ();
		$config['upload_path'] = $this->upload_path . $type;
		$config['allowed_types'] = '*';
		$config['file_name'] = $biz_seqno . DS . random_string();

		return $config;
	}



	public function add_biz_comment() {
		$input = null;
		$input['biz_seqno'] = $this->input->post( 'biz_seqno' );
		$input['comment'] = $this->input->post( 'comment' );
		$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
		$input['reg_date'] = TIME_YMDHIS;

		$this->Work_model->table = 'work_biz_comment';
		$is_success = $this->Work_model->insert( $input );

		$json = null;
		$json['is_success'] = $is_success;
		$json['reg_date'] = set_long_date_format( '-', $input['reg_date'] );
		$json['reg_user_name'] = $this->session->userdata( 'ss_name' );
		$json['comment'] = $input['comment'];

		echo json_encode( $json );
	}



	public function remove_work_url() {
		$check_list = ($_POST['chk']);
		$this->Work_model->table = 'work_biz_url';
		foreach ( $_POST['chk'] as $i => $val ) {

			$this->Work_model->delete( 'seqno', $val );
		}
	}



	public function download($type, $seqno) {
		$this->load->helper( 'download' );

		$this->Work_model->table = 'work_biz_url';
		$row = $this->Work_model->get_info( 'seqno', $seqno );

		$data = file_get_contents( $this->upload_path . $type . "/" . $row['new_name'] );
		force_download( $row['file_name'], $data );
	}



	public function add_complain() {
		$this->Work_model->table = 'work_complain';

		$input = null;
		$input['doctor'] = $this->input->post( 'doctor' );
		$input['complain'] = $this->input->post( 'complain' );
		$input['measure'] = $this->input->post( 'measure' );
		$input['reg_date'] = TIME_YMDHIS;
		$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
		$input['cst_seqno'] = $this->input->post( 'cst_seqno' );
		$input['complain_charger_id'] = $this->input->post( 'complain_charger_id' );
		$input['complain_charger_team'] = $this->input->post( 'complain_charger_team' );
		$input['complain_date'] = $this->input->post( 'complain_date' );
		$input['complain_type'] = $this->input->post( 'complain_type' );

		if ($this->input->post( 'mode' ) == 'MODIFY') {

			$input['mod_date'] = TIME_YMDHIS;
			$this->Work_model->update( 'seqno', $this->input->post( 'seqno' ), $input );
		} else {
			$this->Work_model->insert( $input );
		}
	}



	public function add_paper() {
		$this->Work_model->table = 'work_paper';

		$input = null;
		$input['title'] = $this->input->post( 'title' );
		$input['paper_type'] = $this->input->post( 'paper_type' );
		$input['info'] = $this->input->post( 'info' );

		if ($this->input->post( 'mode' ) == 'MODIFY') {

			$input['mod_date'] = TIME_YMDHIS;
			$this->Work_model->update( 'seqno', $this->input->post( 'seqno' ), $input );
			$paper_seqno = $this->input->post( 'seqno' );
		} else {

			$input['reg_date'] = TIME_YMDHIS;
			$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );

			$this->Work_model->insert( $input );
			$paper_seqno = $this->Work_model->get_insert_id();
		}

		$this->_upload_common_file( 'work_paper', $paper_seqno );

		$json = null;
		$json['seqno'] = $paper_seqno;

		echo json_encode( $json );
	}



	private function _upload_common_file($refer_table, $refer_seqno) {
		$this->Work_model->table = 'common_file';

		$files = $_FILES;
		$count = count( $_FILES['userfile']['name'] );
		for($i = 0; $i < $count; $i ++) {

			$_FILES['userfile']['name'] = $files['userfile']['name'][$i];
			$_FILES['userfile']['type'] = $files['userfile']['type'][$i];
			$_FILES['userfile']['tmp_name'] = $files['userfile']['tmp_name'][$i];
			$_FILES['userfile']['error'] = $files['userfile']['error'][$i];
			$_FILES['userfile']['size'] = $files['userfile']['size'][$i];

			$this->upload->initialize( $this->_set_upload_options( 'paper', $refer_seqno, $i ) );
			$this->upload->do_upload();

			$input = null;
			$input['refer_table'] = $refer_table;
			$input['refer_seqno'] = $refer_seqno;
			$input['file_name'] = $files['userfile']['name'][$i];
			$input['new_name'] = $this->upload->file_name;
			$input['file_size'] = $files['userfile']['size'][$i];
			$input['mime_type'] = $files['userfile']['type'][$i];

			$this->Work_model->insert( $input );
		}
	}



	public function remove_common_file() {

		$check_list = ($_POST['chk']);
		$this->Work_model->table = 'common_file';
		foreach ( $_POST['chk'] as $i => $val ) {

			$this->Work_model->delete( 'seqno', $val );

			//unlink

		}

	}

	function search_user() {

		$name = $this->input->post('name');
		$where['name LIKE '] = "%{$name}%";
		$where['status'] = '1';

		$user_list = $this->common_lib->search_user($where);

		if($user_list) {
			$team = $this->common_lib->get_team(); //팀
			foreach($user_list as $user) {
				$users[] = array(
					'label'=>$team[$user['team_code']].' > '.$user['name'],
					'no'=>$user['no'],
					'name'=>$user['name'],
					'user_id'=>$user['user_id']
				);
			}
			return_json(true, '',$users);
		}
		else {
			return_json(false);
		}
	}

	function save_minutes() {
		$p = $this->input->post(NULL, true);
		$record = array(
			'writer_id'=>$this->session->userdata('ss_user_id'),
			'type'=>$p['type'],
			'room_code'=>$p['room_code'],
			'room_etc'=>$p['room_etc'],
			'attendees'=>$p['attendees'],
			'issues'=>htmlspecialchars($p['issues'],ENT_QUOTES),
			'opinion_attendees'=>serialize($p['opinion']),
			'opinion_adopt'=>htmlspecialchars($p['opinion_adopt'], ENT_QUOTES),
			'minutes_start'=>$p['minutes_date'].' '.$p['minute_time_start'],
			'minutes_end'=>$p['minutes_date'].' '.$p['minute_time_end']
		);


		if($p['mode'] == 'insert') {
			$record['date_insert'] = NOW;
			$rs = $this->Work_model->insert_minutes($record);
		}
		else {
			$rs = $this->Work_model->update_minutes($record, array('no'=>$p['minutes_no']));
		}

		if($rs) {
			return_json(true);
		}
		else {
			return_json(false);
		}
	}

	function save_happycall() {
		$p = $this->input->post(NULL, true);

		$record = array(
			'cst_seqno'=>$p['cst_seqno'],
			'customer'=>$p['customer'],
			'writer_id'=>$this->session->userdata('ss_user_id'),
			'operation_date'=>$p['operation_date'],
			'treat_cost_no'=>$p['treat_cost_no'],
			'manager_team_code'=>$p['manager_team_code'],
			'manager_id'=>$p['manager_id'],
			'doctor_id'=>$p['doctor_id'],
			'comment'=>htmlspecialchars($p['comment'],ENT_QUOTES),
			'comment_special'=>htmlspecialchars($p['comment_special'],ENT_QUOTES)
		);


		if($p['mode'] == 'insert') {
			$record['date_insert'] = NOW;
			$rs = $this->Work_model->insert_happycall($record);
		}
		else {
			$rs = $this->Work_model->update_happycall($record, array('no'=>$p['happycall_no']));
		}

		if($rs) {
			return_json(true);
		}
		else {
			return_json(false);
		}
	}

	function comment_save() {
		$p = $this->input->post(NULL, true);

		$writer_id = $this->session->userdata('ss_user_id');
		$record = array(
			'paper_no'=>$p['paper_no'],
			'writer_id'=>$writer_id,
			'writer_info'=>serialize(array(
				'name'=>$this->session->userdata('ss_name'),
				'team_name'=>$this->session->userdata('ss_team_name'),
				'position_name'=>$this->session->userdata('ss_position_name')
			)),
			'comment'=>htmlspecialchars($p['comment'], ENT_QUOTES),
			'reader_id'=>$writer_id,
			'date_insert'=>NOW
		);

		$this->Work_model->table = 'work_paper_comment';
		$rs = $this->Work_model->insert($record);
		if($rs) {
			return_json(true,'의견이 등록되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
	}

	function comment_remove() {
		$p = $this->input->post(NULL, true);
		$where = array(
			'no'=>$p['comment_no']
		);
		$rs = $this->Work_model->delete_comment($where);
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
	}



	// 20170309 kruddo : 일일매출보고 등록(상담)
	public function add_biz_sales_log() {
		$this->Work_model->table = 'work_biz_expense';

		$team_code = $this->input->post( 'team_code' );
		$reg_user_id = $this->session->userdata( 'ss_user_id' );

		$expense_consulting_date = $this->input->post( 'expense_consulting_date' );
		$expense_project_date = $this->input->post( 'expense_project_date' );
		$expense_customer = $this->input->post( 'expense_customer' );
		$expense_path = $this->input->post( 'expense_path' );
		$expense_doctor = $this->input->post( 'expense_doctor' );
		$expense_consultant = $this->input->post( 'expense_consultant' );
		$expense_desc = $this->input->post( 'expense_desc' );


		$is_schedule = $this->input->post( 'is_schedule' );

		if($is_schedule == "Y"){
			$expense_receipt_price = $this->input->post( 'expense_s_receipt_price' );
			$expense_receipt_card = $this->input->post( 'expense_s_receipt_card' );
			$expense_receipt_money = $this->input->post( 'expense_s_receipt_money' );
			$expense_receipt_account = $this->input->post( 'expense_s_receipt_account' );

			$expense_deposit_refund = $this->input->post( 'expense_s_deposit_refund' );
			$expense_deposit_price = $this->input->post( 'expense_s_deposit_price' );					// 예치금
		}
		else{
			$expense_receipt_price = $this->input->post( 'expense_receipt_price' );
			$expense_receipt_card = $this->input->post( 'expense_receipt_card' );
			$expense_receipt_money = $this->input->post( 'expense_receipt_money' );
			$expense_receipt_account = $this->input->post( 'expense_receipt_account' );

			$expense_deposit_refund = $this->input->post( 'expense_deposit_refund' );
			$expense_deposit_unpaid = $this->input->post( 'expense_deposit_unpaid' );					// 미수금
		}

		$expense_etc = $this->input->post( 'expense_etc' );

		$input = array(
			'team_code' => $team_code,
			'reg_user_id' => $reg_user_id,
			'expense_consulting_date' => $expense_consulting_date,
			'expense_project_date' => $expense_project_date,
			'expense_customer' => $expense_customer,
			'expense_path' => $expense_path,
			'expense_doctor' => $expense_doctor,
			'expense_consultant'=>$expense_consultant,
			'expense_desc' => $expense_desc,

			'expense_receipt_price' => str_replace(",", "", $expense_receipt_price),
			'expense_receipt_card' => str_replace(",", "", $expense_receipt_card),
			'expense_receipt_money' => str_replace(",", "", $expense_receipt_money),
			'expense_receipt_account' => str_replace(",", "", $expense_receipt_account),

			'expense_deposit_refund' => str_replace(",", "", $expense_deposit_refund),
			'expense_deposit_unpaid' => str_replace(",", "", $expense_deposit_unpaid),
			'expense_deposit_price' => str_replace(",", "", $expense_deposit_price),

			'expense_etc' => $expense_etc,
			'date_insert' => NOW,
			'is_schedule' => $is_schedule,
		);

		$seqno = $this->Work_model->biz_expense_insert( $input );


		if($seqno) {
			return_json(true,'일일매출보고 등록되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}


	// 20170309 kruddo : 일일매출보고 등록(피부)
	public function add_biz_sales_skin_log() {
		$this->Work_model->table = 'work_biz_expense_skin';

		$team_code = $this->input->post( 'team_code' );
		$reg_user_id = $this->session->userdata( 'ss_user_id' );

		$expense_op_date = $this->input->post( 'expense_op_date' );
		$expense_customer = $this->input->post( 'expense_customer' );
		$expense_tel = $this->input->post( 'expense_tel' );
		$expense_op_contents = $this->input->post( 'expense_op_contents' );
		$expense_receipt_price = $this->input->post( 'expense_receipt_price' );
		$expense_doctor = $this->input->post( 'expense_doctor' );
		$expense_etc = $this->input->post( 'expense_etc' );

		$input = array(
			'team_code' => $team_code,
			'reg_user_id' => $reg_user_id,
			'expense_op_date' => $expense_op_date,
			'expense_customer' => $expense_customer,
			'expense_tel' => $expense_tel,
			'expense_op_contents' => $expense_op_contents,
			'expense_receipt_price' => str_replace(",", "", $expense_receipt_price),
			'expense_doctor' => $expense_doctor,
			'expense_etc' => $expense_etc,
			'date_insert' => NOW,
		);


		$seqno = $this->Work_model->biz_expense_insert( $input );

		if($seqno) {
			return_json(true,'일일매출보고 등록되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}

	}

	// 20170309 kruddo : 일일매출보고 등록(코디)
	public function add_biz_sales_codi_log() {

		$this->Work_model->table = 'work_biz_expense_codi';

		$team_code = $this->input->post( 'team_code' );
		$reg_user_id = $this->session->userdata( 'ss_user_id' );

		$expense_consulting_date = $this->input->post( 'expense_consulting_date' );
		$expense_customer = $this->input->post( 'expense_customer' );
		$expense_div = $this->input->post( 'expense_div' );

		$expense_project_today_yn = $this->input->post( 'expense_project_today_yn' );
		$expense_treat_part = $this->input->post( 'expense_treat_part' );
		$expense_path = $this->input->post( 'expense_path' );
		$expense_path2 = $this->input->post( 'expense_path2' );
		$expense_consulting = $this->input->post( 'expense_consulting' );
		$expense_closing = $this->input->post( 'expense_closing' );
		$expense_doctor = $this->input->post( 'expense_doctor' );

		$expense_deposit_price = $this->input->post( 'expense_deposit_price' );
		$expense_receipt_price = $this->input->post( 'expense_receipt_price' );
		$expense_receipt_card = $this->input->post( 'expense_receipt_card' );
		$expense_receipt_money = $this->input->post( 'expense_receipt_money' );
		$expense_receipt_account = $this->input->post( 'expense_receipt_account' );

		$expense_deposit_stay = $this->input->post( 'expense_deposit_stay' );

		$expense_etc = $this->input->post( 'expense_etc' );

		$input = array(
			'team_code' => $team_code,
			'reg_user_id' => $reg_user_id,
			'expense_consulting_date' => $expense_consulting_date,
			'expense_customer' => $expense_customer,
			'expense_div' => $expense_div,

			'expense_project_today_yn' => $expense_project_today_yn,
			'expense_treat_part' => $expense_treat_part,
			'expense_path' => $expense_path,
			'expense_path2' => $expense_path2,
			'expense_consulting' => $expense_consulting,
			'expense_closing' => $expense_closing,

			'expense_deposit_price' => str_replace(",", "", $expense_deposit_price),
			'expense_deposit_stay'=>$expense_deposit_stay,
			'expense_receipt_price' => str_replace(",", "", $expense_receipt_price),
			'expense_receipt_card' => str_replace(",", "", $expense_receipt_card),
			'expense_receipt_money' => str_replace(",", "", $expense_receipt_money),
			'expense_receipt_account' => str_replace(",", "", $expense_receipt_account),

			'expense_doctor' => $expense_doctor,
			'expense_etc' => $expense_etc,
			'date_insert' => NOW,
		);


		$seqno = $this->Work_model->biz_expense_insert( $input );

		if($seqno) {
			return_json(true,'일일매출보고 등록되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}


	public function remove_biz_sales_log() {

		$biz_type = $this->input->post( 'biz_type' );

		if($biz_type == 1){
			$this->Work_model->table = 'work_biz_expense';
		}
		else if($biz_type == 2){
			//$this->Work_model->table = 'work_biz_expense_codi';

			$biz_table = $this->input->post( 'biz_table' );
			if($biz_table == 'op'){
				$this->Work_model->table = 'work_biz_expense_codi_op';
			}
			else{
				$this->Work_model->table = 'work_biz_expense_codi';
			}

		}
		else if($biz_type == 3){
			$this->Work_model->table = 'work_biz_expense_skin';
		}

		$input['is_delete'] = 'Y';

		$this->Work_model->update( 'no', $this->input->post( 'no' ), $input );
		return_json(true,'일일매출보고 삭제하였습니다.');
	}


	// 20170309 kruddo : 일일매출보고 등록(상담, 코디, 피부)



	// 20170309 kruddo : 일일OP매출보고 등록(코디)

	public function add_biz_sales_codi_op_log() {

		$this->Work_model->table = 'work_biz_expense_codi_op';

		$team_code = $this->input->post( 'team_code' );
		$reg_user_id = $this->session->userdata( 'ss_user_id' );

		$expense_item = $this->input->post( 'expense_item' );
		$expense_consulting_date = $this->input->post( 'expense_consulting_date' );
		$expense_customer = $this->input->post( 'expense_customer' );
		$expense_tel = $this->input->post( 'expense_tel' );
		$expense_op_contents = $this->input->post( 'expense_op_contents' );

		$expense_op_price = $this->input->post( 'expense_op_price' );
		$expense_provide_price = $this->input->post( 'expense_provide_price' );
		$expense_surtax_price = $this->input->post( 'expense_surtax_price' );

		$expense_op_money = $this->input->post( 'expense_op_money' );
		$expense_op_card = $this->input->post( 'expense_op_card' );
		$expense_op_account = $this->input->post( 'expense_op_account' );

		$expense_deposit_money = $this->input->post( 'expense_deposit_money' );
		$expense_deposit_card = $this->input->post( 'expense_deposit_card' );

		$expense_deposit_account = $this->input->post( 'expense_deposit_account' );
		$expense_reserve_account = $this->input->post( 'expense_reserve_account' );
		$expense_unpaid_account = $this->input->post( 'expense_unpaid_account' );
		$expense_deposit_price = $this->input->post( 'expense_deposit_price' );

		$expense_doctor = $this->input->post( 'expense_doctor' );
		$expense_consulting = $this->input->post( 'expense_consulting' );
		$expense_etc = $this->input->post( 'expense_etc' );

		$input = array(
			'team_code' => $team_code,
			'reg_user_id' => $reg_user_id,

			'expense_item' => $expense_item,
			'expense_consulting_date' => $expense_consulting_date,
			'expense_customer' => $expense_customer,
			'expense_tel' => $expense_tel,
			'expense_op_contents' => $expense_op_contents,

			'expense_op_price' => str_replace(",", "", $expense_op_price),
			'expense_provide_price' => str_replace(",", "", $expense_provide_price),
			'expense_surtax_price' => str_replace(",", "", $expense_surtax_price),

			'expense_op_money' => str_replace(",", "", $expense_op_money),
			'expense_op_card' => str_replace(",", "", $expense_op_card),
			'expense_op_account' => str_replace(",", "", $expense_op_account),

			'expense_deposit_money' => str_replace(",", "", $expense_deposit_money),
			'expense_deposit_card' => str_replace(",", "", $expense_deposit_card),

			'expense_deposit_account' => str_replace(",", "", $expense_deposit_account),
			'expense_reserve_account' => str_replace(",", "", $expense_reserve_account),
			'expense_unpaid_account' => str_replace(",", "", $expense_unpaid_account),
			'expense_deposit_price' => str_replace(",", "", $expense_deposit_price),


			'expense_doctor' => $expense_doctor,
			'expense_consulting' => $expense_consulting,
			'expense_etc' => $expense_etc,
			'date_insert' => NOW,
		);


		$seqno = $this->Work_model->biz_expense_insert( $input );

		if($seqno) {
			return_json(true,'일일매출보고 등록되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

/*
	public function remove_biz_sales_log() {

		$biz_type = $this->input->post( 'biz_type' );

		if($biz_type == 1){
			$this->Work_model->table = 'work_biz_expense';
		}
		else if($biz_type == 2){
			$biz_table = $this->input->post( 'biz_table' );
			if($biz_table == 'op'){
				$this->Work_model->table = 'work_biz_expense_codi_op';
			}
			else{
				$this->Work_model->table = 'work_biz_expense_codi';
			}
		}
		else if($biz_type == 3){
			$this->Work_model->table = 'work_biz_expense_skin';
		}

		$input['is_delete'] = 'Y';

		$this->Work_model->update( 'no', $this->input->post( 'no' ), $input );
		return_json(true,'일일매출보고 삭제하였습니다.');
	}
*/

	// 20170309 kruddo : 일일OP매출보고 등록(코디)
}
