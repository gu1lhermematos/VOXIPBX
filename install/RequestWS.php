<?php
/*
 define('URL_WS', 'http://www.snepstore.local/server.php');
 require_once('/var/www/zf_ws/library/ZF/Controller/Helper/lib/nusoap.php');

 class Zend_Controller_Action_Helper_RequestWS
 extends Zend_Controller_Action_Helper_Abstract {

 public $pluginLoader;

 private $resultado;

 public function __construct() {
 // TODO Auto-generated Constructor
 $this->pluginLoader = new Zend_Loader_PluginLoader ();
 }

 public function requestWS($method, $params = array()){
 $client = new nusoap_client(URL_WS);
 $err = $client->getError();

 return $this->resultado = $client->call($method, $params);
 }

 public function direct($method, $params = array())
 {
 return $this->requestWS($method, $params);
 }
 }
 */
require_once('Zend/Soap/Client.php');
class Zend_Controller_Action_Helper_RequestWS extends Zend_Controller_Action_Helper_Abstract {

	public $pluginLoader;

	private $resultado;

	public function __construct() {
		// TODO Auto-generated Constructor
		$this->pluginLoader = new Zend_Loader_PluginLoader ();  
	}

	public function requestWS($method, $params = null){
		$default_params = array(
	  'soap_version' => SOAP_1_1, 
      'uri' => 'urn:',
	 // 'location' => 'http://192.168.0.9/server.php');
     //'location' => 'http://openstore.agenciapontobr.com/server.php');
	 //'location' => 'http://192.168.0.6/snepstore/server.php');
	 // 'location' => 'http://192.168.0.13/snepstore/server.php'); 
         //'location' => 'http://snepstore.agenciapontobr.com/server.php');
	 'location' => 'http://snepstore.com.br/server.php'); 

      $soap = new Zend_Soap_Client(null, $default_params);
      $data = $soap->$method($params); 

      return $this->resultado = $data;
	}

	public function direct($method, $params = array())
	{
		return $this->requestWS($method, $params);
	}
}
