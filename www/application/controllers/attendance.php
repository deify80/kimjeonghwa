<?php
/**
 * 근태관리
 * 작성 : 2015.05.11
 * @author 이혜진
 */

if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

class Attendance extends CI_Controller {

	var $dataset;
	public function __construct() {
		parent::__construct();

		$this->param = $this->input->post(NULL, true);
		$this->yield = TRUE;

		$this->load->model( array (
			'User_model',
			'Attendance_model'
		) );
	}


	/**
	 * 근태설정
	 * @return [type] [description]
	 */
	public function settings() {
		$dept_info = $this->User_model->get_dept_list(); //부서리스트
		$team_info = $this->User_model->get_team_list(); //팀리스트
		$day_list = $this->config->item( 'yoil' ); //요일
		$hour_list = $minute_list = array();
		foreach(range(0,23) as $val) { $hour_list[] = str_pad($val, 2, '0', str_pad_left);}
		foreach(range(0,50,10) as $val) { $minute_list[] = str_pad($val, 2, '0', str_pad_left);}

		$datum = array(
			'cfg'=>array(
				'dept'=>$dept_info,
				'day'=>$day_list,
				'hour'=>$hour_list,
				'minute'=>$minute_list
			),			
			'team_info'=>$team_info
		);
		$this->_display('settings', $datum );
	}

	/**
	 * 근태결과
	 * @return [type] [description]
	 */
	public function logs() {
		$status_list = $this->config->item( 'attendance' ); //요일
		$dept_info = $this->User_model->get_dept_list(); //부서리스트
		$team_info = $this->User_model->get_team_list(); //팀리스트
		$datas = array(
			'cfg'=>array(
				'status'=>$status_list,
				'dept'=>$dept_info,
				'team'=>$team_info
			)
		);
		$this->_display('logs', $datas );
	}

	public function logs_list() {
		// pre($this->param);
		
		$page = $this->param['page'];
		$limit = ($this->param['limit'])?$this->param['limit']:PRE_PAGE;
		$offset = ($page-1)*$limit;
		

		//검색조건설정
		parse_str($this->input->post('search'), $assoc);
		$where = array();
		foreach($assoc as $k=>$v) {
			if(in_array($k, array('sf','price_type', 'category_sub','block'))) continue;
			if($v == 'all' || !$v) continue;

			switch($k) {
				case 'name':
					$where['user.name LIKE']="%{$v}%";
				break;
				case 'date_s' :
					$where['logs.date >='] = $v;
				break;
				case 'date_e' :
					$where['logs.date <='] = $v;
				break;
				case 'status' :
					$where['logs.status'] = $v;
				break;
				default :
					$where['logs.'.$k]=$v;
					break;
			}
		}

		$rs = $this->Attendance_model->select_logs($where, $offset, $limit);

		if($rs['count']['search'] > 0) {
			$list = array();
			$dept_info = $this->User_model->get_dept_list(); //부서리스트
			$team_info = $this->User_model->get_team_list(); //팀리스트
			$attendance_info = $this->config->item( 'attendance' ); //출근상태
			$day_list = $this->config->item( 'yoil' ); //요일
			$idx =  $rs['count']['search']-$offset;
			foreach($rs['list'] as $row) {
				$row['dept_name'] = $dept_info[$row['dept_code']];
				$row['team_name'] = $team_info[$row['team_code']];
				$row['status_text'] = $attendance_info[$row['status']];
				$row['w'] = date('w',strtotime($row['date']));
				$row['w_text'] = $day_list[$row['w']];
				$row['time_in'] = ($row['time_in'])?substr($row['time_in'],0,5):'00:00';
				$row['time_out'] = ($row['time_out'])?substr($row['time_out'],0,5):'00:00';
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

	public function logs_input() {
		$status_list = $this->config->item( 'attendance' ); //출근상태
		$dept_info = $this->User_model->get_dept_list(); //부서리스트
		$team_info = $this->User_model->get_team_list(); //팀리스트
		

		$hour_list = $minute_list = array();
		foreach(range(0,23) as $val) { $hour_list[] = str_pad($val, 2, '0', str_pad_left);}
		foreach(range(0,50,10) as $val) { $minute_list[] = str_pad($val, 2, '0', str_pad_left);}


		$log_no = $this->param['log_no'];
		$log_info = $this->Attendance_model->select_logs_user(array('logs.no'=>$log_no));
		

		$settings = $this->User_model->get_team_row(array('team_code'=>$log_info['team_code']));
		$log_info['dept_name'] = $dept_info[$log_info['dept_code']];
		$log_info['team_name'] = $settings['team_name'];
		$datas = array(
			'cfg'=>array(
				'hour'=>$hour_list,
				'minute'=>$minute_list,
				'status'=>$status_list
			),
			'settings'=>$settings,
			'rs'=>$log_info
		);

		$this->_display('logs_input', $datas );
	}

	/**
	 * 근태로그 일괄등록
	 * @return [type] [description]
	 */
	public function logs_batch() {
		$datas = array();
		$this->_display('logs_batch', $datas );	
	}

	/**
	 * 근태 수동등록
	 * @return [type] [description]
	 */
	public function logs_passive() {
		$status_list = $this->config->item( 'attendance' ); //출근상태
		$datas = array(
			'cfg'=>array(
				'status'=>$status_list
			)
		);
		$this->_display('logs_passive', $datas);
	}

	/**
	 * 근태통계
	 * @return [type] [description]
	 */
	public function statistics() {
		$srch_date = get_search_type_date();
		$datas = array(
			'cfg'=>array(
				'date'=>$srch_date
			)
		);
		$this->_display('statistics', $datas );
	}

	public function statistics_chart() {
		$type = $this->input->post('type');
		$this->_display('statistics_'.$type, $datas );
	}

	private function _display($tmpl, $datum) {
		$this->load->view('/support/attendance/'.$tmpl, $datum );
	}
}
?>