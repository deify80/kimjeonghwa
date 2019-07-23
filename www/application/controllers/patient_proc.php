<?php if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Patient_proc extends CI_Controller {

	private $dir = './DATA/patient/';

	public function __construct() {
		parent::__construct();
		$this->param = $this->input->post(NULL, true);
		$this->load->model(array('Patient_Model'));
	}

	/**
	 * 위젯설정(일괄)
	 * @return [type] [description]
	 */
	public function save_widget() {
		$this->load->model('User_Model');
		$sort_arr = explode(',',$this->param['sort']);
		$success = true;
		foreach($sort_arr as $idx => $widget_id) {
			$sort = $idx+1;
			$record = array(
				'user_no'=>$this->session->userdata('ss_user_no'),
				'widget'=>$widget_id,
				'sort'=>$sort
			);

			if(!empty($this->param['status'])) {
				$record['status']=$this->param['status'][$widget_id];
			}

			$rs = $this->User_Model->insert_widget($record);
			if(!$rs) $success = false;
		}

		if($success) {
			return_json(true);
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}


	/**
	 * 위젯설정 for Row
	 * @return [type] [description]
	 */
	public function save_widget_row() {
		$this->load->model('User_Model');
		$record = array(
			'user_no'=>$this->session->userdata('ss_user_no'),
			'widget'=>$this->param['widget'],
			'status'=>$this->param['status'],
		);

		$rs = $this->User_Model->insert_widget($record);

		if($rs) {
			return_json(true);
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 환자데이터 중복체크
	 * @return [type] [description]
	 */
	function check_duplicate() {
		$patient_no = $this->param['patient_no'];
		$field = $this->param['field'];
		$value = $this->param['value'];

		$where[$field] = $value;
		if($patient_no>0) $where['no !='] = $patient_no;


		$count = $this->Patient_Model->count_patient($where);
		if($count>0) {
			return_json(false);
		}
		else {
			return_json(true);
		}
	}

	/**
	 * 환자정보 저장
	 * @return [type] [description]
	 */
	function save_patient() {
		$p = $this->param;
		$mode = $p['mode'];
		$patient_no = $p['no'];

		// $grade_type = $p['grade_type'];
		// if(in_array($grade_type ,array('2','3'))) { //컴플레인, 블랙리스트
		// 	$grade_code = $grade_type;
		// }
		// else {
		// 	$grade_code = $this->_calc_grade($patient_no);
		// }

		$record = array(
			'name'=>$p['name'],
			'grade_type'=>$p['grade_type'],
			'jumin'=>$p['jumin'],
			'birth'=>str_replace('/','',$p['birth']),
			'sex'=>$p['sex'],
			'tel'=>$p['tel'],
			'mobile'=>str_replace('-','',trim($p['mobile'])),
			'messenger'=>trim($p['messenger']),
			'email'=>$p['email'],
			'doctor_id'=>$p['doctor_id'],
			'manager_team_code'=>$p['manager_team_code'],
			'manager_id'=>$p['manager_id'],
			'treat_cost_no'=>$p['treat_cost_no'],
			//'treat_region_code'=>$p['treat_region_code'],
			//'treat_item_code'=>$p['treat_item_code'],
			'introducer_no'=>$p['introducer_no'],
			'zipcode'=>$p['zipcode'],
			'address'=>$p['address'],
			'address_detail'=>$p['address_detail'],
			'job_code'=>$p['job_code'],
			'company'=>$p['company'],
			'path_code'=>$p['path_code'],
			'comment'=>htmlspecialchars($p['comment'], ENT_QUOTES),
			'agree_privacy'=>(isset($p['agree_privacy']))?$p['agree_privacy']:'N',
			'agree_sms'=>(isset($p['agree_sms']))?$p['agree_sms']:'N',
			'agree_email'=>(isset($p['agree_email']))?$p['agree_email']:'N',
			'agree_surgery'=>(isset($p['agree_surgery']))?$p['agree_surgery']:'N',
			'agree_photo'=>(isset($p['agree_photo']))?$p['agree_photo']:'N',
			'stay_status'=>$p['stay_status'],
			'hst_code'=>$this->session->userdata('ss_hst_code'),
			'biz_id'=>$this->session->userdata('ss_biz_id'),
			'is_o'=>(!empty($p['is_o']))?$p['is_o']:'N',
			'is_x'=>(!empty($p['is_x']))?$p['is_x']:'N'
		);




		if($mode == 'insert') {

			//메신저중복체크
			if($record['messenger']) {
				$count = $this->Patient_Model->count_patient(array('messenger'=>$record['messenger']));
				if($count) {
					return_json(false,'동일한 메신저가 존재합니다.');
				}
			}

			//전화번호 중복체크
			if($record['mobile']) {
				$count = $this->Patient_Model->count_patient(array('mobile'=>$record['mobile']));
				if($count) {
					return_json(false,'동일한 휴대폰번호가 존재합니다.');
				}
			}

			//상담코드 중복체크
			if($record['cst_seqno']) {
				$count = $this->Patient_Model->count_patient(array('cst_seqno'=>$record['cst_seqno']));
				if($count) {
					return_json(false,'이미 추가된 상담환자입니다.');
				}
			}

			//차트번호
			$count = $this->Patient_Model->count_patient(array('DATE_FORMAT(date_insert,"%Y-%m-%d")'=>date('Y-m-d')));
			$chart_no = date('Ymd').'-'.str_pad(($count+1),3,'0',STR_PAD_LEFT);

			$record['chart_no'] = $chart_no;
			$record['cst_seqno'] = $p['cst_seqno'];
			$record['date_insert'] = NOW;
			$rs = $this->Patient_Model->insert_patient($record);
		}
		else {
			if(!$p['auth_update']){
				unset($record['mobile']);
			}
			$rs = $this->Patient_Model->update_patient($record, array('no'=>$patient_no));
		}

		if($rs) {
			$this->load->model('Consulting_Model');

			if($mode=='insert') {
				$patient_no = $rs;
				if($p['cst_seqno'] > 0) {
					$this->Consulting_Model->update_consulting(array('patient_no'=>$patient_no), array('cst_seqno'=>$p['cst_seqno']));
				}
			}
			else {
				//상담DB 동기화
				if($p['cst_seqno'] > 0) {

					$cst_record_old = $this->Consulting_Model->select_consulting_row(array('cst_seqno'=>$p['cst_seqno'])); //기존데이터
					$cst_record = array(
						'name'=>$record['name'],
						'messenger'=>$record['messenger'],
						'tel'=>str_replace('-','',$record['mobile']),
						'birth'=>str_replace('/','',$record['birth']),
						'sex'=>$record['sex'],
						'email'=>$record['email'],
						'job_code'=>$record['job_code']
					);

					if(!$p['auth_update']){
						//unset($record['messenger']);
						unset($cst_record['tel']);
						//unset($record['email']);
					}

					$rs_sync = $this->Consulting_Model->update_consulting($cst_record, array('cst_seqno'=>$p['cst_seqno']));
					if($rs_sync) {
						$this->load->library('consulting_lib');
						$this->consulting_lib->save_log($cst_record_old, $cst_record);
					}
				}
			}

			return_json(true, '저장되었습니다.', array('no'=>$patient_no, 'mode'=>$mode));
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function _calc_grade($amount_paid) {
		$grade_list = $this->Patient_Model->select_patient_all(array('is_use'=>'Y'),'patient_grade','no, standard','standard DESC','no');
		$grade_no = '1';

		foreach($grade_list as $no => $grade) {
			if($amount_paid >= $grade['standard']) {
				$grade_no = $no;
				break;
			}
		}
		return $grade_no;
	}

	/**
	 * 상담일지 등록/수정
	 * @return [type] [description]
	 */
	function save_patient_consulting() {
		$p = $this->param;
		$mode = $p['mode'];
		$patient_no = $p['patient_no'];
		$project_no = $p['project_no'];
		$consulting_no = $p['consulting_no'];

		$record = array(
			'manager_team_code'=>$p['manager_team_code'],
			'manager_id'=>$p['manager_id'],
			'method'=>$p['method'],
			'treat_info'=>$p['treat'],
			'comment'=>htmlspecialchars($p['comment'], ENT_QUOTES),
			'date_consulting'=>$p['date_consulting']
		);

		if($mode == 'insert') {
			$record['patient_no'] = $patient_no;
			$record['project_no'] = $project_no;
			$record['writer_id'] = $this->session->userdata('ss_user_id');
			$record['date_insert'] = NOW;
			$rs = $this->Patient_Model->insert_patient($record, 'patient_consulting');
		}
		else {
			$rs = $this->Patient_Model->update_patient($record, array('no'=>$consulting_no),'patient_consulting');
		}

		if($rs) {
			return_json(true, '저장되었습니다.', array('no'=>$patient_no, 'mode'=>$mode));
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 컴플레인일지 등록/수정
	 * @return [type] [description]
	 */
	function save_patient_complain() {
		$p = $this->param;
		$mode = $p['mode'];
		$patient_no = $p['patient_no'];
		$project_no = $p['project_no'];
		$complain_no = $p['complain_no'];

		$record = array(
			'treat_info'=>$p['treat'],
			'comment'=>htmlspecialchars($p['comment'], ENT_QUOTES),
			'date_complain'=>$p['date_complain']
		);

		if($mode == 'insert') {
			$record['patient_no'] = $patient_no;
			$record['project_no'] = $project_no;
			$record['writer_id'] = $this->session->userdata('ss_user_id');
			$record['date_insert'] = NOW;
			$rs = $this->Patient_Model->insert_patient($record, 'patient_complain');
		}
		else {
			$rs = $this->Patient_Model->update_patient($record, array('no'=>$complain_no),'patient_complain');
		}

		if($rs) {
			return_json(true, '저장되었습니다.', array('no'=>$patient_no, 'mode'=>$mode));
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 치료일지 등록/수정
	 * @return [type] [description]
	 */
	function save_patient_treat() {
		// pre($this->param);

		$mode = $this->param['mode'];
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$treat_no = $this->param['treat_no'];

		$record = array(
			'doctor_id'=>$this->param['doctor_id'],
			'nurse_id'=>$this->param['nurse_id'],
			'skincare_id'=>$this->param['skincare_id'],
			'treat_info'=>$this->param['treat'],
			'comment'=>htmlspecialchars($this->param['comment'], ENT_QUOTES),
			'date_treat'=>$this->param['date_treat']
		);

		if($mode == 'insert') {
			$record['patient_no'] = $patient_no;
			$record['project_no'] = $project_no;
			$record['writer_id'] = $this->session->userdata('ss_user_id');
			$record['date_insert'] = NOW;
			$rs = $this->Patient_Model->insert_patient($record, 'patient_treat');
		}
		else {
			$rs = $this->Patient_Model->update_patient($record, array('no'=>$treat_no),'patient_treat');
		}

		// echo $this->db->last_query();

		if($rs) {
			return_json(true, '저장되었습니다.', array('no'=>$patient_no, 'mode'=>$mode));
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 피부관리일지 등록/수정
	 * @return [type] [description]
	 */
	function save_patient_skin() {

		$p = $this->param;
		$mode = $p['mode'];
		$patient_no = $p['patient_no'];
		$project_no = $p['project_no'];
		$skin_no = $p['skin_no'];

		$record = array(
			'doctor_id'=>$p['doctor_id'],
			'skincare_id'=>$p['skincare_id'],
			'manager_team_code'=>$p['manager_team_code'],
			'manager_id'=>$p['manager_id'],
			'cnt_care_used'=>$p['cnt_care_used'],
			'cnt_care_total'=>$p['cnt_care_total'],
			'treat_info'=>$p['treat'],
			'comment'=>htmlspecialchars($p['comment'], ENT_QUOTES),
			'date_skincare'=>$p['date_skincare']
		);

		if($mode == 'insert') {
			$record['patient_no'] = $patient_no;
			$record['project_no'] = $project_no;
			$record['writer_id'] = $this->session->userdata('ss_user_id');
			$record['date_insert'] = NOW;
			$rs = $this->Patient_Model->insert_patient($record, 'patient_skin');
		}
		else {
			$rs = $this->Patient_Model->update_patient($record, array('no'=>$skin_no),'patient_skin');
		}

		if($rs) {
			return_json(true, '저장되었습니다.', array('no'=>$patient_no, 'mode'=>$mode));
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 수술간호일지 등록/수정
	 * @return [type] [description]
	 */
	function save_patient_nurse() {
		$mode = $this->param['mode'];
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$nurse_no = $this->param['nurse_no'];


		$nurse_id_arr = $this->param['nurse_id'];
		if(is_array($nurse_id_arr)) $nurse_id=implode(',',$nurse_id_arr);
		$record = array(
			'doctor_id'=>$this->param['doctor_id'],
			'nurse_id'=>$nurse_id,
			'assistance_id'=>$this->param['assistance_id'],
			'treat_type_code'=>$this->param['treat_type_code'],
			'treat_info'=>$this->param['treat'],
			'time_in'=>$this->param['time_in'],
			'time_out'=>$this->param['time_out'],
			'height'=>$this->param['height'],
			'weight'=>$this->param['weight'],
			'bp'=>$this->param['bp'],
			'anesthesia_type'=>$this->param['anesthesia_type'],
			'medication'=>serialize($this->param['medication']),
			'comment'=>htmlspecialchars($this->param['comment'], ENT_QUOTES),
			'date_nurse'=>$this->param['date_nurse']
		);

		if($mode == 'insert') {
			$record['patient_no'] = $patient_no;
			$record['project_no'] = $project_no;
			$record['writer_id'] = $this->session->userdata('ss_user_id');
			$record['date_insert'] = NOW;
			$rs = $this->Patient_Model->insert_patient($record, 'patient_nurse');
		}
		else {
			$rs = $this->Patient_Model->update_patient($record, array('no'=>$nurse_no),'patient_nurse');
		}

		if($rs) {
			return_json(true, '저장되었습니다.', array('no'=>$patient_no, 'mode'=>$mode));
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 수술간호일지 등록/수정
	 * @return [type] [description]
	 */
	function save_patient_doctor() {
		$mode = $this->param['mode'];
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$doctor_no = $this->param['doctor_no'];

		$record = array(
			'doctor_id'=>$this->param['doctor_id'],
			'nurse_id'=>$this->param['nurse_id'],
			'manager_team_code'=>$this->param['manager_team_code'],
			'manager_id'=>$this->param['manager_id'],
			'treat_info'=>$this->param['treat'],
			'comment'=>htmlspecialchars($this->param['comment'], ENT_QUOTES),
			'date_doctor'=>$this->param['date_doctor']
		);

		if($mode == 'insert') {
			$record['patient_no'] = $patient_no;
			$record['project_no'] = $project_no;
			$record['writer_id'] = $this->session->userdata('ss_user_id');
			$record['date_insert'] = NOW;
			$rs = $this->Patient_Model->insert_patient($record, 'patient_doctor');
		}
		else {
			$rs = $this->Patient_Model->update_patient($record, array('no'=>$doctor_no),'patient_doctor');
		}

		if($rs) {
			return_json(true, '저장되었습니다.', array('no'=>$patient_no, 'mode'=>$mode));
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 차트등록
	 * @return [type] [description]
	 */
	function save_patient_chart() {

		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$subject = $this->param['subject'];

		//첨부파일
		$files = array();
		if(is_array($_FILES)) {
			$attach = $_FILES['chart'];
			foreach($attach as $field=>$file) {
				foreach($file as $idx=>$value) {
					$files[$idx][$field] = $value;
				}
			}
		}

		$_FILES = $files;

		$upload_path = $this->dir.$patient_no.'/chart';

		if(!is_dir($upload_path))  @mkdir($upload_path, 0777, true);
		$config['upload_path'] = $upload_path;
		$config['encrypt_name'] = TRUE;
		$config['allowed_types'] = '*';

		$this->load->library('upload', $config);
		$this->upload->initialize($config);

		$failure = false;
		foreach( $files as $key => $val ){
			if ( !$this->upload->do_upload($key) ) {
				$error = array('error' => $this->upload->display_errors());
			}
			else {
				$upload_data = $this->upload->data();
				$rs_resize = $this->resize_image(array('width'=>100, 'height'=>150, 'source_image'=>$upload_data['full_path']));
				$thumbnail = ($rs_resize)?$upload_data['raw_name'].'_thumb'.$upload_data['file_ext']:$upload_data['file_name'];

				$dir = str_replace($_SERVER['DOCUMENT_ROOT'],'',$upload_data['file_path']);

				$record = array(
					'patient_no' => $patient_no,
					'project_no' => $project_no,
					'subject' => $subject,
					'kind'=>$this->param['kind'],
					'times'=>$this->param['times'],
					'file_name' => $upload_data['orig_name'],
					'file_path' => $dir.$upload_data['file_name'],
					'file_path_thumbnail' => $dir.$thumbnail,
					'writer_id' => $this->session->userdata('ss_user_id'),
					'date_insert' => NOW,
					'date_chart'=>$this->param['date_chart']
				);

				$rs = $this->Patient_Model->insert_patient( $record,'patient_chart');
				if(!$rs) $failure=true;
			}
		}

		if(!$failure){
			return_json(true);
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}

	function resizing() {
		$photo = $this->Patient_Model->select_patient_all(array('is_delete'=>'N'),'patient_photo');
		// pre($photo);
		foreach($photo as $row) {

			$path = $_SERVER['DOCUMENT_ROOT'].$row['file_path'];
			if(!is_file($path)) continue;
			$size = getimagesize($path);

			if($size[0]>1000 || $size[1] > 700) {
				echo $path." resized!<br />";
				$this->resize_image(array('width'=>1000, 'height'=>700, 'create_thumb'=>FALSE, 'source_image'=>$path));
			}
		}
	}

	/**
	 * 챠트삭제
	 * @return [type] [description]
	 */
	function remove_patient_chart() {
		$patient_no = $this->param['patient_no'];
		$chart_no = $this->param['chart_no'];
		$chart_info = $this->Patient_Model->select_patient_row(array('no'=>$chart_no), 'patient_chart','file_path, file_path_thumbnail');

		//db삭제
		$rs = $this->Patient_Model->update_patient(array('is_delete'=>'Y'), array('no'=>$chart_no),'patient_chart');
		if($rs) {
			//파일삭제
			//@unlink($_SERVER['DOCUMENT_ROOT'].$chart_info['file_path']);
			//@unlink($_SERVER['DOCUMENT_ROOT'].$chart_info['file_path_thumbnail']);
			return_json(true);
		}

		else {
			return_json(false,'잠시 후에 다시 시도해 주세요');
		}
	}

	/**
	 * 사진등록
	 * @return [type] [description]
	 */
	function save_patient_photo() {

		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$kind = $this->param['kind'];
		$times = $this->param['times'];

		//첨부파일
		$files = array();
		if(is_array($_FILES)) {
			$attach = $_FILES['photo'];
			foreach($attach as $field=>$file) {
				foreach($file as $idx=>$value) {
					$files[$idx][$field] = $value;
				}
			}
		}

		$_FILES = $files;

		$upload_path = $this->dir.$patient_no.'/photo';
		if(!is_dir($upload_path)) @mkdir($upload_path, 0777, true);
		$config['upload_path'] = $upload_path;
		$config['encrypt_name'] = TRUE;
		$config['allowed_types'] = '*';

		$this->load->library('upload', $config);
		$this->upload->initialize($config);

		$failure = false;
		foreach( $files as $key => $val ){
			if ( !$this->upload->do_upload($key) ) {
				$error = array('error' => $this->upload->display_errors());
			}
			else {
				$upload_data = $this->upload->data();

				//썸네일
				$rs_thumbnail = $this->resize_image(array('width'=>100, 'height'=>100, 'source_image'=>$upload_data['full_path']));
				$thumbnail_name = ($rs_thumbnail)?$upload_data['raw_name'].'_thumb'.$upload_data['file_ext']:$upload_data['file_name'];

				//리사이징(최대:가로1000px)
				// pre($upload_data);
				if($upload_data['image_width']>1000) {
					$this->resize_image(array('width'=>1000, 'height'=>700, 'create_thumb'=>FALSE, 'source_image'=>$upload_data['full_path']));
				}

				$dir = str_replace($_SERVER['DOCUMENT_ROOT'],'',$upload_data['file_path']);
				$record = array(
					'patient_no' => $patient_no,
					'project_no' => $project_no,
					'kind'=>$kind,
					'times' => $times,
					'file_name' => $upload_data['orig_name'],
					'file_path' => $dir.$upload_data['file_name'],
					'file_path_thumbnail' => $dir.$thumbnail_name,
					'writer_id' => $this->session->userdata('ss_user_id'),
					'date_photo'=>$this->param['date_photo'],
					'date_insert' => NOW
				);

				$rs = $this->Patient_Model->insert_patient( $record,'patient_photo');
				if(!$rs) $failure=true;
			}
		}

		if(!$failure){
			return_json(true);
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}


	/**
	 * 전/후 사진삭제
	 * @return [type] [description]
	 */
	function remove_patient_photo() {

		$patient_no = $this->param['patient_no'];
		$photo_no = $this->param['photo_no'];
		$photo_info = $this->Patient_Model->select_patient_row(array('no'=>$photo_no), 'patient_photo','file_path, file_path_thumbnail');

		//db삭제
		$rs = $this->Patient_Model->remove_patient(array('no'=>$photo_no),'patient_photo');
		if($rs) {
			//파일삭제
			@unlink($_SERVER['DOCUMENT_ROOT'].$photo_info['file_path']);
			@unlink($_SERVER['DOCUMENT_ROOT'].$photo_info['file_path_thumbnail']);
			return_json(true);
		}

		else {
			return_json(false,'잠시 후에 다시 시도해 주세요');
		}
	}

	/**
	 * 동의서등록
	 * @return [type] [description]
	 */
	function save_patient_agree() {

		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$category = $this->param['category'];
		$failure = false;

		if($this->param['mode'] == 'update') {
			$record = array(
				'category' => $category
			);
			$where = array(
				'no'=>$this->param['agree_no']
			);
			$rs = $this->Patient_Model->update_patient( $record, $where, 'patient_agree');
			if(!$rs) $failure=true;
		}
		else {
			//첨부파일
			$files = array();
			if(is_array($_FILES)) {
				$attach = $_FILES['agree'];
				foreach($attach as $field=>$file) {
					foreach($file as $idx=>$value) {
						$files[$idx][$field] = $value;
					}
				}
			}

			$_FILES = $files;


			$upload_path = $this->dir.$patient_no.'/agree';
			if(!is_dir($upload_path)) @mkdir($upload_path, 0777, true);
			$config['upload_path'] = $upload_path;
			$config['encrypt_name'] = TRUE;
			$config['allowed_types'] = '*';

			$this->load->library('upload', $config);
			$this->upload->initialize($config);


			foreach( $files as $key => $val ){
				if ( !$this->upload->do_upload($key) ) {
					$error = array('error' => $this->upload->display_errors());
				}
				else {
					$upload_data = $this->upload->data();

					$rs_resize = $this->resize_image(array('width'=>100, 'height'=>100, 'source_image'=>$upload_data['full_path']));
					$photo = ($rs_resize)?$upload_data['raw_name'].'_thumb'.$upload_data['file_ext']:$upload_data['file_name'];

					$dir = str_replace($_SERVER['DOCUMENT_ROOT'],'',$upload_data['file_path']);
					$record = array(
						'patient_no' => $patient_no,
						'project_no' => $project_no,
						'category' => $category,
						'file_name' => $upload_data['orig_name'],
						'file_path' => $dir.$upload_data['file_name'],
						'file_path_thumbnail' => $dir.$photo,
						'writer_id' => $this->session->userdata('ss_user_id'),
						'date_insert' => NOW
					);

					$rs = $this->Patient_Model->insert_patient( $record,'patient_agree');
					if(!$rs) $failure=true;
				}
			}
		}



		if(!$failure){
			return_json(true);
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요.');
		}
	}


	/**
	 * 동의서삭제
	 * @return [type] [description]
	 */
	function remove_patient_agree() {

		$patient_no = $this->param['patient_no'];
		$agree_no = $this->param['agree_no'];
		$agree_info = $this->Patient_Model->select_patient_row(array('no'=>$photo_no), 'patient_agree','file_path, file_path_thumbnail');

		//db삭제
		$rs = $this->Patient_Model->remove_patient(array('no'=>$agree_no),'patient_agree');
		if($rs) {
			//파일삭제
			@unlink($_SERVER['DOCUMENT_ROOT'].$photo_info['file_path']);
			@unlink($_SERVER['DOCUMENT_ROOT'].$photo_info['file_path_thumbnail']);
			return_json(true);
		}

		else {
			return_json(false,'잠시 후에 다시 시도해 주세요');
		}
	}

	/**
	 * 환자삭제
	 * @return [type] [description]
	 */
	function remove_patient() {
		$patient_no = $this->param['patient_no'];
		$record = array(
			'is_delete'=>'Y'
		);

		$rs = $this->Patient_Model->update_patient($record, array('no'=>$patient_no));
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function save_patient_profile() {

		$patient_no = $this->param['patient_no'];

		$upload_path = $this->dir.$patient_no.'/profile';
		if(!is_dir($upload_path)) @mkdir($upload_path, 0777, true);
		$config['upload_path'] = $upload_path;
		$config['encrypt_name'] = TRUE;
		$config['allowed_types'] = '*';

		$this->load->library('upload', $config);
		$this->upload->initialize($config);
		if ( !$this->upload->do_upload('patient_photo') ) {
			return_json(false, $this->upload->display_errors());
		}
		else {
			$upload_data = $this->upload->data();
			$dir = str_replace($_SERVER['DOCUMENT_ROOT'],'',$upload_data['file_path']);

			//썸네일
			$rs_resize = $this->resize_image(array('width'=>150, 'height'=>175, 'create_thumb'=>FALSE, 'source_image'=>$upload_data['full_path']));
			$photo = $upload_data['file_name'];

			$record = array('photo'=>$dir.$photo);
			$rs = $this->Patient_Model->update_patient($record, array('no'=>$patient_no));
			if($rs){
				return_json(true);
			}
			else {
				return_json(false, '잠시 후에 다시 시도해주세요.');
			}
		}
	}

	function resize_image($config) {
		$this->load->library('image_lib');

		$config_default = array(
			'image_library' => 'gd2',
			'maintain_ratio' => TRUE,
			'create_thumb' => TRUE,
			'thumb_marker' => '_thumb',
			'width' => 150,
			'height' => 150
		);

		$config = array_merge($config_default, $config);

		$this->image_lib->clear();
		$this->image_lib->initialize($config);

		if (!$this->image_lib->resize()) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * 상담내역 삭제
	 * @return [type] [description]
	 */
	function remove_consulting() {
		$patient_no = $this->param['patient_no'];
		$consulting_no = $this->param['consulting_no'];

		$rs = $this->Patient_Model->remove_patient(array('no'=>$consulting_no), 'patient_consulting');
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 컨플레인내역 삭제
	 * @return [type] [description]
	 */
	function remove_complain() {
		$patient_no = $this->param['patient_no'];
		$complain_no = $this->param['complain_no'];

		$rs = $this->Patient_Model->update_patient(array('is_delete'=>'Y'), array('no'=>$complain_no), 'patient_complain');
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 치료일지 삭제
	 * @return [type] [description]
	 */
	function remove_treat() {
		$patient_no = $this->param['patient_no'];
		$treat_no = $this->param['treat_no'];

		$rs = $this->Patient_Model->remove_patient(array('no'=>$treat_no), 'patient_treat');
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 피부관리일지 삭제
	 * @return [type] [description]
	 */
	function remove_skin() {
		$patient_no = $this->param['patient_no'];
		$skin_no = $this->param['skin_no'];

		$rs = $this->Patient_Model->remove_patient(array('no'=>$skin_no), 'patient_skin');
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 수술간호일지 삭제
	 * @return [type] [description]
	 */
	function remove_nurse() {
		$patient_no = $this->param['patient_no'];
		$nurse_no = $this->param['nurse_no'];

		$rs = $this->Patient_Model->remove_patient(array('no'=>$nurse_no), 'patient_nurse');
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 수납내역 등록/수정
	 * @return [type] [description]
	 */
	function save_patient_pay() {
		$p = $this->param;
		$pay_no = $p['pay_no'];

		$this->load->library('Patient_lib');

		$record = array(
			'manager_team_code'=>$p['manager_team_code'],
			'manager_id'=>$p['manager_id'],
			'acceptor_id'=>$p['acceptor_id'],
			'doctor_id'=>$p['doctor_id'],
			'pay_type'=>$p['pay_type'],
			'calc_type'=>$p['calc_type'],
			'sales_type'=>$p['sales_type'],
			'receipt_type'=>$p['receipt_type'],
			'comment'=>$p['comment'],
			'date_paid'=>$p['date_paid']
		);

		if($p['pay_type'] == 'paid') {
			$record['amount_paid_cash']=str_replace(',','',$p['amount_paid_cash']);
			$record['amount_paid_cash_x']=str_replace(',','',$p['amount_paid_cash_x']);
			$record['amount_paid_cash_o'] = $record['amount_paid_cash']-$record['amount_paid_cash_x'];
			$record['amount_paid_card']=str_replace(',','',$p['amount_paid_card']);
			$record['amount_paid_bank']=str_replace(',','',$p['amount_paid_bank']);
			$record['amount_paid']=str_replace(',','',$p['amount_paid']);
			$record['bank_code']=$p['bank_code'];
			$record['card_code']=$p['card_code'];
		}
		else {
			$record['amount_refund_cash']=str_replace(',','',$p['amount_refund_cash']);
			$record['amount_refund_cash_x']=str_replace(',','',$p['amount_refund_cash_x']);
			$record['amount_refund_cash_o'] = $record['amount_refund_cash']-$record['amount_refund_cash_x'];
			$record['amount_refund_card']=str_replace(',','',$p['amount_refund_card']);
			$record['amount_refund_bank']=str_replace(',','',$p['amount_refund_bank']);
			$record['amount_refund']=str_replace(',','',$p['amount_refund']);
		}

		if($p['mode'] == 'insert') {
			$record['patient_no'] = $p['patient_no'];
			$record['project_no'] = $p['project_no'];
			$record['writer_id'] = $this->session->userdata('ss_user_id');
			$record['date_insert'] = NOW;
			$rs = $this->Patient_Model->insert_patient($record, 'patient_pay');
		}
		else {
			if(!$p['auth_save']) {
				$record = array(
					'manager_id'=>$p['manager_id'],
					'doctor_id'=>$p['doctor_id'],
					'comment'=>$p['comment']
				);
			}


			$rs = $this->Patient_Model->update_patient($record, array('no'=>$pay_no),'patient_pay');
		}

		if($rs) {
			$this->_sync_amount_pay($p['project_no']);
			return_json(true, '저장되었습니다.', array('no'=>$patient_no, 'mode'=>$mode));

		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 수납내역 삭제
	 * @return [type] [description]
	 */
	function remove_pay() {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$pay_no = $this->param['pay_no'];

		//$rs = $this->Patient_Model->remove_patient(array('no'=>$pay_no), 'patient_pay');
		$rs = $this->Patient_Model->update_patient(array('is_delete'=>'Y'), array('no'=>$pay_no), 'patient_pay');

		if($rs) {
			$this->_sync_amount_pay($project_no);
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}


	private function _sync_amount_pay($project_no) {
		$project_info = $this->Patient_Model->select_patient_row(array('no'=>$project_no), 'patient_project', 'patient_no, amount_basic');
		$sum_field = "SUM(amount_paid) AS paid_total,
			SUM(amount_paid_cash) AS paid_cash,
			SUM(amount_paid_card) AS paid_card,
			SUM(amount_paid_bank) AS paid_bank,
			SUM(amount_refund) AS refund_total,
			SUM(amount_refund_cash) AS refund_cash,
			SUM(amount_refund_card) AS refund_card,
			SUM(amount_refund_bank) AS refund_bank,

			SUM(IF(calc_type='addition', amount_paid, 0)) AS amount_addition";

		$sum = $this->Patient_Model->select_widget_row('patient_pay',array('project_no'=>$project_no, 'is_delete'=>'N'), $sum_field);

		$record = array(
			'amount_addition'=>$sum['amount_addition'], //추가매출액
			'amount_refund'=>$sum['amount_refund'], //환불액
			'amount_unpaid'=>($project_info['amount_basic']-$sum['paid_total']+$sum['amount_addition']+$sum['amount_refund']), //미수금
			'paid_total'=>($sum['paid_total']-$sum['refund_total']), //총수납액
			'paid_cash'=>($sum['paid_cash']-$sum['refund_cash']), //현금수납액
			'paid_card'=>($sum['paid_card']-$sum['refund_card']), //카드수납액
			'paid_bank'=>($sum['paid_bank']-$sum['refund_bank']) //계좌이체수납액
		);


		$this->Patient_Model->update_patient($record, array('no'=>$project_no), 'patient_project');
	

		//환자 총 매출액
		$this->sync_patient_grade($project_info['patient_no']);

		$this->sync_consulting($project_info['patient_no']);
	}

	public function temp() {
		
	}

	function sync_patient_grade($patient_no='') {
		$patient_no = ($patient_no)?$patient_no:$this->param['patient_no'];
		$sum = $this->Patient_Model->select_widget_row('patient_pay',array('patient_no'=>$patient_no, 'is_delete'=>'N'), "SUM(amount_paid) AS amount_paid");

		//총매출액쿼리
		$amount_paid = $sum['amount_paid'];
		$grade_no = $this->_calc_grade($amount_paid); //환자등급
		$record = array(
			'grade_no'=>$grade_no,
			'amount_paid'=>$amount_paid
		);


		$this->Patient_Model->update_patient($record, array('no'=>$patient_no), 'patient');
	}

	private function _sync_amount_material($project_no) {
		$sum_field = "SUM(use_count*goods_price) AS price_total";
  		$sum = $this->Patient_Model->select_widget_row('patient_material',array('project_no'=>$project_no), $sum_field);
  		$amount_material = round($sum['price_total'],-1);

  		$record = array(
			'amount_material'=>$amount_material
		);

		$this->Patient_Model->update_patient($record, array('no'=>$project_no), 'patient_project');
	}

	/**
	 * doctor's order 삭제
	 * @return [type] [description]
	 */
	function remove_doctor() {
		$p = $this->param;
		$doctor_no = $this->param['doctor_no'];

		$rs = $this->Patient_Model->remove_patient(array('no'=>$doctor_no), 'patient_doctor');
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function save_grade() {
		$p = $this->param;
		$grade_no = $p['no'];
		if($grade_no>0) {
			$record = array(
				'rgb'=>$p['rgb'],
				'comment'=>htmlspecialchars($p['name'],ENT_QUOTES),
			);

			if($p['is_fix'] == 'N') {
				$record['name'] = $p['name'];
				$record['is_use'] = $p['is_use'];
				$record['standard'] = $p['standard'];
			}

			$rs = $this->Patient_Model->update_patient($record, array('no'=>$grade_no),'patient_grade');
		}
		else {
			$record = array(
				'name'=>$p['name'],
				'is_use'=>$p['is_use'],
				'standard'=>$p['standard'],
				'rgb'=>$p['rgb'],
				'comment'=>htmlspecialchars($p['name'],ENT_QUOTES),
			);
			$rs = $this->Patient_Model->insert_patient($record,'patient_grade');

		}
		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 회원 등급 동기화
	 * @return [type] [description]
	 */
	function sync_grade() {
		$this->load->library('Patient_lib');
		$rs = $this->patient_lib->expect_grade(true);
		if($rs) {
			return_json(true,'등급이 적용되었습니다..');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function _get_grade($patient_no) {

	}

	/**
	 * 프로젝트 등록
	 * @return [type] [description]
	 */
	function save_patient_project() {
		$this->load->model('Treat_Model');
		$p = $this->param;
	
		if($p['type'] == 3) { //구분:진료
			$cost = array();
		}
		else {
			$treat_arr = explode(',',$p['treat_costs']);
			foreach($treat_arr as $treat_no) {
				$cost[] = $this->Treat_Model->select_cost_row(array('no'=>$treat_no));
			}
		}

		$record = array(
			'type'=>$p['type'],
			'doctor_id'=>$p['doctor_id'],
			'nurse_id'=>(is_array($p['nurse_id']))?implode(',',$p['nurse_id']):'',
			'manager_team_code'=>$p['manager_team_code'],
			'manager_id'=>$p['manager_id'],
			//'treat_cost_no'=>$p['treat_cost_no'],
			'treat_costs'=>$p['treat_costs'],
			'op_type'=>$p['op_type'],
			'tax_type'=>$p['tax_type'],
			'amount_basic'=>str_replace(',','',$p['amount_basic']),
			'amount_material'=>str_replace(',','',$p['amount_material']),
			'amount_refund'=>str_replace(',','',$p['amount_refund']),
			'cost'=>($p['type'] == 3)?'0':$p['cost'],
			'cost_info'=>serialize($cost),
			'comment'=>htmlspecialchars($p['comment'], ENT_QUOTES),
			'date_project'=>$p['date_project'],
			'consulting_sales_yn'=>$p['consulting_sales_yn']				// 20170308 kruddo : 상담매출여부
		);

		$record['discount_amount'] = ($record['cost']>0)?$record['cost']-$record['amount_basic']:'0';
		$record['discount_rate'] = round($record['discount_amount']/$record['cost']*100,2);
		$record['amount_op'] = $record['amount_basic']-$record['amount_material']-$record['amount_refund'];
	
		if($p['mode'] == 'insert') {
			$record['patient_no'] = $p['patient_no'];
			$record['date_insert'] = NOW;
			$record['amount_unpaid'] = $record['amount_basic']; //초기등록시 미수금은 매출액과 같음

			$rs = $this->Patient_Model->insert_patient($record,'patient_project');

		}
		else {
			$record['amount_unpaid'] = $record['amount_basic']-$p['paid_total']; //초기등록시 미수금은 매출액과 같음

			$where = array(
				'no'=>$p['project_no']
			);
			$rs = $this->Patient_Model->update_patient($record, $where, 'patient_project');
		}

		if($rs) {
			//총매출액
			if($p['patient_no'] > 0) {
				$this->load->model('Consulting_Model');
				$sum = $this->Patient_Model->select_patient_row(array('patient_no'=>$p['patient_no']), 'patient_project', 'SUM(amount_basic) AS basic, SUM(amount_addition) AS addition');
				$patient_sales = $sum['basic'] + $sum['addition'];
				$this->Consulting_Model->update_consulting(array('patient_sales'=>$patient_sales), array('patient_no'=>$p['patient_no']));
			}

			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function remove_patient_project() {
		$patient_no = $this->param['patient_no'];
		$project_no = $this->param['project_no'];
		$rs = $this->Patient_Model->update_patient(array('is_delete'=>'Y'), array('no'=>$project_no), 'patient_project');
		if($rs) {
			$tbl_arr = array('chart','pay','doctor','nurse','treat','consulting','photo','material','agree');
			foreach($tbl_arr as $tbl) {
				$this->Patient_Model->update_patient(array('is_delete'=>'Y'), array('project_no'=>$project_no), 'patient_'.$tbl);
			}

			//등급동기화
			$this->sync_patient_grade($patient_no);

			//매출동기화
			$this->sync_consulting($patient_no);
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 물품기록지
	 * @return [type] [description]
	 */
	function save_patient_material() {
		$p = $this->param;

		/*
		if($p['mode'] == 'insert') {
			$goods_info = array(
				'group_code'=>$p['group_code'],
				'classify_name'=>$p['classify_name'],
				'item_name'=>$p['item_name']
			);
			$record = array(
				'patient_no'=>$p['patient_no'],
				'project_no'=>$p['project_no'],
				'writer_id'=>$this->session->userdata('ss_user_id'),
				'goods_no'=>$p['no'],
				'goods_name'=>$p['goods_name'],
				'goods_price'=>str_replace(',','',$p['unit_price']),
				'goods_info'=>serialize($goods_info),
				'use_count'=>$p['use_count']['int'].'.'.$p['use_count']['decimal'],
				'comment'=>htmlspecialchars($p['comment'], ENT_QUOTES),
				'date_insert'=>NOW
			);
			$rs = $this->Patient_Model->insert_patient($record, 'patient_material');
		}
		else {
			$record = array(
				'use_count'=>$p['use_count']['int'].'.'.$p['use_count']['decimal'],
				'comment'=>htmlspecialchars($p['comment'], ENT_QUOTES)
			);
			$where = array(
				'no'=>$p['material_no']
			);
			$rs = $this->Patient_Model->update_patient($record, $where, 'patient_material');
			// echo $this->db->last_query();
		}
		*/

		$goods_info = array();
		$goods_code = $p['goods_code'];
		$goods_name = $p['goods_name'];
		$goods_count = $p['goods_count'];
		$goods_unit_price = $p['goods_unit_price'];
		$classify_name = $p['classify_name'];
		$item_name = $p['item_name'];

		$goods_unit_total_price = $p['goods_unit_total_price'];
		$category_subject = $p['category_subject'];

		$goods_names = '';
		$goods_no = '';
		$goods_cnt = 1;

		//return_json(true,'저장되었습니다.', $goods_unit_total_price);
		for( $i = 0 ; $i < count($goods_code) ; $i++ ){
			if( $goods_code[$i] ){

				$goods_names = $goods_names?$goods_names:$goods_name[$i].' 외 '.count($goods_code);
				$goods_no = $goods_no?$goods_no:$goods_code[$i];
				//$goods_cnt += $goods_count[$i];

				//$goods_names = $goods_name[$i];
				//$goods_no = $goods_code[$i];

				$goods = array(
					'goods_code' => $goods_code[$i],
					'goods_name' => $goods_name[$i],
					'goods_count' => $goods_count[$i],
					'goods_unit_price' => $goods_unit_price[$i],
					'goods_unit_total_price' => $goods_unit_total_price[$i],
					'classify_name' => $classify_name[$i],
					'item_name' => $item_name[$i],
				);
			}
			$goods_info[] = $goods;
		}
		//return_json(true,'저장되었습니다.', $goods_info);

		$record = array(
			'patient_no'=>$p['patient_no'],
			'project_no'=>$p['project_no'],
			'writer_id'=>$this->session->userdata('ss_user_id'),
			'goods_no'=>$goods_no, //$p['no'],
			'goods_name'=>$goods_names,//$p['goods_name'],
			'goods_price'=>str_replace(',','',$p['goods_total_price']),
			'goods_info'=>serialize($goods_info),
			'use_count'=>$goods_cnt,//$p['use_count']['int'].'.'.$p['use_count']['decimal'],
			//'comment'=>htmlspecialchars($p['comment'], ENT_QUOTES),
			'date_insert'=>$p['use_date'],//NOW
			'category_subject'=>$p['category_subject'],
			'kind'=>$p['kind'],
		);


		if($p['mode'] == 'insert') {
			$rs = $this->Patient_Model->insert_patient($record, 'patient_material');
		}
		else {

			$where = array(
				'no'=>$p['material_no']
			);
			$rs = $this->Patient_Model->update_patient($record, $where, 'patient_material');
			// echo $this->db->last_query();
		}

		//$rs = $this->Patient_Model->insert_patient($record, 'patient_material');

		if($rs) {
			$this->_sync_amount_material($p['project_no']);
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function remove_patient_material() {
		$p = $this->param;
		$material_no = $p['material_no'];

		//db삭제
		$rs = $this->Patient_Model->remove_patient(array('no'=>$material_no),'patient_material');
		if($rs) {
			$this->_sync_amount_material($p['project_no']);
			return_json(true);
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요');
		}
	}

	function save_patient_manager() {
		$p = $this->param;
		$patient_no = $p['patient_no'];
		$record = array(
			'manager_team_code'=>$p['manager_team_code'],
			'manager_id'=>$p['manager_id']
		);

		$rs = $this->Patient_Model->update_patient($record, array('no'=>$patient_no));
		if($rs) {
			$tbl_arr = array('doctor','consulting','project','pay', 'appointment');
			foreach($tbl_arr as $tbl) {
				$this->Patient_Model->update_patient($record, array('patient_no'=>$patient_no), 'patient_'.$tbl);
			}
			return_json(true,'변경되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요');
		}
	}

	function save_patient_favorite() {
		$patient_no = $this->param['patient_no'];

		$patient_info = $this->Patient_Model->select_patient_row(array('no'=>$patient_no));
		$favorite_arr = explode(',',$patient_info['favorite_user']);
		$user_no = $this->session->userdata('ss_user_no');

		if($this->param['favorite'] == 'Y') {
			array_push($favorite_arr, $user_no);
		}
		else {
			$key = array_search($user_no, $favorite_arr);
			unset($favorite_arr[$key]);
		}

		$favorite_arr = array_unique(array_filter($favorite_arr));
		$record = array(
			'favorite_user'=>implode(',',$favorite_arr)
		);
		$where = array('no'=>$patient_no);

		$rs = $this->Patient_Model->update_patient($record, $where);
		if($rs) {
			return_json(true,'변경되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요');
		}
	}

	function sync_consulting($patient_no) {
		if(!$patient_no) {
			return false;
		}
		$this->load->model('Consulting_Model');

		$patient = $this->Patient_Model->select_patient_all(array('no'=>$patient_no), 'patient','no, name, cst_seqno');


		foreach($patient as $row) {
			$patient_no = $row['no'];
			$cst_seqno = $row['cst_seqno'];

			$sum = $this->Patient_Model->select_patient_row(array('patient_no'=>$patient_no, 'is_delete'=>'N'), 'patient_project', 'SUM(amount_basic) AS basic, SUM(amount_addition) AS addition, SUM(paid_total) AS paid, SUM(amount_refund) AS refund');
			//echo $this->db->last_query();
			//echo 'patient_no : '.$patient_no;
			//pre($sum);



			$patient_sales = $sum['basic'] + $sum['addition'];
			$patient_paid = (int)$sum['paid'];
			$refund = (int)$sum['refund'];
			//$refund =
			$this->Consulting_Model->update_consulting(array('patient_sales'=>$patient_sales, 'patient_paid'=>$patient_paid), array('patient_no'=>$patient_no));

			//echo $this->db->last_query();


			// $this->Consulting_Model->update_consulting(array('patient_no'=>$patient_no), array('cst_seqno'=>$cst_seqno));
		}
	}

	function sync_consulting_init() {
		exit;
		$this->load->model('Consulting_Model');
		$patient = $this->Patient_Model->select_patient_all(array('cst_seqno > '=>0), 'patient','no, name, cst_seqno');

		foreach($patient as $row) {
			$patient_no = $row['no'];
			$cst_seqno = $row['cst_seqno'];

			$sum = $this->Patient_Model->select_patient_row(array('patient_no'=>$patient_no), 'patient_project', 'SUM(amount_basic) AS basic, SUM(amount_addition) AS addition, SUM(paid_total) AS paid');
			//echo 'patient_no : '.$patient_no;
			//pre($sum);



			$patient_sales = $sum['basic'] + $sum['addition'];
			$patient_paid = $sum['paid'];
			$this->Consulting_Model->update_consulting(array('patient_sales'=>$patient_sales, 'patient_paid'=>$patient_paid), array('patient_no'=>$patient_no));


			// $this->Consulting_Model->update_consulting(array('patient_no'=>$patient_no), array('cst_seqno'=>$cst_seqno));
		}
	}


}
