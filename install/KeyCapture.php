<?php 
class Zend_Controller_Action_Helper_KeyCapture extends Zend_Controller_Action_Helper_Abstract {
	
	public $xmlSnep;
	
	private $key;
	
	
	public function __construct() { 
		// TODO Auto-generated Constructor
		$this->pluginLoader = new Zend_Loader_PluginLoader();
	}
	
	/**
	 * @return the $key
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @param field_type $key
	 */
	public function setKey($key) {
		$this->key = $key;
	}

	public function keyCapture(){
		
		$cmd = 'sudo lshw -xml -class system > modules/updateModule/infoplaca.xml'; 
		
		$exemplo = shell_exec($cmd);
		
		return $this->readXML();
		 
	}
	function readXML(){
		
		$this->xmlSnep = simplexml_load_file('modules/updateModule/infoplaca.xml');
		
		//$teste = $this->xmlSnep->configuration->setting;
		
		/*foreach($this->xmlSnep->configuration->setting as $teste){
			echo $teste;
			if($idKey['id'] == "uuid"){
				$this->setKey($idKey['value']);
			}
		}*/
		$arrXmlLength = count($this->xmlSnep->configuration->setting);
		for($i = 0; $i < $arrXmlLength; $i++){
			if($this->xmlSnep->configuration->setting[$i]['id'] == "uuid"){
				$this->setKey($this->xmlSnep->configuration->setting[$i]['value']);
			}
		}
		
		//$this->setKey($this->xmlSnep->configuration->setting[3]['value']);
		
		$this->keyDestroy();
		
		return $this->getKey();
	}
	
	public function direct()
	{
		return $this->keyCapture(); 
	}
	
	public function keyDestroy(){
		//unlink('modules/updateModule/infoplaca.xml');
	}
}


