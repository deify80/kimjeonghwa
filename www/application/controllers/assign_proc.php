<?php
/**
 * DB분배 Process
 * @author 이혜진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Assign_proc extends CI_Controller {
	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->load->model(array('Assign_model'));
	}


	function info_save() {
		$p = $this->param;
		$this->Assign_model->init_assign($p['biz_id'], $p['path'] );

		$record = array(
			'path'=>$p['path'],
			'memo'=>$p['memo'],
			'biz_id'=>$p['biz_id'],
			'hst_code'=>$this->session->userdata('ss_hst_code'),
			'status'=>1,
			'date_insert'=>NOW
		);

		$this->Assign_model->table = 'assign_info';
		$rs = $this->Assign_model->insert( $record );
		if($rs) {
			$seqno = $rs;

			// 팀 쪼개기
			$exp_order = explode( ',', $p['order'] );

			foreach ( $exp_order as $i => $team ) {
				$record = array(
					'seqno'=>$seqno,
					'order_no'=>$i,
					'turn_status'=>($team == $p['order_code']) ? 'Y' : 'N',
					'team_code'=>$team
				);

				$this->Assign_model->table = 'assign_order';
				$this->Assign_model->insert( $record );
			}

			return_json(true,'등록되었습니다.');

		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요#1');
		}
	}

	function remove_info() {
		$p = $this->param;
		$record = array('status'=>0);
		$where = array('seqno'=>$p['seqno']);
		$rs = $this->Assign_model->update_info($record, $where);
		if($rs) {
			return_json(true,'삭제되었습니다.');
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요');
		}
	}


	// 20170307 kruddo : DB규칙 - 자동분배규칙 수정
	function update_info_order() {
		$p = $this->param;
		$record = array('turn_status'=>'N', 'date_update'=>NOW);
		$where = array('seqno'=>$p['seqno']);
		$rs = $this->Assign_model->update_info_order($record, $where);

		$record = array('turn_status'=>'Y');
		$where = array('seqno'=>$p['seqno'], 'team_code'=>$p['team_code']);
		$rs = $this->Assign_model->update_info_order($record, $where);

		$record = array('date_update'=>NOW);
		$where = array('seqno'=>$p['seqno']);
		$rs = $this->Assign_model->update_info($record, $where);

		if($rs) {
			return_json(true,'변경되었습니다.');
		}
		else {
			return_json(false, '잠시 후에 다시 시도해주세요');
		}
	}
	// 20170307 kruddo : DB규칙 - 자동분배규칙 수정
}
?>
