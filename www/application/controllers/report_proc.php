<?php
/**
 * 보고서관리 Process
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Report_proc extends CI_Controller {

	public function __construct() {
		parent::__construct();
		
		$this->param = $this->input->post(NULL, true);
		$this->load->model(array('Report_Model'));
		
	}

	private function _upload($config, $filename) {
		if(!is_dir($config['upload_path'])) @mkdir($config['upload_path'], DIR_WRITE_MODE, true);
		$config_default = array(
			'encrypt_name'=>TRUE,
			'allowed_types'=>'*'
		);

		$config = array_merge($config_default, $config);
		
		$this->load->library('upload', $config);

		if (!$this->upload->do_upload($filename)) {
			echo $this->upload->display_errors();
			$result = false;
		}
		else {
			$result = $this->upload->data();
		}
		return $result;
	}


	/**
	 * 보고문서 업로드
	 * @return [type] [description]
	 */
	function report_upload() {

		$config = array(
			'upload_path'=>"./DATA/temp/".date('Ymd').'/'
		);
		// pre($config);
		$upload_data = $this->_upload($config, 'file_report');
		if(!$upload_data) {
			return_json(false,'파일 업로드에 실패하였습니다.\n'.$this->upload->display_errors());
		}
		else {

			$file_path = str_replace($_SERVER['DOCUMENT_ROOT'],'',$upload_data['full_path']);
			$return = array('file_url' => 'http://'.$_SERVER['HTTP_HOST'].$file_path);
			return_json(true,'', $return);
		}
	}

	function report_save() {
		$p = $this->param;
		// pre($p);
		// exit;
		$writer = $this->common_lib->get_user_row(array('user_id'=>$p['writer_id']), 'no, user_id, name, position_code');
		
		$report_cnt = $this->Report_Model->count(array('DATE_FORMAT(date_insert,"%Y-%m-%d")'=>date('Y-m-d')));			
		$report_number = $p['report_code'].'-'.date('Ymd').'-'.str_pad(($report_cnt+1),3,'0',STR_PAD_LEFT).'호'; 
		$record = array(
			'report_code'=>$p['report_code'],
			'report_number'=>$report_number,
			'writer_id'=>$writer['user_id'],
			'writer_info'=>serialize(
				array(
					'user_id'=>$writer['user_id'],
					'name'=>$writer['name'],
					'position'=>$this->session->userdata('ss_position_name'),
					'team'=>$this->session->userdata('ss_team_name'),
					'dept'=>$this->session->userdata('ss_dept_name')
				)
			),
			'writer_dept'=>$this->session->userdata('ss_duty_code'),
			'writer_team'=>$this->session->userdata('ss_team_code'),
			'title'=>$p['title'],
			'contents'=>htmlspecialchars($p['contents'], ENT_QUOTES),
			// 'attach'=>$p[''],
			'date_insert'=>NOW
		);
		
		$rs = $this->Report_Model->insert($record,'report');
		if($rs) {
			$report_no = $rs;

			//결재자 등록
			$position_list = $this->config->item( 'position_code' );
			$approval = explode(',',$p['approval']);
			foreach($approval as $k=>$v) {
				$user_id = trim($v);
				$user = $this->common_lib->get_user_row(array('user_id'=>$user_id), 'no, user_id, name, position_code');
				$step = $k+1;
				$record = array(
					'report_no'=>$report_no,
					'step'=>$step,
					'type'=>'approval',
					'user_id'=>$user['user_id'],
					'user_info'=>serialize(array('name'=>$user['name'], 'position'=>$position_list[$user['position_code']])),
					'status'=>($step>1)?'assign':'wait',
					'date_insert'=>NOW
				);
				$sign_no = $this->Report_Model->insert($record,'report_sign');

				if($k<1) {
					$this->Report_Model->update(array('sign_no'=>$sign_no), array('no'=>$report_no), 'report');
				}
			}

			//참조자 등록
			$reference = explode(',',$p['reference']);
			if(!empty($reference)) {
				foreach($reference as $k=>$v) {
					$user = $this->common_lib->get_user_row(array('user_id'=>$v), 'no, user_id, name, position_code');
					$record = array(
						'report_no'=>$report_no,
						'step'=>($k+1),
						'type'=>'reference',
						'user_id'=>$user['user_id'],
						'user_info'=>serialize(array('name'=>$user['name'], 'position'=>$position_list[$user['position_code']])),
						'status'=>'wait',
						'date_insert'=>NOW
					);
					$this->Report_Model->insert($record,'report_sign');
				}
			}

			$report_cfg = $this->Report_Model->select_config_row(array('code'=>$p['report_code']));
			
			//서브데이터 등록
			if($report_cfg['doc_type'] == 'attach') {
				//보고서파일 등록
				$this->load->model('Common_Model');

				$config = array('upload_path'=>"./DATA/report/".$p['report_code'].'/'.$report_no.'/');
				$upload_data = $this->_upload($config, 'file_report');
				if(!$upload_data) {
					return_json(false,'파일 업로드에 실패하였습니다.#1\n'.$this->upload->display_errors());
				}
				else {
					$record = array(
						'refer_table'=>'report',
						'refer_seqno'=>$report_no,
						'new_name'=>$upload_data['file_name'],
						'file_name'=>$upload_data['orig_name'],
						'file_size'=>$upload_data['file_size'],
						'file_path'=>ltrim($config['upload_path'],'.'),
						'mime_type'=>$upload_data['file_type'],
						'etc'=>'report'
					);
					$this->Common_Model->insert_file($record);
				}
			}
			else {
				switch($p['report_code']) {
					case 'D003': //상담팀 일일매출보고

						$doc = $p['doc'];
						// pre($doc);
						$sub = array();
						foreach($doc as $row) {
							$sub['no'][]= $row['no'];
							$sub['data'][] = unserialize(htmlspecialchars_decode($row['data'], ENT_QUOTES));
						}

						$this->Report_Model->update(array('sub_no'=>serialize($sub['no']), 'sub_data'=>serialize($sub['data'])),array('no'=>$report_no));
					break;
					break;
					case 'D004': //회계팀 지출결의서
						$this->load->model('Support_Model');
						$finance_no = array();
						foreach($p['doc'] as $row) {
							$account_info = $this->Support_Model->select_account_row(array('account_seqno'=>$row['account_seqno']), 'account_type AS type, currency, account_name AS name, account_no AS number');
							// pre($p);
							// pre($account_info);
							$record = array(
								'classify_code'=>$row['classify_code'],
								'item_code'=>$row['item_code'],
								'plan_date'=>$row['plan_date'],
								'trade_customer'=>$row['trade_customer'],
								'statement_type'=>'O',
								'amount'=>str_replace(',','',$row['amount']),
								'process_category'=>$row['process_category'],
								'info'=>htmlspecialchars($row['info'], ENT_QUOTES),
								'account_seqno'=>$row['account_seqno'],
								'account_info'=>serialize($account_info),
								'referer'=>'report',
								'referer_no'=>$report_no,
								'status'=>'approved',
								'reg_user_id'=>$this->session->userdata('ss_user_id'),
								'date_insert'=>NOW,
								'is_delete'=>'Y'
							);
							$finance_rs = $this->Support_Model->insert_finance($record);
							if($finance_rs) {
								$finance_no[] = $finance_rs;
							}
						}
						// pre($finance_no);
						$this->Report_Model->update(array('sub_no'=>serialize($finance_no)),array('no'=>$report_no));
					break;
				}
			}
			

			//첨부파일 등록
			////첨부파일
			$files = array();
			if(is_array($_FILES)) {
				$attach = $_FILES['file_attach'];
				foreach($attach as $field=>$file) {
					foreach($file as $idx=>$value) {
						$files[$idx][$field] = $value;
					}
				}
			}

			$_FILES = $files;

			foreach( $files as $key => $val ){
				$upload_data = $this->_upload($config, $key);
				if(!$upload_data) {
					return_json(false,'파일 업로드에 실패하였습니다.#2\n'.$this->upload->display_errors());
				}
				else {
					$record = array(
						'refer_table'=>'report',
						'refer_seqno'=>$report_no,
						'new_name'=>$upload_data['file_name'],
						'file_name'=>$upload_data['orig_name'],
						'file_size'=>$upload_data['file_size'],
						'file_path'=>$config['upload_path'],
						'mime_type'=>$upload_data['file_type'],
						'etc'=>'attach'
					);
					$this->Common_Model->insert_file($record);
				}
			}
			return_json(true,'보고서가 상신되었습니다.');
		}
		else {
			return_json(false);
		}
	}

	function sign_line_save() {
		$p = $this->param;

		if($p['mode'] =='insert') {
			$record = array(
				'user_id'=>$this->session->userdata('ss_user_id'),
				'line_name'=>$p['line_name'],
				'person_approval'=>$p['approval'],
				'person_reference'=>$p['reference'],
				'date_insert'=>NOW
			);
			$rs = $this->Report_Model->insert($record,'report_sign_line');
		}
		else {
			$record = array(
				'person_approval'=>$p['approval'],
				'person_reference'=>$p['reference']
			);

			$where = array(
				'no'=>$p['no']
			);

			$rs = $this->Report_Model->update($record, $where, 'report_sign_line');
		}
	
		if($rs) {
			return_json(true,'결재선이 저장되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	function sign_line_remove() {
		$where = array(
			'no'=>$this->param['line_no']
		);

		$rs = $this->Report_Model->delete($where,'report_sign_line');
		if($rs) {
			return_json(true,'결재선이 삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해 주세요.');
		}
	}

	/**
	 * 결재(승인/반려)
	 * @return [type] [description]
	 */
	function sign() {
		$report_no = $this->param['report_no'];
		$sign = $this->param['sign'];
		$this->load->library('report_lib');
		$rs = $this->report_lib->sign($report_no, $sign);
		if($rs) {
			$sign_txt = ($p['sign'] == 'approved')?'승인':'반려';
			return_json(true,$sign_txt.'되었습니다.', $rs);
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
	}

	/**
	 * 문서처리(상신취소)
	 * @return [type] [description]
	 */
	function cancel() {
		$report_no = $this->param['report_no'];
		$record = array(
			'report_status'=>'cancel'
		);
		$rs = $this->Report_Model->update($record, array('no'=>$report_no), 'report');
		if($rs) {
			return_json(true,'상신취소되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
	}

	/**
	 * 문서처리(삭제)
	 * @return [type] [description]
	 */
	function remove() {
		$report_no = $this->param['report_no'];
		$record = array('is_delete'=>'Y');
		$rs = $this->Report_Model->update($record, array('no'=>$report_no), 'report');
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
	}

	function comment_save() {
		$p = $this->param;
		$writer_id = $this->session->userdata('ss_user_id');
		$record = array(
			'report_no'=>$p['report_no'],
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

		$rs = $this->Report_Model->insert($record, 'report_comment');
		if($rs) {
			return_json(true,'의견이 등록되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
		// pre($record);
	}

	function comment_remove() {
		$where = array(
			'no'=>$this->param['comment_no']
		);
		$rs = $this->Report_Model->delete($where, 'report_comment');
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요.');
		}
	}



}