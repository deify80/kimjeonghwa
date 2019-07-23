<?php
if (! defined( 'BASEPATH' )) exit( 'No direct script access allowed' );

if(!function_exists('resize_image')) {
	function resize_image($config) {
		$CI =& get_instance();
		$CI->load->library('image_lib');

		$config_default = array(
			'image_library' => 'gd2',
			'maintain_ratio' => TRUE,
			'create_thumb' => TRUE,
			'thumb_marker' => '_thumb',
			'width' => 150,
			'height' => 150
		);

		$config = array_merge($config_default, $config);

		$CI->image_lib->clear();
		$CI->image_lib->initialize($config);

		if (!$CI->image_lib->resize()) {
			return false;
		}
		else {
			return true;
		}
	}
}
?>