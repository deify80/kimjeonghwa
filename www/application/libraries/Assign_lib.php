<?php
/**
 * 작성 : 2014.10.23
 * 수정 : 
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );


class Assign_lib {


	function get_order($path) {
		$CI = & get_instance();
		$CI->load->model( 'Assign_model' );
		
		$row = $CI->Assign_model->get_order( $path );
		
		$total = $CI->Assign_model->get_order_total( $row->seqno );
		
		$this->set_order( $total, $row->seqno, $row->order_no );
		
		return $row->team_code;
	}


	public function set_order($total, $seqno, $order_no) {
		$CI = & get_instance();
		$CI->load->model( 'Assign_model' );
		$CI->Assign_model->update_order( $seqno, $order_no, 'N' );
		
		$new_order_no = ($order_no == $total - 1) ? 0 : $order_no + 1;
		$CI->Assign_model->update_order( $seqno, $new_order_no, 'Y' );
	}
}