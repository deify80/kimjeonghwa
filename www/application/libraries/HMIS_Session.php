<?php 
/* ------------------------------------------------------------------------
 * CI Session Class Extension for AJAX calls.
 * ------------------------------------------------------------------------
 *
 * ====- Save as application/libraries/MY_Session.php -====
 */

class HMIS_Session extends CI_Session {

    // --------------------------------------------------------------------

    /**
     * sess_update()
     *
     * Do not update an existing session on ajax or xajax calls
     *
     * @access    public
     * @return    void
     */
    public function sess_update()
    {
        $CI = get_instance();

        if ( ! $CI->input->is_ajax_request())
        {
            parent::sess_update();
        }
    }

}

// ------------------------------------------------------------------------
/* End of file HMIS_Session.php */
/* Location: ./application/libraries/HMIS_Session.php */ 