<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );



class Main extends CI_Controller {



	public function __construct() {
		parent::__construct();
		$this->load->model( array (
			'Consulting_model',
			'Manage_model'
		) );
		$this->yield = TRUE;
	}



	public function msg() {

		$this->page_title = "알림";

		$path_list = $this->config->item( 'all_path' );
		$main_category = $this->Manage_model->get_code_item( '01' );
		$sub_category = $this->Manage_model->get_code_item( '02' );

		$where['SUBSTRING(reg_date,1,8)'] = date( 'Ymd' );
		if ($this->session->userdata( 'ss_dept_code' ) == '90') $result = $this->Consulting_model->get_cst_list( 0, 100, '', $where, 'cst_seqno', 'DESC' );
		foreach ( $result as $i => $row ) {
			$row['path'] = $path_list[$row['path']];
			$row['main_category'] = $main_category[$row['main_category']];
			$row['sub_category'] = $sub_category[$row['sub_category']];
			$list[] = $row;

			// $list[$i]->cst_seqno = $row['cst_seqno'];
			// $list[$i]->path = $path_list[$row['path']];
			// $list[$i]->name = $row['name'];
			// $list[$i]->main_category = $main_category[$row['main_category']];
			// $list[$i]->sub_category = $sub_category[$row['sub_category']];
		}

		$data = array (
			'list'=>$list
		);
		$this->_render( 'index', $data);
	}


	private function _render($tmpl, $datum, $layout='default') {
		$tpl = "/main/{$tmpl}.html";
		$this->layout_lib->default_($tpl, $datum,$layout);
		$this->layout_lib->print_();
	}

}
