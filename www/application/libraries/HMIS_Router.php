<?php


class HMIS_Router extends CI_Router {


	/**
	 * Validates the supplied segments.
	 * Attempts to determine the path to
	 * the controller.
	 *
	 * @access private
	 * @param
	 *        	array
	 * @return array
	 */
	function _validate_request($segments) {
		// Does the requested controller exist in the root folder?
		if (file_exists( APPPATH . 'controllers/' . $this->fetch_directory() . $segments[0] . EXT )) {
			return $segments;
		}
		
		// Is the controller in a sub-folder?
		if (is_dir( APPPATH . 'controllers/' . $this->fetch_directory() . $segments[0] )) {
			// Set the directory and remove it from the segment array
			$this->_append_directory( $segments[0] );
			$segments = array_slice( $segments, 1 );
			
			if (count( $segments ) > 0) {
				// Does the requested controller exist in the sub-folder?
				if (! file_exists( APPPATH . 'controllers/' . $this->fetch_directory() . $segments[0] . EXT )) {
					return $this->_validate_request( $segments );
				}
			} else {
				$this->set_class( $this->default_controller );
				$this->set_method( 'index' );
				
				// Does the default controller exist in the sub-folder?
				if (! file_exists( APPPATH . 'controllers/' . $this->fetch_directory() . $this->default_controller . EXT )) {
					$this->directory = '';
					return array ();
				}
			}
			
			return $segments;
		}
		
		// Can't find the requested controller...
		show_404( $segments[0] );
	}


	/**
	 * Append the directory name
	 *
	 * @access public
	 * @param
	 *        	string
	 * @return void
	 */
	function _append_directory($dir) {
		$this->directory .= $dir . '/';
	}
}

?>