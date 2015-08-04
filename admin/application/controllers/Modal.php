<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

	/*	
	   *	Developed by: Syncrypts
       *	Date	: 21 January, 2015
       *	Invenire Stock Inventory Manager
       *	http://codecanyon.net/user/syncrypts
    */

class Modal extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->database();
		/*cache control*/
		$this->output->set_header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
		$this->output->set_header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	}
	public function index()
	{
	}

	// DECLARATION: LOADS DYNAMIC MODALS
	function popup($page_name = '', $param2 = '', $param3 = '')
	{
		$page_data['param2'] = $param2;
		$page_data['param3'] = $param3;
		$this->load->view('backend/' . $this->session->userdata('login_type') . '/' . $page_name . '.php', $page_data);
	}
}

/* End of file Modal.php */
/* Location: ./application/controllers/Modal.php */
