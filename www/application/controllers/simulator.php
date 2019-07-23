<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Simulator extends CI_Controller {

	
	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->yield = FALSE;

		$this->load->model( array (
			'Consulting_Model'
		) );

		$this->load->library('Patient_lib');
	}

	function trans_status() {
		$status_arr = array(
			'99'=>'수술완료',
			'98'=>'수술예정',
			'90'=>'수술취소',
			'52'=>'내원상담',
			'51'=>'내원예약',
			'50'=>'내원취소',
			'11'=>'진행중',
			'12'=>'재컨택',
			'13'=>'예치금',
			'21'=>'부재중1',
			'22'=>'부재중2',
			'23'=>'부재중3',
			'24'=>'부재중4',
			'01'=>'보류',
			'02'=>'취소',
			'00'=>'종료'
		);

		return $status_arr;
	}

	public function index() {
		$to = array('A','B','C','D');
		$assign = array();
		$where = array(
			'team_code'=>array('91','95')
			// 'cst_status'=>array('90','50','11','12','21','22','23','24','01','02','00')
		);
		$field = 'cst_seqno, name, team_code, cst_status';
		$rs = $this->Consulting_Model->get_cst_all($where, $field, $order_by='cst_status ASC, cst_seqno DESC');
		// pre($rs);
		foreach($rs as $idx => $row) {
			$key = $idx%count($to);
			$to_team = $to[$key];
			$assign[$to_team]['db'][] = $row['cst_seqno'];
			$assign[$to_team]['status'][$row['cst_status']]++;
		}
		// echo $this->db->last_query();
		// pre($assign);
		$datum = array(
			'cfg'=>array(
				'team'=>$to,
				'team_to'=>array(
					'92'=>'박혜정팀',
					'93'=>'김아름팀',
					'94'=>'이혜진팀',
					// '96'=>'지원희팀',
					'98'=>'홍세영팀',
				),
				'status'=>$this->trans_status()
			),
			'assign'=>$assign
		);
		$this->_render('simulator',$datum, 'blank');
	}

	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "/consulting/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	} 
}
