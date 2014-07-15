<?php
App::uses('Controller', 'Controller');
class AppController extends Controller {
	
	function check_alert_msg($type=null,$msg=null){
		if ($type == "alert alert-success"){
			return '<div class="alert alert-success">' . $msg . '</div>';
		}
		else if ($type == "alert alert-info"){
			return '<div class="alert alert-info">' . $msg . '</div>';
		}
		else if ($type == "alert alert-waring"){
			return '<div class="alert alert-waring">' . $msg . '</div>';
		}
		else{
			return '<div class="alert alert-danger">' . $msg . '</div>';
		}
	}//check_alert_msg ends
}
