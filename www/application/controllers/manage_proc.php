<?php if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );
/**
 * 작성 : 2014.10.28
 * 수정 :
 *
 * @author 이미정
 */

class Manage_proc extends CI_Controller {

	private $dir = './DATA/user/';
	private $pwd = '1234567';

	public function __construct() {
		parent::__construct();
		$this->param = $this->input->post(NULL, true);
		$this->load->model( array (
				'Manage_model',
				'User_model'
		) );
	}

	private function _call_msg($result) {
		$msg = ($result) ? '저장 완료하였습니다.' : '저장 실패했습니다.';
		$json = null;
		$json['msg'] = $msg;

		echo json_encode( $json );
	}


	public function basic_update() {

		$param = $this->input->post(NULL, true);
		$input = null;
		$input['hospital_name'] = $this->input->post( 'hospital_name' );
		$input['chief_name'] = $this->input->post( 'chief_name' );
		$input['open_date'] = $this->input->post( 'open_date' );
		$input['corp_no'] = $this->input->post( 'corp_no' );
		$input['addr'] = $this->input->post( 'addr' );
		$input['tel'] = $this->input->post( 'tel' );
		$input['etc'] = $this->input->post( 'etc' );
		$input['is_reset'] = (isset($param['is_reset']))?$param['is_reset']:'N';
		$input['is_cash'] = (isset($param['is_cash']))?$param['is_cash']:'N';

		$input['mod_date'] = TIME_YMDHIS;

		$result = $this->Manage_model->update( 'hospital_info', 'hst_code', $this->session->userdata( 'ss_hst_code' ), $input );


		$this->load->model('Patient_model');
		$this->load->library('x_lib');
		if($param['is_reset_old'] != $input['is_reset']) {
			if($input['is_reset'] == 'Y') {
				$this->x_lib->x();
			}
			else {
				$this->x_lib->o();
			}
		}

		if($param['is_cash_old'] != $input['is_cash']) {
			if($input['is_cash'] == 'Y') {
				//echo 'x';
				$this->x_lib->cash_x();
			}
			else {
				$this->x_lib->cash_o();
			}
		}



		$this->_call_msg( $result );

	}

	function xxx() {
		$date = $this->uri->segment(3);
		$this->load->library('x_lib');

		$this->x_lib->xx($date);
	}

	function dbdump() {
		$dbname = $this->db->database;
		$command = "mysqldump -u".$this->db->username."  -p".$this->db->password." ".$this->db->database." > /home/{$dbname}/backup/db/auto_{$dbname}.".date('Ymd_Hi').".sql";
		exec($command, $output, $return_var);
	}



	public function add_user() {
		$p = $this->param;
		$join_ym = date('ym',strtotime($p['join_date']));
		$count = $this->User_model->count_user(array('DATE_FORMAT(join_date,"%y%m")'=>$join_ym));
		$user_code = $p['prefix'].$join_ym.'N'.str_pad(($count+1), '2', '0', STR_PAD_LEFT);
		$record = array(
			'user_id'=>$p['user_id'],
			'user_code'=>$user_code,
			'name'=>$p['name'],
			'passwd'=>md5($this->pwd),
			'mobile'=>$p['mobile'],
			'email'=>$p['email'],
			'duty_code'=>$p['duty_code'],
			'position_code'=>$p['position_code'],
			'hst_code'=>$p['hst_code'],
			'dept_code'=>$p['dept_code'],
			'team_code'=>$p['team_code'],
			'join_date'=>$p['join_date'],
			'status'=>$p['status'],
			'biz_id'=>','.implode(',',$p['biz_id']).','
		);

		$rs = $this->User_model->insert_user($record);
		if($rs){
			return_json(true,'저장되었습니다.');			}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}

