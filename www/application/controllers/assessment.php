<?php
/**
 * 근태관리
 * 작성 :
 * @author 창훈
 */

if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Assessment extends CI_Controller {

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

		$this->dept_info = $this->User_model->get_dept_list(); //부서리스트
		$this->team_info = $this->User_model->get_team_list(); //팀리스트
		$this->hst_info = $this->Manage_model->get_hst_code();
		$this->biz_list_val = $this->session->userdata( 'ss_my_biz_list' ) ; //병원
		$this->position_list = $this->config->item( 'position_code' ); //직급
		$this->duty_list = $this->config->item( 'duty_code' ); //직책
	}
		
	//평가 설정
	public function index() {			
		$this->_display('assessment/index', $datas );		
	}	

	//평기 리스트
	function inout_list_paging() {
		
		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;		
		
		$namecategory = $this->config->item('nameyesno') ;
		$strexposurecategory = $this->config->item('strexposure') ;


		$page = $this->input->post('page');
		$limit = ($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;

		$rs = $this->Assessment_model->select_inout_paging($where, $offset, $limit);
				
		if($rs['count']['search'] > 0) {
			$list = array();
			$idx =  $rs['count']['total']-$offset;
			foreach($rs['list'] as $row) {

				$row['idx'] = $idx;
				$row['biz_list_name'] =  $this->biz_list_val[$row['biz_info']] ;
				$row['is_result_key'] =  $row['is_result'] ;
				$row['is_result_val'] =  $namecategory[$row['is_result']] ;
				$row['is_cocy_key'] =  $row['is_cocy'] ;
				$row['is_cocy_val'] =  $namecategory[$row['is_cocy']] ;
				$row['is_ifs_key'] =  $row['is_ifs'] ;
				$row['is_ifs_val'] =  $namecategory[$row['is_ifs']] ;
				$row['is_exposure_key'] =  $row['is_exposure'] ;
				$row['is_exposure_val'] =  $strexposurecategory[$row['is_exposure']] ;
				$row['is_rating_key'] =  $row['is_rating'] ;
				$row['is_rating_val'] =  $strexposurecategory[$row['is_rating']] ;

				$list[] = $row;
				$idx--;
			}
		}
		//페이징
		$paging_config = array(
				'total'=>$rs['count']['search'],
				'block_size'=>10,
				'list_size'=>$limit,
				'page_current'=>$page
		);
		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();
	
		$return = array(
				'count'=>$rs['count'],
				'list'=>$list,
				'paging'=>$paging
		);
	
		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}

	//평가 셋팅
	public function index_setting() {

		$seq_no  = $this->input->post( 'inout_no') ;
		$row = $this->Assessment_model->inout_settion_view ( $seq_no ) ;

		$datas = array (
	            'biz_info'=>$this->biz_list_val,
	            'row'=>$row
	    );
		$this->_display('assessment/index_setting', $datas );				
	}

	///검색
    public function get_team_json() {
		$dept_code = $this->segs[3];
		$list = $this->Business_model->get_team_list( $dept_code );
		echo json_encode( $list );
	}
	
	public function get_dept_json() {
	    $hst_code = $this->segs[3];
	    $biz_id = $this->segs[4];
	    $list = $this->Business_model->get_dept_list( $hst_code, $biz_id );
	    echo json_encode( $list );
	}

	// 펑가설정 검색
	public function get_user_info_json() {
		$what = $this->input->post( 'what' );
		$code = $this->input->post( 'code' );
		$seqno = $this->input->post( 'seq_nos' );

		$where = null;

	    $where[] = "assessment.re_no=".$seqno ;

	    if( $what == "hst" ) $where[] = "user_info.hst_code='".$code."'" ;
	    if( $what == "dept" ) $where[] = "user_info.dept_code=".$code ;
	    if( $what == "team" || $what == "team_s" ) $where[] = "user_info.team_code=".$code ;
	    if( $what == "search" ) $where[] = "user_info.name='".$code."'" ;

		$list = $this->Assessment_model->get_user_info_list( $where );
		//print_r($list);
		$data = array();
		foreach( $list as $key => $val ){
			$data['_'.$key]	= $val;
		}		
		echo json_encode( $data );
	}	

	// 펑가설정 검색2
	public function get_user_info_json_right() {
	
		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;		

		$where[] = " assessment.no in (".$code_arr.") "  ;
		$list = $this->Assessment_model->get_user_info_list( $where );

		$data = array();
		foreach( $list as $key => $val ){
			$data['_'.$key]	= $val;
		}		
		echo json_encode( $data );
	}	

	//평가설정 등록/수정
	public function inout_insert() {	

		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;		

		if($inout_no) {
			$mode = 'update';
			$rs = $this->Assessment_model->inout_settion_view (  $inout_no ) ;
		}
		else {
			$mode = 'insert';
		}
		
		// pre($this->session->userdata);
		$datum = array(	
				'mode'=>$mode,
				'rs'=>$rs
		);		
		$this->_display('assessment/index_input', $datum );
	}

	// 성과관리
	public function lists() {			

		$datas = array(	
				'dept_info'=>$this->dept_info,
				'team_info'=>$this->team_info,
				'biz_list_val'=>$this->biz_list_val,
				'position_list'=>$this->position_list,
				'duty_list'=>$this->duty_list
		);		

		$this->_display('result/assess_index', $datas );
	}

	//평가설정
	public function assess_select_list() {
	
		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;		

		$page = $this->input->post('page');
		$limit = ($this->input->post('limit'))?$this->input->post('limit'):PER_PAGE;
		$offset = ($page-1)*$limit;
	
		$where = null ;

		if ( $pcode == 'user') {
	//		$where['user_info.user_id'] = $this->session->userdata( 'ss_user_id' );				
			$where['assessment_settion.is_exposure'] = 'Y' ;
		}

		if ( $syear ) { $where['assessment_settion.syear'] = $syear ; } ;	
		if ( $firsthalf ) { $where['assessment_settion.firsthalf'] = $firsthalf ; } ;	
		if ( $biz_info ) $where['assessment_settion.biz_info'] = $biz_info ;
		if ( $dept_info ) $where['user_info.dept_code'] = $dept_info ;
		if ( $team_info ) $where['user_info.team_code'] = $team_info ;
		if ( $position_list ) $where['user_info.position_code'] = $position_list ;
		if ( $duty_list ) $where['user_info.duty_code'] = $duty_list ;
		if ( $goods_name ) $where['user_info.name LIKE'] = "%".$goods_name."%" ;

		//	$this->output->enable_profiler(true) ;	
		$rs = $this->Assessment_model->select_assessindex_list($where, $offset, $limit);
		if($rs['count']['search'] > 0) {

			$list = array();	
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {

				$c_greetings_princ = 0 ;
				$greetings_usercheck = 'N' ;
				$greetings_check = 'N' ;

				$rowavg = $this->Assessment_model->select_inout_avg( $row[no] );
				$rowavgw = $this->Assessment_model->select_inout_avgs( $row[no] , 'W');	

				if ( $pcode == 'user' ) {
					$resultuser = $this->Assessment_model->select_inout_paging4( $row['no'] );
					$listuser = array();
					$competency_vals='' ;
					foreach($resultuser['listuser'] as $rowuser) {
						$competency_vals[] = $rowuser['no']  ;
					}
					if (  $competency_vals ) {
						foreach($competency_vals as $ii => $val2) :
							$rowavge2 = $this->Assessment_model->select_inout_avgs(  $row['no'], 'E' , $val2  );								
//							$rowavge2 = $this->Assessment_model->select_inout_avgs( $val2 , 'E' , $row['no']);	

							if ( $rowavge2['c_greetings_princ'] == 0 ) {
								$greetings_usercheck = 'Y' ;
							}

						endforeach;
					}
					if ( $competency_vals == '' ) $greetings_usercheck = 'Y' ;
				} 

				// 다면
				if (  $row['competency_val'] ) {

					$competency = explode ( ',' , $row['competency_val'] ) ;

					if (count($competency)) {				
						foreach($competency as $i => $val) :
							$rowavge = $this->Assessment_model->select_inout_avgs( $val , 'E' , $row['no']);	
							if ( $rowavge['c_greetings_princ'] == 0  ) {
								$greetings_check = 'Y' ;
							}
							$c_greetings_princ +=  $rowavge['c_greetings_princ'] ;
						endforeach ;
						$row['c_greetings_princ'] =  round( $c_greetings_princ / count( $competency ) ) ;
					}
				} else {
						$greetings_check = 'Y' ;
						$row['c_greetings_princ'] = 0 ;
				}

				$row['is_result'] =  $row['is_result'] ;
				$row['is_cocy'] =  $row['is_cocy'] ;
				$row['is_ifs'] =  $row['is_ifs'] ;
				$row['greetings_check'] =  $greetings_check ;
				$row['greetings_usercheck'] =  $greetings_usercheck ;
				$row['biz_list_name'] =  $this->biz_list_val[$row['biz_infos']] ;
				$row['dept_name'] = $this->dept_info[$row['dept_code']];
				$row['team_name'] = $this->team_info[$row['team_code']];
				$row['hst_name'] = $this->hst_info[$row['hst_code']];
				$row['position_name'] = $this->position_list[$row['position_code']];
				$row['duty_name'] = $this->duty_list[$row['duty_code']];
				// 성과 
				$row['progress_sum'] = number_format ( round( $rowavg['progress'] ) ) ;
				$row['progress'] = round ( $row['a_result_percent'] * round( $rowavg['progress'] ) / 100  );
				$row['assessment'] =  round ( $row['a_competency_percent'] * round ( $rowavg['assessment'] ) / 100 ) ;
				$row['a_greetings_princ'] =  round ( $row['a_various_percent'] * round ( $rowavg['a_greetings_princ'] ) / 100 ) ;		
				$row['result_princ_t'] =  round ( $row['result_percent'] * ( $row['progress'] + $row['assessment'] + $row['a_greetings_princ'] ) /100  ) ; 
				$row['result_princ_val'] =  number_format ( $rowavg['assessment'] ) ; 

				// 역량
				$row['b_user_princ_sum'] = number_format ( $rowavgw['b_user_princ'] ) ;
				$row['b_user_princ'] = round ( $row['b_result_percent'] * round( $rowavgw['b_user_princ'] ) / 100  );
				$row['b_team_princ'] =  round ( $row['b_competency_percent'] * round( $rowavgw['b_team_princ'] ) / 100  ); 
				$row['b_greetings_princ'] =  round ( $row['b_various_percent'] * round( $rowavgw['b_greetings_princ'] ) / 100  ); 
				$row['competency_princ_t'] =  round ( $row['competency_percent'] * ( $row['b_user_princ'] + $row['b_team_princ'] + $row['b_greetings_princ'] ) / 100  ) ; 			
				$row['competency_princ_val'] =  number_format ( round ( $rowavgw['b_team_princ'] ) )  ; 			

				$row['ifevaluation_princ_t'] =  round ( $row['various_percent'] * ( $row['c_greetings_princ'] ) / 100  ) ; 
	
				//최종합산
				$row['total_princ'] =  $row['result_princ_t'] + $row['competency_princ_t'] + $row['ifevaluation_princ_t']  ;

				if ( $row['total_princ'] >= 90)  {
					$rating = 'A'  ;
				} else if ( $row['total_princ'] >= 80 ) {
					$rating = 'B'  ;
				} else if ( $row['total_princ'] >= 70 ) {
					$rating = 'C'  ;
				} else if ( $row['total_princ'] >= 60 ) {
					$rating = 'D'  ;
				} else {
					$rating = 'E'  ;
				} 

				$row['ratingval'] =  $rating ;				  

				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}
		//페이징
		$paging_config = array(
			'total'=>$rs['count']['search'],
			'block_size'=>10,
			'list_size'=>$limit,
			'page_current'=>$page
		);

		$this->load->library('Pagination_lib', $paging_config, 'pagination');
		$paging = $this->pagination->getPageSet();
	
		$return = array(
			'count'=>$rs['count'],
			'list'=>$list,
			'paging'=>$paging
		);
		
		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);	
		}
	}
	
	public function details_list_paging( ) {

		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;

		$wheres = null ;
		$wheres['assessment.re_no'] = $inout_no ;					
		$wheres['assessment.no'] = $user_name_str ;				
		$rowse = $this->Assessment_model->select_inout_paging3_view($wheres);

		$ifevaluation = explode ( ",",$rowse['ifevaluation'] ) ;
		$adetails_val = explode ( ",",$rowse['adetails_val'] ) ;

		$listup[] = $rowse;
		$where['code'] = $code ;		
		$rs = $this->Assessment_model->select_inout_details($where);
		
		if($rs['count']['search'] > 0) {
			$list = array();

			$idx =  $rs['count']['total']-$offset;
			foreach($rs['list'] as $row) {
				$row['idx'] = $idx;

				if (in_array($row['no'], $ifevaluation)) {
					$row['no_check'] = "checked" ;
				} else {
					$row['no_check'] = "" ;
				}

				if (in_array($row['no'], $adetails_val)) {
					$row['evaluation_check'] = "checked" ;
				} else {
					$row['evaluation_check'] = "" ;
				}

				$list[] = $row;
				$idx--;
			}
		}

		$return = array(
				'count'=>$rs['count'],
				'list'=>$list,
				'listup'=>$listup,
				'paging'=>$paging
		);
		
		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}		
		
	}

	//성과관리 팝업
	public function assess_view_list() {

		$int_no = $this->input->post('inout_no');		
		$mode = $this->input->post('modes');				
		$pcode = $this->input->post('pcode');				
		$rcode = ( $pcode == 'user' ? "users" : "result" ) ;	

		$row = $this->Assessment_model->select_inout_view( $int_no );		
				
		$row['dept_name'] = $this->dept_info[$row['dept_code']];
		$row['team_name'] = $this->team_info[$row['team_code']];
		$row['hst_name'] = $this->hst_info[$row['hst_code']];
		$row['position_name'] = $this->position_list[$row['position_code']];
		$row['duty_name'] = $this->duty_list[$row['duty_code']];		
		$row['biz_list_name'] =  $this->biz_list_val[$row['biz_info']] ;
		$file_name = explode ( "&&" , $row['file_uplode_name'] ) ;
		$row['file_name_olg'] = $file_name[1] ;
		$row['file_name_val'] = $file_name[0] ;

		$data = array('row'=>$row , 'pcode'=>$rcode );
		
		$this->_display($rcode.'/assess_view_'.$mode, $data );		
	}

	public function select_assess_view() {
	
		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;
		
		if ( !$ser_code ) $ser_code = 'W' ; 
		$where['assessment_down.code'] = $ser_code ;
		
		if ( $ser_code == 'E' ) {
			$where['b_reidx'] = $inout_no ;				
			$where['a_reidx'] = $mseq_no ;				
		} else {
			$where['a_reidx'] = $inout_no ;				
		}

		$rs = $this->Assessment_model->select_assess_list($where);

		if($rs['count']['search'] > 0) {
	
			$list = array();
		
			$idx =  $rs['count']['total']-$offset;
			
			foreach($rs['list'] as $row) {
				$row['dept_name'] = $this->dept_info[$row['dept_code']];
				$row['team_name'] = $this->team_info[$row['team_code']];
				$row['hst_name'] = $this->hst_info[$row['hst_code']];
				$row['position_name'] = $this->position_list[$row['position_code']];

				$user_total += round ( $row['b_user_princ'] ) ;
				$team_princ_total += round ( $row['b_team_princ'] ) ;
				$greetings_princ_total += round ( $row['b_greetings_princ'] ) ;
				$c_greetings_princ_total += round ( $row['c_greetings_princ'] ) ;

				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		$loop = array();
		$loops['user_total'] =  round (  $user_total / $rs['count']['search'] ) ;	
		$loops['team_princ_total'] =  round (  $team_princ_total / $rs['count']['search'] ) ;	
		$loops['greetings_princ_total'] =  round (  $greetings_princ_total / $rs['count']['search'] )  ;	
		$loops['c_greetings_princ_total'] =  round (  $c_greetings_princ_total / $rs['count']['search'] ) ;	

		$loop[] = $loops;
	
		$return = array(
				'count'=>$rs['count'],
				'list'=>$list,
				'loop'=>$loop
		);
	
		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}	
	
	public function select_assess_result_view() {
	
		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;

		$where['a_reidx'] = $inout_no ;		
		$rs = $this->Assessment_model->select_assess_reslt_list($where );

	    // 파일
	    $settle_file = $this->Assessment_model->get_assessment_file( $inout_no );
	

		if($rs['count']['search'] > 0) {
	
			$list = array();

			$idx =  $rs['count']['total']-$offset;
			
			foreach($rs['list'] as $row) {
				$row['dept_name'] = $this->dept_info[$row['dept_code']];
				$row['team_name'] = $this->team_info[$row['team_code']];
				$row['hst_name'] = $this->hst_info[$row['hst_code']];
				$row['position_name'] = $this->position_list[$row['position_code']];

				$a_result_percent = $row['a_result_percent'] ;
				$a_competency_percent = $row['a_competency_percent'] ;
				$a_various_percent = $row['a_various_percent'] ;

//				$row['duty_name'] = $row['b_greetings_princ'];
				$a_wdight += $row['pweight'] ; 

				$a_progress = ( $row['pweight'] * $row['progress'] ) / 100 ;
				$a_progress_total += round($a_progress) ;

				$a_assessment = ( $row['pweight'] * $row['assessment'] ) / 100 ;
				$a_assessment_total += round($a_assessment) ;

				$a_greetings = ( $row['pweight'] * $row['a_greetings_princ'] )  / 100 ;
				$a_greetings_total += round($a_greetings) ;

				$row['idx'] = $idx;
				$list[] = $row;
				$idx--;
			}
		}

		$loop = array();
/*
		$loops['a_progress_total'] = round ( $a_result_percent * $a_progress_total / 100 ) ;	
		$loops['a_assessment_total'] = round ( $a_competency_percent * $a_assessment_total / 100 ) ;	
		$loops['a_greetings_total'] = round ( $a_various_percent * $a_greetings_total  / 100 ) ;	
*/
		$loops['a_wdight_total'] = $a_wdight;	
		$loops['a_progress_total'] = $a_progress_total;	
		$loops['a_assessment_total'] = $a_assessment_total;	
		$loops['a_greetings_total'] = $a_greetings_total;	

		$loop[] = $loops;

		$return = array(
				'count'=>$rs['count'],
				'list'=>$list,
	            'settle_file' => $settle_file,
				'loop'=>$loop
		);
	
		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}

	public function assess_view_three_setting() {
		
		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;

		$seq_no  = $this->input->post( 'inout_no') ;
		$row = $this->Assessment_model->inout_settion_view (  $seq_no ) ;
		$row2 = $this->Assessment_model->inout_assessment_view (  $seq_no ) ;

		$datas = array (
	            'biz_info'=>$this->biz_list_val,
	            'inout_no'=>$inout_no,
	            'row'=>$row,
	            'row2'=>$row2
	    );
				
		$this->_display('result/assess_three_setting', $datas );	
	}
	
	public function select_assess_view_three() {
	
		foreach( $_POST as $key => $val ) :
		$$key = $val  ;
		endforeach;
	
		if ( $umode == 'user' ) {
			$row2 = $this->Assessment_model->inout_assessment_view_re ( $re_userid , $re_no ) ;
			$where[] = " CONCAT(',',competency_val,',') LIKE '%,".$row2['no'].",%'; "  ;
		} else {
			$where[] = " assessment.no in (".$competency_val.") "  ;
		}

		$rs = $this->Assessment_model->select_inout_paging3( $where );
	
		if($rs['count']['search'] > 0) {
	
			$list = array();
	
			$idx =  $rs['count']['total']-$offset;
				
			foreach($rs['list'] as $row) {

				if ( $umode == 'user' ) {
					$rowavge = $this->Assessment_model->select_inout_avgs(  $inout_no , 'E' ,  $row[no] );	
				} else {
					$rowavge = $this->Assessment_model->select_inout_avgs( $row[no] , 'E' , $inout_no   );	
				}
				$row['idx'] = $idx;
				$row['c_greetings_princ'] =  $rowavge['c_greetings_princ'] ;
				$c_greetings_princ += $rowavge['c_greetings_princ'] ;

				$list[] = $row;
				$idx--;
			}
		}

		$loop = array();
		$loops['c_greetings_princ_total'] =  round ( $c_greetings_princ / $rs['count']['search'] ) ;	
		$loop[] = $loops;

		$return = array(
				'count'=>$rs['count'],
				'list'=>$list,
			    'loop'=>$loop
		);
	
		if($rs['count']['search']>0) {
			return_json(true, '', $return);
		}
		else {
			return_json(false, '', $return);
		}
	}	
	
	public function assess_view_three_popup() {

		$int_no = $this->input->post('inout_no');
		$moseqno = $this->input->post('moseqno');	
		$pcode = $this->input->post('pcode');	
		$rcode = ( $pcode == 'user' ? "users" : "result" ) ;	

		$row = $this->Assessment_model->select_inout_view( $int_no );
	
		$row['dept_name'] = $this->dept_info[$row['dept_code']];
		$row['team_name'] = $this->team_info[$row['team_code']];
		$row['hst_name'] = $this->hst_info[$row['hst_code']];
		$row['position_name'] = $this->position_list[$row['position_code']];
		$row['duty_name'] = $this->duty_list[$row['duty_code']];
		$row['biz_list_name'] =  $this->biz_list_val[$row['biz_info']] ;

		$data = array('row'=>$row , 'moseqno'=>$moseqno);
		
		$this->_display($rcode.'/assess_view_three_popup', $data );	
	}
	
	// 파일 다운로드
	function download_file()
	{
	    $this->load->helper('download');
	    $file_name = $this->input->get( 'file_name' );
	    $ori_name = $this->Assessment_model->get_ori_name( $file_name );	    
	    $data = file_get_contents("./DATA/assessment/".$file_name);
	    
	    // 한글 파일명 IE에서 깨짐 처리
	    force_download(mb_convert_encoding($ori_name, 'euc-kr', 'utf-8'), $data);
	}
	


	//user
	public function user_lists() {
		$this->_display('users/user_assess_index', $datas );
	}

	private function _display($tmpl, $datum) {
		$this->load->view('/assess/'.$tmpl, $datum );
	}

}
?>