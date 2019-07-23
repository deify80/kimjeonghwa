<?php
/**
 * 작성 : 2014.10.20
 * 수정 :
 *
 * @author 이미정
 */
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Request extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model( array (
				'Consulting_model' 
		) );
		
		$this->load->library( 'user_agent' );
	}



	public function insert() {
		
		$type_list = array (
				'1'=>'W',
				'2'=>'M' 
		);
		
		// 전화번호에 문자가 있다면 messenger 로 처리
		$tel = ($this->input->post( 'phone1' ) && $this->input->post( 'phone2' )) ? $this->input->post( 'phone1' ) . $this->input->post( 'phone2' ) . $this->input->post( 'phone3' ) : $this->input->post( 'phone' );

		$pattern = '/[a-zA-Z]/';
		if (preg_match( $pattern, $this->input->post( 'phone' ) )) {
			$messenger = $this->input->post( 'phone' );
			$tel = '';
		}
		
		// 예상비용, 수술예정시기
		$memo = ($this->input->post( 'prince' ) !='') ? ' 예상비용 - ' . $this->input->post( 'prince' ) : '';
		$memo .= ($this->input->post( 'ondata' ) != '') ? ' 수술예정시기 - ' . $this->input->post( 'ondata' ) : '';
		
		$birth = (set_null($this->input->post( 'age' )) != '')? $this->input->post( 'age' ):$this->input->post( 'birth' );
		
		//생년월일
		if (preg_match( $pattern, $birth) || preg_match('/[\xA1-\xFE][\xA1-\xFE]/', $birth)) {
			$birth = '';
		}	
		
		$birth = preg_replace("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "", $birth);		
		if ($birth != '' && strlen($birth) <= 2) 	$birth = date('Y') - $birth - 1;

		//자리일경우 (앞에서 4자리가 1900보다 크거나 작을때)
		if (strlen($birth) == 6) {
			$birth = (intval(substr($birth, 0, 4)) > 1900) ? '19'.$birth:$birth;
		} 		
		
		$birth = ($birth !='') ? str_pad( $birth, 8, '0', STR_PAD_RIGHT ) : '';		
		
		$email = (set_null($this->input->post( 'email' )) != '')?  set_null($this->input->post( 'email' )):set_null($this->input->post( 'mail' ));
		
		$input = null;
		$input['tel'] = preg_replace("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "", $tel);
		$input['messenger'] =  $messenger ;
		$input['biz_id'] = $this->input->post( 'id' );
		$input['name'] = $this->input->post( 'name' );
		$input['birth'] = $birth;
		$input['email'] =  $email;
		$input['call_time'] =  set_null($this->input->post( 'time' ));
		$input['memo'] = $this->input->post( 'mh_message' );
		$input['type'] = $type_list[$this->input->post( 'kind' )];
		$input['media'] = ($this->input->post('affiliate') != '') ?  'firstcpa' : set_null($this->input->post( 'type' ));
		$input['reg_date'] = TIME_YMDHIS;
		$input['category'] = ($this->input->post( 'gubun' ) == '')? $this->input->post( 'category' ):$this->input->post( 'gubun' );
		$input['path'] = ($input['category']=='1303')?'R':'L';
		$input['hst_code'] = 'H000';
		$input['request_url'] = $this->input->post( 'rurl' );
		$input['os'] = $this->agent->platform();
		$input['browser'] = $this->agent->browser();
		$input['version'] = $this->agent->version();		
		$input['memo'] = $memo;
		$input['ip'] = $this->input->post( 'ip' );
		$input['tmp'] = $this->input->post('age');
		
		$this->Consulting_model->db_insert( $input );
		
	}
}