	public function reset_passwd() {
		$user_no = $this->param['user_no'];
		$record = array('passwd'=>md5($this->pwd));
		$where = array('no'=>$user_no);

		$rs = $this->User_model->update_user($record, $where);
		if($rs){
			return_json(true,'비밀번호가 초기화되었습니다.');
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}

	/**
	 * 직원사진 업로드
	 * @return [type] [description]
	 */
	public function save_user_photo() {

		$user_no = $this->param['user_no'];

		$upload_path = $this->dir.$user_no.'/profile';
		// echo $upload_path;
		if(!is_dir($upload_path)) @mkdir($upload_path, 0777, true);
		$config['upload_path'] = $upload_path;
		$config['encrypt_name'] = TRUE;
		$config['allowed_types'] = '*';

		$this->load->library('upload', $config);
		$this->upload->initialize($config);
		if ( !$this->upload->do_upload('user_photo') ) {
			return_json(false, $this->upload->display_errors());
		}
		else {
			$upload_data = $this->upload->data();
			$dir = str_replace($_SERVER['DOCUMENT_ROOT'],'',$upload_data['file_path']);

			//썸네일
			$this->load->helper('file');
			$rs_resize = resize_image(array('width'=>200, 'height'=>200, 'source_image'=>$upload_data['full_path']));
			$photo = ($rs_resize)?$upload_data['raw_name'].'_thumb'.$upload_data['file_ext']:$upload_data['file_name'];

			$record = array('photo'=>$dir.$photo);
			// pre($record);
			$rs = $this->User_model->update_user($record, array('no'=>$user_no));
			if($rs){
				return_json(true);
			}
			else {
				return_json(false, '잠시 후에 다시 시도해주세요.');
			}
		}
	}

	/**
	 * 탭정보 저장(UPDATE)
	 * @return [type] [description]
	 */
	public function save_user_tab() {
		$p = $this->input->post(NULL, true);
		$user_no = $p['no'];
		switch($p['tab_no']) {
			case '6': //기본정보
				$record = array(
					'status'=>$p['status'],
					'join_date'=>$p['join_date'],
					'retire_date'=>$p['retire_date'],
					'name'=>$p['name'],
					'dept_code'=>$p['dept_code'],
					'team_code'=>$p['team_code'],
					'occupy_code'=>$p['occupy_code'],
					'position_code'=>$p['position_code'],
					'duty_code'=>$p['duty_code'],
					'income'=>str_replace(',','',$p['income']), //연봉정보
					'mobile'=>$p['mobile'],
					'email'=>$p['email'],
					'biz_id'=>','.implode(',',$p['biz_id']).','
				);
			break;
			case '1': //개인정보
				$record = array(
					'name_en'=>$p['name_en'],
					'name_cn'=>$p['name_cn'],
					'householder'=>$p['householder'],
					'householder_relationship'=>$p['householder_relationship'],
					'hobby'=>$p['hobby'],
					'religion'=>$p['religion'],
					'blood'=>$p['blood'],
					'personal_number'=>$p['personal_number'],
					'birthday'=>$p['birthday'],
					'birthday_type'=>$p['birthday_type'],
					'zipcode'=>$p['zipcode'],
					'address'=>$p['address'],
					'address_detail'=>htmlspecialchars($p['address_detail'], ENT_QUOTES),
					'zipcode_real'=>$p['zipcode_real'],
					'address_real'=>$p['address_real'],
					'address_detail_real'=>htmlspecialchars($p['address_detail_real'], ENT_QUOTES),
					'account_bank'=>$p['account_bank'],
					'account_number'=>$p['account_number'],
					'military_type'=>$p['military_type'],
					'military_info'=>(is_array($p['military_info']))?serialize($p['military_info']):'',
					'veterans_type'=>$p['veterans_type'],
					'veterans_info'=>(is_array($p['veterans_info']))?serialize($p['veterans_info']):'',
					'defect_type'=>$p['defect_type'],
					'defect_info'=>(is_array($p['defect_info']))?serialize($p['defect_info']):''
				);
			break;
			case '4': //기타
				$record = array(
					'recruit_info'=>(is_array($p['recruit_info']))?serialize($p['recruit_info']):'', //채용정보
					'emergency_info'=>(is_array($p['emergency_info']))?serialize($p['emergency_info']):'' //긴급연락처
				);
			break;
		}

		// pre($record);
		$where = array('no'=>$user_no);
		$rs = $this->User_model->update_user($record, $where);
		if($rs){
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}



	/**
	 * 퇴사처리
	 * @deprecated
	 * @return [type] [description]
	 */
	public function user_off() {
		foreach ( $_POST['chk'] as $i => $val ) {
			$user_id[] = $val;
		}

		$input = null;
		$input['status'] = 0;
		$this->User_model->update_in( $user_id, $input );
	}

	public function retire_user() {
		$p = $this->param;
		$record = array(
			'status'=>'3',
			'retire_date'=>NOW
		);
		$where = array('no'=>$p['user_no']);
		$rs = $this->User_model->update_user($record, $where);
		if($rs) {
			return_json(true,'퇴사처리되었습니다.');
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}



	public function update_passwd() {
		$where['user_id'] = $this->session->userdata( 'ss_user_id' );
		$where['passwd'] = md5( $this->input->post( 'password' ) );
		$where['status'] = '1';

		$row = $this->User_model->get_info( $where );
		if ($row['user_id'] == '') {
			return_json(false,'현재 비밀번호가 올바르지 않습니다.');
		}



		$record = array(
			'passwd'=>md5($this->input->post('password_new'))
		);

		$rs = $this->User_model->update( $this->session->userdata( 'ss_user_id' ), $record );
		if($rs) {
			return_json(true,'비밀번호가 변경되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
	}


	/**
	 * 중복체크
	 * @return [type] [description]
	 */
	public function check_duplicate() {
		$user_no = $this->param['user_no'];
		$field = $this->param['field'];
		$value = $this->param['value'];

		$where[$field] = $value;
		if($user_no>0) $where['no !='] = $user_no;


		$count = $this->User_model->count_user($where);
		if($count>0) {
			return_json(false);
		}
		else {
			return_json(true);
		}
	}



	public function ip_insert() {
		$total = $this->Manage_model->get_ip( $this->input->post( 'ip' ), $this->session->userdata( 'ss_hst_code' ) );
		$input = null;
		$input['ip'] = trim( $this->input->post( 'ip' ) );
		$input['info'] = $this->input->post( 'info' );
		$input['reg_date'] = TIME_YMDHIS;
		$input['hst_code'] = $this->session->userdata( 'ss_hst_code' );

		$status = 0;
		if ($total == 0) {
			$status = 1;
			$this->Manage_model->insert( 'mgr_ip', $input );
		}
		$json = null;
		$json['status'] = $status;

		echo json_encode( $json );
	}



	public function ip_off() {
		foreach ( $_POST['chk'] as $i => $val ) {
			$seqno[] = $val;
		}

		$input = null;
		$input['use_flag'] = 'N';
		$this->Manage_model->update_in( 'mgr_ip', 'seqno', $seqno, $input );
	}



	public function access_insert() {

		$this->output->enable_profiler( TRUE );

		$this->Manage_model->set_access_init( $this->input->post( 'menu_seqno' ) );

		foreach ( $_POST['dept_code'] as $i => $val ) {
			 $input_category[] = 'dept';
			 $input_valid_code[] = $val;
		}


		foreach ( $_POST['team_code'] as $i => $val ) {
			$input_category[] = 'team';
			$input_valid_code[] = $val;
		}

		foreach ( $_POST['duty_code'] as $i => $val ) {
			$input_category[] = 'duty';
			$input_valid_code[] = $val;
		}

		foreach ( $input_valid_code as $i => $val ) {
			$input = null;
			$input['menu_seqno'] = $this->input->post( 'menu_seqno' );
			$input['category'] = $input_category[$i];
			$input['valid_code'] = $input_valid_code[$i];

			$this->Manage_model->insert( 'mgr_access', $input );
		}
	}

	public function make_config_menu() {
		$menus = $this->Manage_model->get_menu();

		$config_path = APPPATH.'config/cmltd_menu.php';


		$fp = fopen($config_path, "w");
		unset($config_content);
		$config_content[] = "<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');";

		foreach ($menus as $m) {
			$mk = $m['no'];
			foreach ($m as $key=>$value) {
				if($key == 'route') {
					$route = explode(',',$value);
					foreach($route as $rk=>$rv) {
						$config_content[] = '$config'."['cmltd_menu']['$mk']['$key']['$rk']='$rv';";
					}
				}
				else {
					$config_content[] = '$config'."['cmltd_menu']['$mk']['$key']='$value';";
				}

			}

			$depth = $m['depth'];
			$config_depth[$depth][] = $mk;

			if($depth >2 && $m['href']) {
				$config_href[$m['href']] = $route;
			}


			$config_all[] = $mk;
		}


		$config_content[]='';
		foreach($config_depth as $depth=>$mk) {
			$value = implode(',',$mk);
			$config_content[] = '$config'."['cmltd_depth']['$depth']='$value';";
		}


		$config_content[]='';
		foreach ($config_href as $href=>$route) {
			foreach($route as $rk=>$rv) {
				$config_content[] = '$config'."['cmltd_href']['$href'][$rk]='$rv';";
			}

		}

		$config_content[] = '';
		$config_content[] = '$config'."['cmltd_all']='".implode(',',$config_all)."';";
		$config_content[] = '?>';

		fwrite($fp, implode(PHP_EOL,$config_content));
		fclose($fp);
	}

	/**
	 * 메뉴권한설정 저장
	 * @return json
	 */
	function grant_save() {
		$menu_no = $this->input->post('menu_no');
		$grant_duty = $this->input->post('grant_duty');
		$grant_dept = $this->input->post('grant_dept');
		$grant_biz = array();
		// pre($this->param);
		// exit;

		$grant_dept_value = array();
		if(is_array($grant_dept)) {
			foreach($grant_dept as $row) {
				list($info, $biz_id) = explode('|',$row);

				list($dept, $team, $user) = explode('_',$info);
				if($dept == 'all') {
					$grant_dept_value[] = 'all';
					break;
				}

				$grant = array();
				$grant[] = $dept;
				if($team) $grant[] = $team;
				if($user) $grant[] = $user;

				$grant_key = implode('_',$grant);
				$grant_dept_value[] = $grant_key;

				//사업장
				$grant_biz[$grant_key] = explode(',',$biz_id);
			}
			$grant_dept_value = ','.implode(',',$grant_dept_value).',';
		}
		else {
			$grant_dept_value='';
		}

		// if(is_array($grant_duty)) {
		// 	$grant_duty_value = ','.implode(',',$grant_duty).',';
		// }
		// else {
		// 	$grant_duty_value = '';
		// }


		//메뉴 권한 정보 저장
		$datum = array(
			// 'grant_duty'=>$grant_duty_value,
			'grant_dept'=>$grant_dept_value,
			'grant_dept_biz'=>serialize($grant_biz)
		);

		$rs = $this->Manage_model->update_menu($datum, $menu_no);
		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'저장에 실패하였습니다.');
		}
	}

	function group_save() {

		$no = $this->input->post('no');
		$mode = $this->input->post('mode');
		$code = $this->input->post('code');

		$record = array(
			'type'=>$this->input->post('type'),
			'comment'=>$this->input->post('comment'),
			'is_use'=>$this->input->post('is_use'),
			'users'=>$this->input->post('grant')
		);
		if($mode == 'insert') {
			//그룹코드 중복체크
			$count = $this->Manage_model->count_group(array('group_code'=>$code));
			if($count>0) {
				return_json(false,'이미 존재하는 그룹코드입니다.');
			}

			$record['group_code'] = $code;
			$record['date_insert'] =date('Y-m-d H:i:s');
			$rs = $this->Manage_model->insert_group($record);
		}
		else {
			$rs = $this->Manage_model->update_group($record, array('no'=>$no));
		}


		if($rs) {
			return_json(true, '');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function group_remove() {
		$no = $this->input->post('no');
		$rs = $this->Manage_model->delete_group(array('no'=>$no));

		if($rs) {
			return_json(true, '');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 관리 그룹 사용여부 변경
	 * @return [type] [description]
	 */
	function group_set_use() {
		$is_use = $this->input->post('is_use');
		$no = $this->input->post('no');

		$record = array(
			'is_use'=>$is_use
		);
		$rs = $this->Manage_model->update_group($record, array('no'=>$no));

		if($rs) {
			return_json(true, '');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}


	/**
	 * 팀 사용여부 변경
	 * @return [type] [description]
	 */
	function team_set_status() {
		$status = $this->input->post('status');
		$team_code = $this->input->post('team_code');

		$record = array(
			'status'=>$status
		);
		$rs = $this->User_model->update_team($record, array('team_code'=>$team_code));

		if($rs) {
			return_json(true, '');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function team_save() {
		$p = $this->param;
		$team_code = $this->input->post('team_code');
		$mode = $this->input->post('mode');

		$record = array(
			'biz_id'=>$p['biz_id'],
			'dept_code'=>$this->input->post('dept_code'),
			'status'=>$this->input->post('status'),
			'team_name'=>$this->input->post('team_name')
		);
		if($mode == 'insert') {
			//팀코드 중복체크
			$count = $this->Manage_model->count_team(array('team_code'=>$team_code));
			if($count>0) {
				return_json(false,'이미 존재하는 그룹코드입니다.');
			}

			$record['team_code'] = $team_code;
			$rs = $this->Manage_model->insert_team($record);
		}
		else {
			$rs = $this->Manage_model->update_team($record, array('team_code'=>$team_code));
		}


		if($rs) {
			return_json(true, '저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function team_move() {
		$team_from = $this->input->post('team_from');
		$team_to = $this->input->post('team_to');
		$where = array('team_code'=>$team_from);
		$record = array('team_code'=>$team_to);
		$rs = $this->User_model->update_user($record, $where);
		if($rs) {
			return_json(true, '팀원이 이동되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
		//update_user
	}
}
