<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Consulting_proc extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->param = $this->input->post(NULL, true);
		$this->load->model('Consulting_m');
	}

	public function db_check() {
		$tel = trim($this->param['tel']);
		$check_row = $this->Consulting_m->check_tel($tel);
		if($check_row['total'] > 0) {
			$this->load->model('User_m');
			$team_info = $this->User_m->get_team_row(array('team_code'=>$check_row['team_code']));
			return_json(false,'이미 등록된 연락처입니다.<br />'.$team_info['team_name']);
		}
		else {
			return_json(true,'등록가능한 연락처입니다.');
		}
	}

	public function db_input() {
		$p = $this->param;

		$ss = $this->session->userdata;


		$failure = false;

		$record = array(
			'biz_id'=>$ss['ss_biz_id'],
			'hst_code'=>$ss['ss_hst_code'],
			'name'=>$p['name'],
			'tel'=>str_replace('-','',$p['tel']),
			'sex'=>$p['sex'],							// 20170320 kruddo : 성별 추가
			'category'=>$p['category'],					// 20170320 kruddo : 상담항목 추가
			'path'=>$p['path'],
			'type'=>'M',
			'media'=>$p['media'],
			'reg_date'=>TIME_YMDHIS,
			'db_status'=>0,
			'memo'=>$p['memo'],
			'reg_user_id'=> $ss['ss_user_id'],
			'date_insert'=>date('Y-m-d H:i:s'),
			'ip'=>$_SERVER['REMOTE_ADDR']
		);
		//pre($record);exit;


		if ($ss['ss_dept_code'] == '90' && in_array($p['path'], array('4','6'))) {
			//상담팀 && 소개인경우 직접분배대기
			//$record['db_status'] = '-2'; //검토
		}



		$db_seqno = $this->Consulting_m->db_insert($record);
		if($db_seqno) {
			//중복체크
			$check_row = $this->Consulting_m->check_tel(trim($record['tel']));

			//상담실인경우 즉시 분배
			if($ss['ss_dept_code'] == '90' && $record['db_status']!=-2) {
				if($check_row['total'] > 0) {
					$record_update = array(
						'db_status'=>'8',
						'assign_type'=>'F',
						'assign_date'=>TIME_YMDHIS,
						'assign_user_id'=> $ss['ss_user_id']
					);

					$this->Consulting_m->db_update($record_update, array('db_seqno'=>$db_seqno));
					$failure = '중복데이터로 팀분배불가';
				}
				else {
					$this->load->library('consulting_lib');
					$record_consulting = array(
						'db_seqno'=>$db_seqno,
						'name'=>$record['name'],
						'tel'=>$record['tel'],
						'path'=>$record['path'],
						//'category'=>$record['category'],			// 20170320 kruddo : 상담항목 추가
						'sex'=>$record['sex'],					// 20170320 kruddo : 성별 추가
						'reg_date'=>TIME_YMDHIS,
						'biz_id'=>$record['biz_id'],
						'hst_code'=>$record['hst_code'],
						'etc'=>$record['memo'],
						'team_code'=>$ss['ss_team_code'],
						'org_team_code'=>$ss['ss_team_code'],
						'charge_date'=>$this->consulting_lib->set_charge_date($record['path']),
						'media'=>$record['media'],
						'db_type'=>$record['type']
					);
					$cst_seqno = $this->Consulting_m->insert($record_consulting);
					if($cst_seqno) {
						$record_update = array(
							'db_status'=>'1',
							'assign_type'=>'F',
							'assign_date'=>TIME_YMDHIS,
							'assign_user_id'=> $ss['ss_user_id']
						);

						$rs = $this->Consulting_m->db_update($record_update, array('db_seqno'=>$db_seqno));
						if(!$rs) $failure = '팀분배결과업데이트 실패';
						else {
							$this->input_memo($cst_seqno, false);
						}
					}
					else {
						$failure = '팀분배실패';
					}
				}
			}
		}
		else {
			$failure='DB등록실패';
		}


		if(!$failure){
			return_json(true,'등록되었습니다.');
		}
		else {
			return_json(false,'등록에 실패하였습니다.<BR />'.$failure);
		}
	}


	public function input_memo($cst_seqno='', $return=true) {
		$cst_seqno = ($cst_seqno)?$cst_seqno:$this->input->post('cst_seqno');
		$change_item = '상담내용|상담내용 추가';
		$this->save_log_change( $change_item, $cst_seqno);

		$input = null;
		$input['mod_date'] = TIME_YMDHIS;
		$this->Consulting_m->update_cst( $cst_seqno, $input );

		$input = null;
		$input['memo'] = $this->input->post( 'memo' );
		$input['cst_seqno'] = $cst_seqno;
		$input['reg_date'] = TIME_YMDHIS;
		$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );

		$result = $this->Consulting_m->memo_insert( $input );

		if ($result) {
			if(!$return) return true;
			return_json(true,'저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}

	function save_log_change($change_item, $cst_seqno) {
		if ($this->input->post( 'change_item' ) != '') $change_item = $this->input->post( 'change_item' );
		$exp_change_item = explode( '㉿', $change_item );
		foreach ( $exp_change_item as $i => $val ) {

			if ($val != "") {
				$exp_value = explode( '|', $val );
				$list[$exp_value[0]] = $exp_value[1];
			}
		}

		$total = 0;
		foreach ( $list as $i => $val ) {
			$input = null;
			$input['title'] = strip_tags( $i );
			$input['contents'] = strip_tags( $val );
			$input['reg_date'] = TIME_YMDHIS;
			$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );
			$input['cst_seqno'] = $cst_seqno;
			$input['team_code'] = $this->session->userdata( 'ss_team_code' );
			$this->Consulting_m->log_insert( $input );
			$total ++;
		}

		return $total;
	}

}
