<?php
/**
 * 근태관리
 * 작성 :
 * @author 창훈
 */

if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Assessment_proc extends CI_Controller {

	public function __construct() {

		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->yield = TRUE;
		
		$this->load->model( array (
			'Manage_model',
			'User_model',			
			'Business_model',
			'Assessment_model'
		) );
	}
	
	// 평가 등록
	public function index_save() {

		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;		

		$input['biz_info'] = $biz_info;		
		$input['syear'] = $syear;		
		$input['firsthalf'] = $firsthalf ;
		$input['sdate'] = $sdate ;
		$input['ldate'] = $ldate ;
		$input['is_result'] = $is_result ;
		$input['is_cocy'] = $is_cocy ;
		$input['is_ifs'] = $is_ifs;
		$input['is_exposure'] = $is_exposure;
		$input['etc'] = $etc;
		$input['is_rating'] = $is_rating;

		if ( $mode == 'update' ) {
			$reidx = $this->Assessment_model->settion_update($input , $inout_no );			
		} else {

			$input['regdate'] = date("Y-m-d") ;
			$reidx = $this->Assessment_model->settion_insert($input);	

			$where['status'] = '1' ;
			$result = $this->User_model->get_user_all($where);	
			foreach( $result as $key => $row ){
				$input2['re_no'] = $reidx  ;
				$input2['date'] = date( "Y-m-d" ) ;
				$input2['syear'] = $firsthalf ;
				$input2['userid'] = $row['user_id'] ;
				$this->Assessment_model->insert2($input2);
			}		

		}
	}	
	
	//성과관리 저장
	public function  index_setting_save() {

		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;		

		if ( $checked ) {
			$competency_arr = implode(",",$checked);
		}	
		if ( $checked2) {	
			$various_arr =  implode(",",$checked2);
		}	
				
		$temps = explode (","  , $approval_name  ) ;
		$competency_v = implode(",",$competency_arr);
		
		if (count($temps)) {
			foreach ($temps as $i=>$val) {	    
				
				$cst_seqno =  $val ;
//				echo $cst_seqno ."<br>" ;

				if ( $cdog != 'dog' ) :
					$where = null;
					$where[] = "a_reidx=".$cst_seqno;				
					$this->Assessment_model->deletes($where);				
				endif;					
				
				$input = null;
				$input['result_percent'] = $result_percent;
				$input['competency_percent'] = $competency_percent ;
				$input['various_percent'] = $various_percent ;
				$input['a_result_percent'] = $a_result_percent ;
				$input['a_competency_percent'] = $a_competency_percent ;
				$input['a_various_percent'] = $a_various_percent ;
				$input['b_result_percent'] = $b_result_percent ;
				$input['b_competency_percent'] = $b_competency_percent ;
				$input['b_various_percent'] = $b_various_percent ;
				
				if ( $cdog != 'dog' ) :
				$competency_v = implode(",",$checked2);
				$input['ifevaluation'] = $competency_v ;
				$input['adetails_val'] = $competency_arr ;

				if ( $competency_v ) {
					
					$input['competency_val'] = '' ;
					$where_c = null;
					$where_c[] = "no=".$cst_seqno;
					$where_c[] = "code='E' " ;
					$this->Assessment_model->asses_down_delete($where_c);
				}
				if (count($checked)) {

					$where_d = null;
					$where_d[] = "a_reidx=".$cst_seqno;
					$where_d[] = "code='W' " ;

					$this->Assessment_model->asses_down_delete($where_d);

					foreach ($checked as $i=>$val) {			
						$input_a = null;							
						$input_a['code'] = 'W' ;			
						$input_a['a_reidx'] = $cst_seqno ;
						$input_a['adetails_reidx'] = $val ;						
						$reidx = $this->Assessment_model->down_insert($input_a);
					} 
				}	
				endif; 
				$this->Assessment_model->update($input, $cst_seqno );				

			}
		}

	}

	public function pronc_update() {

		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;	

		$input_a = null;
		if ( $vcate =='tp1' ) {
			$input_a['b_user_princ'] = $vprinc ;
		} else if  ( $vcate =='tp2' ) {
			$input_a['b_team_princ'] = $vprinc ;
		} else if ( $vcate =='tp3' ) {
			$input_a['b_greetings_princ'] = $vprinc ;
		} else if ( $vcate =='tp4' ) {
			$input_a['c_greetings_princ'] = $vprinc ;
		} 

		$reidx = $this->Assessment_model->asses_down_update($input_a , $vno);		
	}

	// 성과 등록
	public function assess_view_one_save() {

		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;		

		/************/
	    // 기존첨부파일 삭제
	    $settle_file_remove = $this->input->post( 'settle_file_remove' );
	    if(is_array($settle_file_remove)) {
	    	$this->Assessment_model->assessment_file_del(implode(',',$settle_file_remove));
	    }

	    // 첨부파일 저장
		$files = array();
		if(is_array($_FILES)) {
			$attach = $_FILES['settle_file'];
			foreach($attach as $field=>$file) {
				foreach($file as $idx=>$value) {
					$files[$idx][$field] = $value;
				}
			}
		}

		$_FILES = $files;
		
	    // 파일 저장 옵션 초기화
		$config['upload_path'] = "./DATA/assessment/";	       
	    $config['encrypt_name'] = TRUE;
	    $config['allowed_types'] = '*';
	    
	    $this->load->library('upload', $config);
	    $this->upload->initialize($config);

	    foreach( $files as $key => $val ){

			echo $key ;

	        if ( !$this->upload->do_upload($key) ) {
	            $error = array('error' => $this->upload->display_errors());
	        }
	        else {
	            $data = array('upload_data' => $this->upload->data());
				echo  $data['upload_data']['file_name'] ;
	            $inputs = array(
	                    'settle_no' => $inout_no,
	                    'file_name' => $data['upload_data']['file_name'],
	                    'ori_name' => $val['name'],
	            );
	            $this->Assessment_model->assessment_file_insert( $inputs );
	        }
	    }
		/************/

		if ( $mode == 'update' ) {

			if (count($no)) {
				foreach ($no as $i=>$val) {
					$input['codeval'] = $codeval[$i] ;
					$input['problem'] = $problem[$i] ;
					$input['pweight'] = $pweight[$i] ;
					$input['performance'] = $performance[$i] ;
					$input['progress'] = $progress[$i] ;
					$input['assessment'] = $assessment[$i] ;
					if ( $a_greetings_princ ) $input['a_greetings_princ'] = $a_greetings_princ[$i] ;
					
					$reidx = $this->Assessment_model->asses_result_update($input , $no[$i] );	
				}
			}

		} else {
			$input['a_reidx'] = $inout_no;		
			$input['codeval'] = $codeval ;
			$input['problem'] = $problem ;
			$input['pweight'] = $pweight ;
			$input['performance'] = $performance ;
			$input['progress'] = $progress ;
			$input['assessment'] = $assessment ;

			$reidx = $this->Assessment_model->result_insert($input);	
		}

	}	

	public function assessment_view_act() {
		echo "<pre>" ;		
			print_r ( $_REQUEST ) ;
		echo "</pre>" ;
		
		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;		

		$where_d[] = "a_reidx=".$inout_no;
		$where_d[] = "code='E' " ;
		$this->Assessment_model->asses_down_delete($where_d);


		$temps = explode (","  , $approval_name  ) ;
		
		if (count($temps)) {
			foreach ($temps as $i=>$val) {	
					$approval_val = explode (","  , $ifevaluation ) ;					

					if (count($approval_val)) {
						foreach ($approval_val as $is=>$vals) {	    
							$input_a = null;
							$input_a['code'] = 'E' ;
							$input_a['a_reidx'] = $inout_no ;
							$input_a['b_reidx'] = $val ;
							$input_a['adetails_reidx'] = $vals ;
							$reidx = $this->Assessment_model->down_insert($input_a);
						}
					}
		
			}
		}

		$input['competency_val'] = $approval_name ;
		$this->Assessment_model->update($input, $inout_no );			
	}

	public function assess_view_one_delete( ) {

		$seqno = $this->input->post('seqno');		

		$where = null;
		$where[] = "no=".$seqno;

		$this->Assessment_model->asses_result_delete($where);
		exit;
	}
/*
	public function assessment_upload () {

		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;	
		
		$config['upload_path'] = "./DATA/assessment/";	    
	    $config['encrypt_name'] = TRUE;
	    $config['allowed_types'] = '*'; //application/vnd.ms-excel

		if ( $old_file_name ) :
			@unlink($config['upload_path'].$old_file_name );
		endif;

		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload('excel_file'))
		{
			$error = array('error' => $this->upload->display_errors());
		}	
		else
		{
			$upload_data = $this->upload->data();
			$input = null;
			$input['file_uplode_name'] = $upload_data['orig_name']."&&".$upload_data['file_name'];
			$this->Assessment_model->update($input, $inout_no );	
			
			$return = array(
				'file_name_olg'=>$upload_data['orig_name'],
				'file_name_val'=>$upload_data['file_name']
			);

			return_json(true, '', $return);
		}

	}
*/
	public function download() {
		$this->load->helper( 'download' );
	
		$int_no = $this->uri->segment(3);		
		$row = $this->Assessment_model->select_inout_view( $int_no );	

		$file_name = explode ( "&&" , $row['file_uplode_name'] ) ;
		
		$data = file_get_contents( "./DATA/assessment/".$file_name[1]);
		force_download( $file_name[1] , $data );
		exit;
	}

}
?>