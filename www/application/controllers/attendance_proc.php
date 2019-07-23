<?php
/**
 * 근태관리 Process
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Attendance_proc extends CI_Controller {

	public function __construct() {
		parent::__construct();
		
		$this->param = $this->input->post(NULL, true);

		$this->load->model(array('User_model','Attendance_Model'));
		
		// $this->yield = TRUE;
	}

	/**
	 * 근태설정값 가져오기
	 * @return [type] [description]
	 */
	public function settings_get($type='json') {
		$dept_code = $this->input->post('dept_code');
		if($dept_code) {
			$where['dept_code'] = $dept_code;
		}

		$where['status']='1';

		$team_list = $this->User_model->get_team($where);
		if(count($team_list)>0) {
			$setting_info = array();
			foreach($team_list as $team) {
				list($team['time_in_hour'],$team['time_in_minute']) = explode(':',$team['time_in']);
				list($team['time_out_hour'],$team['time_out_minute']) = explode(':',$team['time_out']);
				list($team['time_night_hour'],$team['time_night_minute']) = explode(':',$team['time_night']);
				$team['days_work'] = explode(',',$team['days_work']);
				$setting_info[$team['team_code']] = $team;
			}
			$success = true;
			$return_data = $setting_info;
		}
		else {
			$success = false;
			$return_data = null;
		}


		if($type == 'json') {
			return_json($success, '',$return_data);
		}
		else {
			return $return_data;
		}
	}

	/**
	 * 근태설정값 저장
	 * @return [type] [description]
	 */
	public function settings_save() {
		
		$set = $this->param['set'];
		$success = true;
		if(is_array($set)) {
			foreach($set as $team_code => $info) {
				$datum = array(
					'time_in'=>$info['time_in_hour'].':'.$info['time_in_minute'],
					'time_out'=>$info['time_out_hour'].':'.$info['time_out_minute'],
					'time_night'=>$info['time_night_hour'].':'.$info['time_night_minute'],
					'use_night'=>($info['use_night'])?$info['use_night']:'N',
					'days_work'=>(is_array($info['days_work']))?implode(',',array_keys($info['days_work'])):'',
					'use_statistics'=>($info['use_statistics'])?$info['use_statistics']:'N'
				);
				$where = array(
					'team_code'=>$team_code
				);
				$rs = $this->Attendance_Model->update_settings($datum, $where);
				if(!$rs) $success=false;
			}
		}

		if($success) {
			return_json(true,'설정이 저장되었습니다');
		}
		else {
			return_json(false,'설정에 실패하였습니다.');
		}
	}

	/**
	 * 근태기록 상태변경
	 * @return json
	 */
	function change_status() {
		$where['no'] = $this->param['logs_no'];
		$record = array(
			'status'=>$this->param['status_code']
		);
		$rs = $this->Attendance_Model->update_logs($record, $where);
		if($rs) {
			return_json(true,'상태가 변경되었습니다.');
		}
		else {
			return_json(false,'상태변경에 실패하였습니다.');
		}
	}

	/**
	 * 근태기록 삭제
	 * @return json
	 */
	function remove_logs() {
		$where['no'] = $this->param['logs_no'];
		$rs = $this->Attendance_Model->delete_logs($where);
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'삭제에 실패하였습니다.');
		}
	}

	/**
	 * 근무시간, 야간근무시간
	 * @param  integer $status 출근상태
	 * @param  date $date 날짜(0000-00-00)
	 * @param  time $time_in 출근시각(00:00:00)
	 * @param  time $time_out 퇴근시각(00:00;00)
	 * @param  string $is_overnight 익일퇴근여부(Y/N)
	 * @param  string $standard_night 야간근무기준시각
	 * @return array                 [description]
	 */
	private function _calc_working_hour($status, $date, $time_in, $time_out, $is_overnight, $standard_night) {
	
		//총근무시간, 야간근무시간 계산
		if(substr($status,0,1)==3) { //30~35는 근무시간 0 
			$hour_working=0;
			$hour_working_night=0;
			$time_in = '';
			$time_out = '';
		}
		else {
			$mk_in = strtotime($date.' '.$time_in);
			$mk_out = strtotime($date.' '.$time_out);
			if($is_overnight == 'Y') $mk_out = strtotime('+1 day', $mk_out);
			$hour_working = floor(($mk_out-$mk_in-3600)/60); //총근무시간
			if($this->param['is_night'] == 'Y') {
				$mk_night = strtotime($date.' '.$standard_night);
				$hour_working_night = ($mk_out-$mk_night)/60;
			}
			else {
				$hour_working_night = 0;	
			}
		}

		return array('time_in'=>$time_in,'time_out'=>$time_out, 'hour_working'=>$hour_working, 'hour_working_night'=>$hour_working_night);
	}

	/**
	 * 근태데이터 변경
	 * @return [type] [description]
	 */
	function update_logs() {
		$where = array('no'=>$this->param['no']);

		//총근무시간, 야간근무시간 계산
		$hour = $this->_calc_working_hour($this->param['status'], $this->param['date'], $this->param['time_in'], $this->param['time_out'], $this->param['is_overnight'], $this->param['standard_night']);
		
		$record = array(
			'status'=>$this->param['status'],
			'time_in'=>$hour['time_in'],
			'time_out'=>$hour['time_out'],
			'is_late'=>isset($this->param['is_late'])?$this->param['is_late']:'N',
			'is_earlyleave'=>isset($this->param['is_earlyleave'])?$this->param['is_earlyleave']:'N',
			'is_night'=>isset($this->param['is_night'])?$this->param['is_night']:'N',
			'hour_working'=>$hour['hour_working'],
			'hour_working_night'=>$hour['hour_working_night'],
			'comment'=>$this->param['comment']
		);
		$rs = $this->Attendance_Model->update_logs($record, $where);
		if($rs) {
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}


	function insert_logs() {
		$users = $this->count_miss('array');
		if($users['count'] < 1) {
			return_json(false,'등록할 근태미등록 직원이 없습니다.');
		}

		$settings_info = $this->settings_get('array');
		$param = $this->param;
		$rc = array(
			'date'=>$param['date'],
			'status'=>$param['status'],
			'time_in'=>$param['time_in'],
			'time_out'=>$param['time_out'],
			'is_late'=>(isset($param['is_late']))?$param['is_late']:'N',
			// 'is_night'=>(isset($param['is_night']))?$param['is_night']:'N',
			'is_overnight'=>(isset($param['is_overnight']))?$param['is_overnight']:'N',
			'hour_working'=>$hour_working,
			'hour_working_night'=>$hour_working_night,
		);


		$success = $fauilre = 0;		
		foreach($users['list'] as $user) {
			$user_info = $this->User_model->get_info(array('user_id'=>$user), 'user_id, dept_code, team_code, position_code');

			$hour = $this->_calc_working_hour($rc['status'], $rc['date'], $rc['time_in'], $rc['time_out'], $rc['is_overnight'], $settings_info[$user_info['team_code']]['time_night']);		
			$rc['is_night'] = ($hour['hour_working_night']>0)?'Y':'N'; //야간근무여부
	
			$record = array_merge($rc, $user_info, $hour);
			$rs = $this->Attendance_Model->insert_logs($record);
			if($rs) $success++;
			else $failure++;
		}

		if($success > 0) {
			return_json(true, "근태기록이 입력되었습니다.\n\n성공:".number_format($success)."건 /  실패:".number_format($failure)."건");
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	/**
	 * 근태 일괄 데이터 업로드
	 * @return [type] [description]
	 */
	function batch_upload() {
		$config['upload_path'] = "./DATA/temp/";	    
	    $config['encrypt_name'] = TRUE;
	    $config['allowed_types'] = '*'; //application/vnd.ms-excel
		
		// pre($_FILES);
		$this->load->library('upload', $config);
		if ($this->upload->do_upload('excel_file')) {
			$upload_data = $this->upload->data();
			$this->load->library('Excel_lib');
			$obj = PHPExcel_IOFactory::load($upload_data['full_path']);
			
			$sheet_data = $obj->getActiveSheet()->toArray(null,true,true,true);
			array_shift($sheet_data);

			$return = array(
				'file_name'=>$upload_data['orig_name'],
				'file_full_path'=>$upload_data['full_path'],
				'data_count'=>count(array_filter($sheet_data))
			);

			return_json(true,'', $return);
		}	
		else {
			return_json(false,'파일 업로드에 실패하였습니다.\n'.$this->upload->display_errors());
		}
	}



	/**
	 * 근태기록 일괄등록
	 * @return [type] [description]
	 */
	function batch_save() {
		$path = $this->param['file_path'];

		$this->load->library('Excel_lib');
		// $path = '/home/hjlee/html/hmis/www/DATA/temp/b9b5a0b5519d4316a2123c9192b60f0f.xls';
		$obj = PHPExcel_IOFactory::load($path);
		$sheet_data = $obj->getActiveSheet()->toArray(null,true,true,true);

		//근태 설정값 로드
		$settings_info = $this->settings_get('array');
		$success = $failure = 0;
		array_shift($sheet_data);
		foreach($sheet_data as $k=>$v) {
			$user_id = $v['A'];
			$date = str_replace('/','-',$v['B']);
			$user_info = $this->User_model->get_info(array('user_id'=>$user_id), 'team_code, dept_code, position_code');

			if(!$user_info) continue; //직원정보가 없는경우
			$settings = $settings_info[$user_info['team_code']];
			

			$w = date('w', strtotime($date));
			$is_workingday = (in_array($w, $settings['days_work']))?'Y':'N';
			if(empty($v['C']) && empty($v['D'])) { //출퇴근시간이 없는경우
				$status = ($is_workingday == 'Y')?'30':'35'; //결근 or 휴일
				$data_missing = 'time_in, time_out';
				$record = array(
					'date'=>$date,
					'dept_code'=>$user_info['dept_code'],
					'team_code'=>$user_info['team_code'],
					'position_code'=>$user_info['position_code'],
					'user_id'=>$user_id,
					'status'=>$status
				);
			}
			else {
				$status = ($is_workingday == 'Y')?'10':'11'; //출근 or 휴일근무

				$time_in = $v['C'].':00';
				$time_out = $v['D'].':00';
				$data_missing = '';
				if(empty($v['C'])) { //출근시간이 없는경우
					$time_in = $time_out;
					$data_missing = 'time_in';

				}
				if(empty($v['D'])) { //퇴근시간이 없는경우
					$time_out = $time_in;
					$data_missing = 'time_out';
				}


				$is_overnight = (strrpos($time_out,'+')===false)?'N':'Y';

				$time_out = str_replace('+','',$time_out);
				$working_begin = strtotime("{$date} {$time_in}");//업무시작 timestamp
				$working_end = strtotime("{$date} {$time_out}"); //업무종료 timestamp

				if($is_overnight=='Y') $working_end = strtotime('+1 day', $working_end);// 익일퇴근시 업무종료 timestamp 재설정

				$is_late = ($settings['time_in'] < $time_in)?'Y':'N'; //지각여부
				$is_earlyleave = ($settings['time_out'] > $time_out && $is_overnight!='Y')?'Y':'N'; //조퇴여부

				//야간근무계산(야간근무 설정(Y)시)
				if($settings['use_night'] == 'Y') {
					if($is_overnight == 'Y') $is_night = 'Y';
					else $is_night = ($settings['time_night'] < $time_out)?'Y':'N';				
				}
				else $is_night='N';	

				if($is_night=='Y') {
					$standard_night = strtotime($date.' '.$settings['time_night']);
					$standard_out = strtotime($date.' '.$settings['time_out']);
					if($working_end > $standard_night) { //야간근무기준시각보다 큰경우 야간근무시간을 계산함
						$hour_working_night = floor(($working_end-$standard_out)/60);
					}
					else $hour_working_night = 0;
				}
				else {
					$hour_working_night = 0;
				}

				$hour_working = floor(($working_end-$working_begin-3600)/60); //총근무시간(퇴근시각-출근시각-점심시각(1시간=3600초)), 분단위
			

				$record = array(
					'date'=>$date,
					'dept_code'=>$user_info['dept_code'],
					'team_code'=>$user_info['team_code'],
					'position_code'=>$user_info['position_code'],
					'user_id'=>$user_id,
					'status'=>$status,
					'time_in'=>$time_in,
					'time_out'=>$time_out,
					'is_late'=>$is_late,
					'is_night'=>$is_night,
					'is_overnight'=>$is_overnight,
					'is_earlyleave'=>$is_earlyleave,
					'hour_working'=>$hour_working,
					'hour_working_night'=>$hour_working_night
				);
			}

			$record['date_insert'] = date('Y-m-d H:i:s');
			$record['data_raw'] = implode(',',array_filter($v));
			$record['data_missing'] = $data_missing;

			$rs = $this->Attendance_Model->insert_logs($record);
			if($rs) $success++;
			else $failure++;
		
		} 

		return_json(true,"처리되었습니다.\n처리결과 성공 : {$success}건, 실패:".$failure."건");
	}

	function count_miss($type='json') {
		$date = $this->input->post('date');

		$logs = $this->Attendance_Model->select_logs_row(array('date'=>$date), 'GROUP_CONCAT(user_id) AS user_id');
		$logs_user = explode(',',$logs['user_id']);

		$rs = $this->User_model->get_info(array('status'=>'1', 'join_date <='=>$date), 'GROUP_CONCAT(user_id) AS user_id');
		$user = explode(',',$rs['user_id']);
		
		$gap = array_diff($user, $logs_user);

		$return_data = array('count'=>count($gap), 'list'=>array_values($gap));
		if($type == 'json') {
			return_json(true, '',$return_data);
		}
		else {
			return $return_data;
		}
	}

	function get_statistics() {
		$date_s = $this->input->post('date_s');
		$date_e = $this->input->post('date_e');
		$type = $this->input->post('type');


		
		switch($type) {
			case 'team':
				$group_by = "team_code";
				$x_list = $this->User_model->get_team_list();
			break;
			case 'dept':
				$group_by = "dept_code";
				$x_list = $this->User_model->get_dept_list();
			break;
			case 'position':
				$group_by = "position_code";
				$x_list = $this->config->item( 'position_code' );
			break;
		}

		if($date_s) {$where['date >= ']=$date_s;}
		if($date_e) {$where['date <= ']=$date_e;}
		$where["{$group_by} is not null"] = '';
		$where["{$group_by} > 0"] = '';


		$field = "{$group_by}, sum(if(is_late = 'Y', 1, 0)) AS is_late, sum(if(is_earlyleave = 'Y', 1, 0)) AS is_earlyleave, sum(if(is_night = 'Y', 1, 0)) AS is_night";
		$rs = $this->Attendance_Model->select_logs_groupby($field, $where, $group_by);
		if(!$rs) {
			return_json(false,'');
		}



		
		$field_list = array(
			'is_late'=>'지각',
			'is_earlyleave'=>'조퇴',
			'is_night'=>'야간근무'
		);

		foreach($rs as $k=>$v) {
			if(!array_key_exists($k, $x_list)) continue;
			$x[] = $x_list[$k];
			foreach($v as $field=>$value) {
				if($field == $group_by) continue;
				$data[$field][] = $value; 	
			}			
		}

		foreach($data as $field=>$d) {
			$series[$field]['name'] = $field_list[$field];
			$series[$field]['data'] = $d;
		}


		// pre($series);
		// pre(array_values($series));


		
		$return_data = array(
			'x'=>$x,
			'series'=>array_values($series)
		);

		// pre($return_data);

		return_json(true, '',$return_data);
		
	}
}
?>