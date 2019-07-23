<?php
/**
 * 작성 : 2014.11.04
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Db_proc extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model( array (
				'User_model',
				'Consulting_model',
				'Assign_model'
		) );

		$this->category_list = $this->config->item( 'category' );
		$this->load->library( array('assign_lib', 'consulting_lib') );

		$this->param = $this->input->post(NULL, true);
	}

	public function search() {
		$p = $this->param;

		$query = $p['query'];
		$where = array(
			"(name LIKE '%{$query}%' OR tel LIKE '%{$query}%' OR messenger LIKE '%{$query}%')"=>null
		);

		$team = $this->common_lib->get_cfg('team');
		$cst_status = $this->config->item('cst_status');
		$path =  $this->common_lib->get_cfg('path');

		$rs = $this->Consulting_model->select_consulting($where);
		$list = array();
		foreach($rs as $idx => $row) {
			if($idx >= 100) continue;
			$row['tel'] = set_blind('phone', tel_check( $row['tel'], '-' ));
			$row['status'] = $cst_status[$row['cst_status']];
			$row['team'] = $team[$row['team_code']];
			$row['location'] = ($row['charge_date']>=date('YmdHis'))?'team':'common';
			$row['path'] = $path[$row['path']];
			$contact = array_filter(array($row['tel'], $row['messenger']));
			$row['contact'] = (empty($contact))?'미입력':implode(', ',$contact);
			$list[] = $row;
		}
		if($rs) {
			return_json(true, '', $list);
		}
		else {
			return_json(false);
		}
	}

	public function add() {
		$p = $this->param;
		$record = array(
			'biz_id'=>$this->session->userdata('ss_biz_id'),
			'hst_code'=>$this->session->userdata('ss_hst_code'),
			'name'=>$p['name'],
			'birth'=>str_replace('-','',$p['birth']),
			'sex'=>$p['sex'],
			'tel'=>str_replace('-','',$p['tel']),
			'messenger'=>$p['messenger'],
			'email'=>$p['email'],
			'category'=>$p['category'],
			'call_time'=>$p['call_time'],
			'path'=>$p['path'],
			'type'=>'W',
			'media'=>$p['media'],
			'reg_date'=>TIME_YMDHIS,
			'memo'=>$p['memo'],
			'reg_user_id'=> $this->session->userdata('ss_user_id'),
			'date_insert'=>date('Y-m-d H:i:s'),
			'ip'=>$_SERVER['REMOTE_ADDR']
		);

		$db_seqno = $this->Consulting_model->db_insert($record);
		if($db_seqno) {
			if($p['type'] == 'consulting') { //팀DB에서 등록시 즉시 배분
				$db_seqno[] = $db_seqno;
				$this->do_assign( $db_seqno, $p['assign_type']);
			}
			else {
				return_json(true,'신규DB가 등록되었습니다.');
			}
 		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	public function do_assign($db_seqno = null, $assign_type='') {
		$p = $this->input->post(NULL, true);


		if (! is_array( $db_seqno )) {
			foreach ( $p['chk'] as $i => $val ) {
				$db_seqno[] = $val;
			}
		}

		if(empty($assign_type)) {
			$assign_type = $this->input->post( 'assign_type' );
		}

		if(empty($db_seqno)) {
			return false;
		}

		//보상처리 - 직접분배시에만 적용
		if($p['assign_type'] == 'F') {
			$compensation = (isset($p['compensation']))?$p['compensation']:'N';
			$compensation_reason = ($compensation == 'Y')?$p['compensation_reason']:'';
		}

		// pre($p);
		// pre($db_seqno);

		// 트랜잭션 처리
		$this->db->trans_begin();

		$success_total = 0;
		$result = $this->Consulting_model->get_db_in( $db_seqno );


		foreach ( $result as $i => $row ) {

			$db_status = '8';
			$insert_valid = false;

			// 중복체크
			$check_row['total'] = 0;
			if (trim($row['tel']) != '') $check_row = $this->Consulting_model->check_tel( trim($row['tel']) );
			if(DEV === true) {
				//pre($check_row);exit;
			}

			$insert_valid = $this->_check_dup($check_row); //2년 지난  db는 재 등록가능

			//db입력중복체크
			$count = $this->Consulting_model->consulting_count(array('db_seqno'=>$row['db_seqno']));

			if (($check_row['total'] == 0 || $insert_valid == true) && $count<1) {

				if ($assign_type == 'F') { //직접분배
					// 지인소개이거나 실시간 상담일 경우
					$team_code = $this->input->post( 'team_code' );
				} else {
					$team_code = $this->assign_lib->get_order( $row['path'] );
				}

				$input = null;
				$input['db_seqno'] = $row['db_seqno'];
				$input['name'] = $row['name'];
				$input['birth'] = $row['birth'];
				$input['tel'] = str_replace( '-', '', $row['tel'] );
				$input['email'] = set_null( $row['email'] );
				$input['messenger'] = set_null( $row['messenger'] );
				$input['path'] = $row['path'];
				$input['reg_date'] = TIME_YMDHIS;
				$input['biz_id'] = $row['biz_id'];
				$input['hst_code'] = $row['hst_code'];
				$input['etc'] = $row['memo'];
				$input['sex'] = $row['sex'];
				$input['team_code'] = $team_code;
				$input['org_team_code'] = $team_code;
				$input['media'] = $row['media'];
				$input['db_category'] = $row['category'];
				$input['db_type'] = $row['type']; //유입구분

				$input['compensation'] = $compensation;
				$input['compensation_reason'] = $compensation_reason;


				// 배정될 팀이 존재하는경우
				if (! empty( $team_code )) {

					//db 보유시간 추출
					//$input['charge_date'] = $this->consulting_lib->set_charge_date($input['path'], $input['reg_date']);
					$input['charge_date'] = $this->consulting_lib->set_charge_date($input['path']);


					$insert_status = $this->Consulting_model->consulting_insert( $input );
					$cst_seqno = $this->Consulting_model->get_insert_id();
					if ($insert_status) {
						$this->_memo_add( $cst_seqno, $row );
						//팀분배 로그
						$datum = array(
							'cst_seqno'=>$cst_seqno,
							'act'=>'first', //first:최초분배, move:이동, remain:변화없음
							'hst_code'=>$row['hst_code'],
							'biz_id'=>$row['biz_id'],
							'team_code'=>$team_code,
							'team_code_from'=>'',
							'charge_date'=>$input['charge_date']
						);
						$this->Consulting_model->charge_log_insert($datum);
					}

					$db_status = '1';
					$success_total ++;

					//최초 컨택 팀 로그 쌓기 add by hjlee 2015-05-04
					$this->Consulting_model->update_db_status(array($row['db_seqno']), $assign_type, $db_status);
				}
				else {
					//팀이 없는경우
					$this->Consulting_model->update_db_status(array($row['db_seqno']), $assign_type, $db_status);
				}
			}
			else {
				$this->Consulting_model->update_db_status(array($row['db_seqno']), $assign_type, $db_status);
			}
		}

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
		} else {
			$this->db->trans_commit();
		}

		// 완료 건수
		$json = null;
		$json['success_total'] = $success_total;
		$json['dup_total'] = $check_row['total'];
		$json['success'] = true;

		echo json_encode( $json );
	}



	private function _memo_add($cst_seqno, $row=null) {
		$this->memo = "";
		$this->memo = ($row['call_time'] != '') ? '[상담가능시간] ' . $row['call_time'] : '';
		$this->memo .= ($row['category'] != '') ? '<br>[상담항목] ' . $this->category_list[$row['category']] : '';
		$this->memo .= ($this->input->post( 'realtime_memo' ) != '') ? '<br>[실시간 상담] ' . $this->input->post( 'realtime_memo' ) : '';

		$input = null;
		$input['memo'] = $this->memo;
		$input['cst_seqno'] = $cst_seqno;
		$input['reg_date'] = TIME_YMDHIS;
		$input['sort'] = 'A';
		$input['reg_user_id'] = $this->session->userdata( 'ss_user_id' );

		if (trim($this->memo) != '') $result = $this->Consulting_model->memo_insert( $input );
	}


	/**
	 * 보류 처리
	 * @return [type] [description]
	 */
	public function do_off() {
		$db_seqno = $this->param['chk'];
		$rs = $this->Consulting_model->db_update(array('db_status'=>'9', 'assign_type'=>''), array('db_seqno'=>$db_seqno));
		// $rs = $this->Consulting_model->update_db_status( $db_seqno, '', '9' );

		if($rs) {
			return_json(true);
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}



	public function direct_add() {

		// db 유입 추가
		$this->add();

		$id = $this->Consulting_model->get_insert_id();
		$db_seqno[] = $id;

		$this->do_assign( $db_seqno );
	}




	private function _check_dup($check_row) {

		if ($check_row['total'] == 0) {
			return false;
		}

		//2년 여부 판단 (재문의만 처리되도록 200년으로 변경 BY HJLEE 20181230)
		$limit_date = date( 'Ymd', mktime( 0, 0, 0, substr( $check_row['reg_date'], 4, 2 ), substr( $check_row['reg_date'], 6, 2 ), substr( $check_row['reg_date'], 0, 4 )+200) );

		$this->memo .= '[재문의] 입력사항';
		if ($limit_date > date( 'Ymd' )) {
			$input = null;
			$input['accept_date'] = null;
			$this->Consulting_model->update_cst( $check_row['cst_seqno'], $input );

			$this->_memo_add( $check_row['cst_seqno'] );
			$insert_valid = false;
		} else {
			$insert_valid = true;
		}

		return $insert_valid;
	}

	function landing_save() {
		$p = $this->input->post(NULL, true);
		$mode = $p['mode'];
		$landing_no = $p['landing_no'];
		$record = array(
			'title'=>$p['title'],
			'keywords'=>$p['keywords'],
			'description'=>$p['description'],
			'category_code'=>$p['category_code'],
			'tmpl_web'=>$p['tmpl_web'],
			'tmpl_mobile'=>$p['tmpl_mobile']
		);


		if($mode == 'insert') {
			$record['date_insert'] = NOW;
			$rs = $this->Consulting_model->insert_landing($record);
		}
		else {
			$inout_no = $this->param['inout_no'];
			$rs = $this->Consulting_model->update_landing($record, array('no'=>$landing_no));
		}

		if($rs) {
			return_json(true);
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}



	/**
	 * 자동분배 ON/OFF
	 * @return [type] [description]
	 */
	function auto_switch() {
		$switch = $this->input->post('switch');
		$rs = $this->common_lib->set_config('db','auto',$switch);

		$log_path = DOC_ROOT.'/application/logs/db_switch/'.date('ymd').'.txt';
		$log = array(
			'post'=>$this->param,
			'user_id'=>$this->session->userdata("ss_user_id"),
			'switch'=>$switch
		);

		if($rs) {
			$log['success'] = 'yes';
			write_log($log_path,$log);
			return_json(true);
		}
		else {
			$log['success'] = 'no';
			write_log($log_path,$log);
			return_json(false);
		}
	}

	function auto_assign() {


		//자동분배여부
		$is_auto = $this->common_lib->get_config('db','auto');
		if($is_auto=='off') {
			return_json(false,'switch off', array('switch'=>'off'));
		}

		//대기
		$where = array(
			'db_status' => '0',
			'hst_code'=>'H000',
			'biz_id'=>$this->session->userdata('ss_biz_id')
		);
		$result = $this->Consulting_model->select_db_row($where, 'db_seqno');

		$db_seqno = null;
		if($result) {
			$db_seqno = $result['db_seqno'];

			$this->Consulting_model->db_update(array('db_status'=>'-1', 'assign_user_id'=>$this->session->userdata("ss_user_id")), array('db_seqno'=>$db_seqno));
		}

		if($db_seqno > 0) {
			$rs = $this->do_assign(array($db_seqno), 'O');
		}
		else {
			return_json(false,'no db');
		}
	}


	function change_team() {
                $p = $this->param;
                $ss = $this->session->userdata;
                $team = $this->common_lib->get_cfg('team');



                //consulting_info 팀변경
                $where = array('db_seqno'=>$p['db_seqno']);
                $record = array('team_code'=>$p['team_code']);
                $result = $this->Consulting_model->consulting_update($record, $where);

                $cst_info = $this->Consulting_model->select_consulting_row($where);

                //변경로그
                $record_log = array(
                        'title'=>'팀',
                        'contents'=>$team[$p['team_code']],
                        'reg_date'=>date('YmdHis'),
                        'reg_user_id'=>$ss['ss_user_id'],
                        'cst_seqno'=>$cst_info['cst_seqno'],
                        'team_code'=>$ss['ss_team_code']
                );

                $this->Consulting_model->log_insert($record_log);


                //patient 팀변경
                $this->load->model('Patient_Model');
                $rs = $this->Patient_Model->update_patient(array('manager_team_code'=>$p['team_code'], 'manager_id'=>''), array('cst_seqno'=>$cst_info['cst_seqno']));

                //예약팀변경
                $patient_info = $this->Patient_Model->select_patient_row(array('cst_seqno'=>$cst_info['cst_seqno']));
                $this->Patient_Model->update_patient(array('manager_team_code'=>$p['team_code'], 'manager_id'=>''), array('patient_no'=>$patient_info['no']),'patient_appointment');

                if($rs) {
                        return_json(true, '변경되었습니다.');
                }
                else {
                        return_json(false, '잠시 후에 다시 시도해주세요.');
                }

        }

}

