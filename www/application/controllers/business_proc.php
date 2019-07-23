<?php
/**
 * 작성 : 2014.12.12
 * 수정 :
 *
 * @author 우석진
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Business_proc extends CI_Controller {


	public function __construct() {
		parent::__construct();
		$this->load->model( array (
				'Business_model'
		) );

		$this->load->library('Func_lib');
	}

	public function settle_add(){
		// pre($_POST);exit;

		$mode = $this->input->post( 'mode' );

		$input = null;
		$input['title'] = $this->input->post( 'title' );
		$input['content'] = $this->input->post( 'content' );
		$input['settle_number'] = $this->input->post( 'settle_number' );
		$input['user_no'] = $this->session->userdata( 'ss_user_no' );
		$input['settle_type'] = $this->input->post( 'settle_type' );
		$input['biz_id'] = $this->input->post( 'biz_id' );
		$input['reg_date'] = $this->input->post( 'reg_date' );

		$input['expense_schedule'] = $this->input->post( 'expense_schedule' );
		$input['expense_complete'] = $this->input->post( 'expense_complete' );

		// 20170119 kruddo : 임시결재 기능 추가
		$input['tmp_yn'] = $this->input->post( 'tmp_yn' );
		// 20170119 kruddo : 임시결재 기능 추가

	    // 기안 수정
	    if( $mode == "modify" ){
	    	$settle_no = $this->input->post( 'settle_no' );
	        $input['status'] = 0;
	        // 기안 수정
	        $this->Business_model->settle_modify( $input, $settle_no );
	        $this->Business_model->settle_reset( $settle_no );
	        $file_cancel_no = $this->input->post( 'file_cancel_no' );
	        // 배열이 아닐까?
	        if( $file_cancel_no ){
	           $this->Business_model->settle_file_del( $file_cancel_no );
	        }
	    }
	    // 기안 저장
	    else{
	        $settle_no = $this->Business_model->settle_insert( $input );
	    }

	    // 결재자 저장
	    $approval_person = $this->input->post( 'approval_person' );
	    $approval_array = explode(",", $approval_person);

	    for( $i = 0 ; $i < count($approval_array) ; $i++ ){
	        $input = array(
	                'settle_no' => $settle_no,
	                'user_no' => $approval_array[$i],
	                'level' => $i+1,
	                'person_type' => 0,
	                'status' => $i==0 ? $i : NULL,
	                'reg_date' => date('Y-m-d H:i:s')
	        );
	        $this->Business_model->settle_person_insert( $input );
	    }

	    // 참조자 저장
	    $refer_person = $this->input->post( 'refer_person' );
	    $refer_array = explode(",", $refer_person);

	    for( $i = 0 ; $i < count($refer_array) ; $i++ ){

	        if( $refer_array[$i] ){
    	        $input = array(
    	                'settle_no' => $settle_no,
    	                'user_no' => $refer_array[$i],
    	                'level' => 0,
    	                'person_type' => 1,
    	                'status' => 0,
    	                'reg_date' => date('Y-m-d H:i:s')
    	        );
    	        $this->Business_model->settle_person_insert( $input );
	        }
	    }


	    //기안 참조 저장
	    $draft_no = $this->input->post( 'draft_no' );

	    if( $draft_no ){
    	    $draft_no = split(",", $draft_no);

    	    for( $i = 0 ; $i < count( $draft_no ) ; $i++ ){

    	        $input = array(
    	                'settle_no' => $settle_no,
    	                'draft_no' => $draft_no[$i]
    	        );
    	        $this->Business_model->settle_draft_insert( $input );
    	    }
	    }


		// 20170322 kruddo : 기안작성 기안선택 일부 삭제 - 지출결의서(1)
	    switch ($this->input->post( 'settle_type' )) {
	    	case 1:
	    		$expense_type = $this->input->post( 'expense_type' );
		        $expense_date = $this->input->post( 'expense_date' );
		        $expense_company = $this->input->post( 'expense_company' );
		        $expense_history = $this->input->post( 'expense_history' );
				$expense_period_start = $this->input->post( 'expense_period_start' );
				$expense_period_end = $this->input->post( 'expense_period_end' );
		        $expense_price = $this->input->post( 'expense_price' );
		        $expense_etc = $this->input->post( 'expense_etc' );

		        for( $i = 0 ; $i < count($expense_date) ; $i++ ){
					// 20170201 kruddo : 임시 저장일 경우 거래일 데이터 없더라도 저장 하도록
					if($this->input->post( 'tmp_yn' ) == "Y"){
						$expense_date[$i] = ($expense_date[$i])?$expense_date[$i]:'0000-00-00';
					}
					// 20170201 kruddo : 임시 저장일 경우 거래일 데이터 없더라도 저장 하도록
					

		            if( $expense_date[$i] ){

	    	            $input = array(
	    	                    'settle_no' => $settle_no,
	    	                    'expense_type' => $expense_type[$i],
	    	                    'expense_date' => $expense_date[$i],
	    	                    'expense_company' => $expense_company[$i],
	    	                    'expense_history' => $expense_history[$i],
								'expense_period_start' => $expense_period_start[$i],
								'expense_period_end' => $expense_period_end[$i],
	    	                    'expense_price' => str_replace(",", "", $expense_price[$i]),
	    	                    'expense_etc' => $expense_etc[$i],
	    	            );

	    	            $this->Business_model->settle_expense_insert( $input );
	    	            //$this->func_lib->pr($input);
		            }

		        }
	    	break;

	    }

	    // 기존첨부파일 삭제
	    $settle_file_remove = $this->input->post( 'settle_file_remove' );
	    if(is_array($settle_file_remove)) {
	    	$this->Business_model->settle_file_del(implode(',',$settle_file_remove));
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

		// 20170126 kruddo - 임시저장된 파일 저장
		$tmp_file = $this->input->post( 'settle_tmp_file' );
		if($tmp_file != ''){
			$this->Business_model->settle_tmp_file_insert( $settle_no, $tmp_file );
		}
		// 20170126 kruddo - 임시저장된 파일 저장

		$_FILES = $files;

		// 파일 저장 옵션 초기화
		$config['upload_path'] = "./images/settle/";
		$config['encrypt_name'] = TRUE;
		$config['allowed_types'] = '*';

		$this->load->library('upload', $config);
		$this->upload->initialize($config);
		foreach( $files as $key => $val ){
			if ( !$this->upload->do_upload($key) ) {
				$error = array('error' => $this->upload->display_errors());
			}
		    else {
		        $data = array('upload_data' => $this->upload->data());

		        $input = array(
		                'settle_no' => $settle_no,
		                'file_name' => $data['upload_data']['file_name'],
		                'ori_name' => $val['name'],

		        );

		        $this->Business_model->settle_file_insert( $input );
		    }
		}

		//$settle_tmp_list = $this->Business_model->get_settle_tmp_list();
		//return_json(true, $settle_tmp_list);

		if($this->input->post( 'tmp_yn' ) == "Y"){
			$settle_tmp_list = $this->Business_model->get_settle_tmp_list();
			return_json(true, $settle_tmp_list);
		}
		else{
			return_json(true,'결재가 정상적으로 등록되었습니다.');
		}
		
	}



    // 기안 승인
	function settle_approve(){

		$settle_no = $this->input->post( 'settle_no' );
		$max_level = $this->input->post( 'max_level' );
		$my_level = $this->input->post( 'my_level' );

		// 본인이 최종 결제자일시
		if( $max_level ==  $my_level ){
			$this->Business_model->settle_approve_end( $settle_no, $max_level );
		}
		else if( $max_level > $my_level ){
			$this->Business_model->settle_approve_next( $settle_no, $max_level, $my_level );
		}
	}

	// 기안 승인
	function settle_return(){

	    $settle_no = $this->input->post( 'settle_no' );

	    $this->Business_model->settle_return( $settle_no, $this->session->userdata( 'ss_user_no' ) );
	}



	// 기안 취소
	function settle_cancel(){
	    //$this->func_lib->pr( $_POST );
	    $this->Business_model->settle_cancel( $this->input->post( 'settle_no' ) );
	    redirect('/business/settle/send');
	}

	// 기안 삭제
	function settle_del(){
	    $rs = $this->Business_model->settle_del( $this->input->post( 'settle_no' ) , $this->input->post('del_mode') );
	    return_json(true,'삭제되었습니다.');
	    // redirect('/business/settle/send');
	}

	/**
	 * 기안 완전삭제(DB삭제)
	 * @return [type] [description]
	 */
	function settle_terminate() {

	}

	// 참고 내용 추가
	function consult_add(){
	    //$this->func_lib->pr( $_POST );

	    $input = array(
	            'settle_no' => $this->input->post( 'settle_no' ),
	            'content' => $this->input->post( 'consult' ),
	            'user_no' => $this->session->userdata( 'ss_user_no' ),
	            'reg_date' => date('Y-m-d H:i:s')
	    );

	    $this->Business_model->settle_consult_insert( $input );


	    $json_res = array(
	            'content' => $this->input->post( 'consult' ),
	            'user_name' => $this->session->userdata( 'ss_name' ),
	            'reg_date' => date('Y-m-d H:i:s')
	    );


	    echo json_encode( $json_res );
	}

	function save_line_add(){
	    $input = array(
	            'user_no' => $this->session->userdata( 'ss_user_no' ),
	            'save_title' => $this->input->post( 'save_title' ),
	            'save_person' => $this->input->post( 'save_person' ),
	            'save_type' => $this->input->post( 'save_type' ),
	            'reg_date' => date('Y-m-d H:i:s')
	    );

	    $this->Business_model->save_line_add( $input );
	}

	/**
	 * 결제라인삭제
	 * @return [type] [description]
	 */
	function save_line_del() {
		$line_no = $this->input->post('line_no');
		$rs = $this->Business_model->save_line_del( $line_no );
		if($rs) {
			return_json(true,'결제라인이 삭제되었습니다.');
		}
		else {
			return_json(false,'잠시 후에 다시 시도해주세요');
		}
	}


	// 20170424 kruddo - 임시 저장 기능 추가
	// 임시 목록에서 선택
	public function settle_view(){
		$settle_no = $this->input->post( 'settle_tmp_no' );

		$json_res = array(
	            'view_data' => $this->Business_model->get_settle_view( $settle_no ),				// 결재 데이터
	            'approval' => $this->Business_model->get_settle_person( $settle_no, 0 ),			// 결재라인
	            'refer' => $this->Business_model->get_settle_person( $settle_no, 1 ),				// 참조라인
				'file' => $this->Business_model->get_settle_file( $settle_no),						// 첨부파일
				'draft' => $this->Business_model->get_settle_draft( $settle_no)						// 기안참조
	    );

		echo json_encode( $json_res);
	}

	// 임시 삭제
	function settle_tmp_del(){
	    $rs = $this->Business_model->settle_del( $this->input->post( 'settle_tmp_no' ) , $this->input->post('del_mode') );
		$settle_tmp_list = $this->Business_model->get_settle_tmp_list();
		return_json(true, $settle_tmp_list);

	    //return_json(true,'삭제되었습니다.');
	    // redirect('/business/settle/send');
	}

	// 20170424 kruddo - 임시 저장 기능 추가



}
