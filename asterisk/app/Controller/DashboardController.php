<?php
App::uses('UsersController', 'Controller');

class DashboardController extends UsersController {
	public $name = 'Dashboard';
	public $helpers = array('Form','Session','Html');
	public $components = array('Paginator');
	 
	public $paginate = array(
        'limit' => 100,
        'order' => array(
            'Controle.id' => 'desc'
        )
    );
	
	function dashboard() {
		$this->set('thisUser',$this->Session->read('User'));
		$this->set('page','dashboard');
		$this->loadModel('Controle');
		
		//make the array containing the last third days
		$k=0;
		for($i=30; $i>=0; $i--){
			$today = new DateTime(date("Y-m-d"));
			$today = $today->sub(new DateInterval("P" . $i . "D"));
			$today = $today->format("d-m-y");
			$arrDays[$k] = $today;
			$k++;
		}
		
		//start looking for the values
		$arrDataGeneral = array(); //array with all data from the database
		$i=0;
		foreach($arrDays as $dayNow){
			$this->Controle->create();
			$arrDataGeneral[$i]['day_to_show'] = $dayNow;
			$arrDataGeneral[$i]['data_to_show'] = count($this->Controle->find('all',array('conditions'=>array('data'=>$dayNow))));
			$i++;
		}
		$this->set('graphStartOn',explode("-",$arrDays[0]));
		$this->set('allCalls',$arrDataGeneral);
		
		
		//start to search and make groups for each "operadora" we have on the database
		$this->Controle->create();
		$operators = $this->Controle->find('all',array('group'=>array('operadora')));
		
		$arrayCallsOperators = array();
		$i=0;
		$k=0;
		foreach($operators as $operator){
			//$operator['Controle']['operadora']
			$this->Controle->create();
			$arrayCallsOperators[$i]['operadora'] = $operator['Controle']['operadora'];
			$arrayCallsOperators[$i]['total'] = 0;
			foreach($arrDays as $day){
				$this->Controle->create();
				$arrayCallsOperators[$i]['total'] += count($this->Controle->find('all',array('conditions'=>array('data'=>$day,'operadora'=>$arrayCallsOperators[$i]['operadora']))));
			}
			$arrayCallsOperators[$i]['total'] = $arrayCallsOperators[$i]['total'] / count($arrDays);
			$i++;
		}
		
		$this->set('callOperators',$arrayCallsOperators);
	}
	
	function graphs(){
		$this->set('thisUser',$this->Session->read('User'));
		$this->set('page','graphs');
		
		$this->loadModel('Controle');
		$operators = $this->Controle->find('all',array('group'=>array('operadora'),'fields'=>array('operadora')));
		$this->set('operators',$operators);
	}
	
	function call_history(){
		$this->set('thisUser',$this->Session->read('User'));
		$this->set('page','call_history');
		$this->loadModel('Controle');
		
		$this->Paginator->settings = $this->paginate;
		
		$data = $this->Paginator->paginate('Controle');
	    $this->set('history', $data);
		
		
	}
	
	
	
	function gen_custom_graph(){
		$this->set('thisUser',$this->Session->read('User'));
		$this->set('page','graphs');
		$this->loadModel('Controle');
		
		if ($this->request->isPost()){
			$this->loadModel('Controle');
			
			$initialDate = $this->data['generate_custom_graph']['firstDate'];
			$finalDate = $this->data['generate_custom_graph']['secondDate'];
			$initialDate = explode("-",$initialDate);
			$finalDate = explode("-",$finalDate);
			$initialDate = $initialDate[2] . "-" . $initialDate[1] . "-" . $initialDate[0];
			$finalDate = $finalDate[2] . "-" . $finalDate[1] . "-" . $finalDate[0];
			$datetime1 = new DateTime($initialDate);
			$datetime2 = new DateTime($finalDate);
			$interval = $datetime1->diff($datetime2);
			$interval = $interval->format('%a');
			
			
			$filterOperatorsGoTo = $this->data['generate_custom_graph']['operatorFilterGoesTo'];
			//checkboxes filterFor$k, where $k = filterOperatorsGoTo, start on 0 and go until we have checkboxes
			$arrOperators = array();
			for ($i=0; $i < $filterOperatorsGoTo; $i++){
				if (isset($this->data['filterFor' . $i])){
					$arrOperators[] = trim($this->data['filterFor' . $i]);
				}
			}
			
			//generating days array
			$k=0;
			for($i=$interval; $i>=0; $i--){
				$today = new DateTime(date("Y-m-d"));
				$today = $today->sub(new DateInterval("P" . $i . "D"));
				$today = $today->format("d-m-y");
				$arrDays[$k] = $today;
				$k++;
			}
			
			
			$finalArray = array();
			$i=0; //helpers
			$k=0; //helpers
			foreach($arrOperators as $operator){
				$finalArray[$i]['operator']['name'] = $operator;
				foreach($arrDays as $day){
					$this->Controle->create();
					$finalArray[$i]['operator']['days'][$k] = count($this->Controle->find('all',array('conditions'=>array('operadora'=>$operator,'data'=>$day))));
					$k++;
				}
				$k=0;
				$i++;
			}
			
			$this->set('arrFinal',$finalArray);
			$this->set('intervalDays',$arrDays);
		}//isPost end
	}
	
	function list_users(){
		$this->set('thisUser',$this->Session->read('User'));
		$this->set('page','user_list');
		$this->loadModel('User');
		
		$this->set('users',$this->User->find('all'));
	}
	function log_export(){
		$this->set('thisUser',$this->Session->read('User'));
		$this->set('page','log_export');
		
		
		$this->loadModel('Controle');
		$operators = $this->Controle->find('all',array('group'=>array('operadora'),'fields'=>array('operadora')));
		$this->set('operators',$operators);
	}
	
	function generate_excel(){
		$this->autoRender = false;
		$this->loadModel('Controle');
		if ($this->request->isPost()){
			$initialDate = $this->data['export_calls']['firstDate'];
			$finalDate = $this->data['export_calls']['secondDate'];
			$initialDate = explode("-",$initialDate);
			$finalDate = explode("-",$finalDate);
			$initialDate = $initialDate[2] . "-" . $initialDate[1] . "-" . $initialDate[0];
			$finalDate = $finalDate[2] . "-" . $finalDate[1] . "-" . $finalDate[0];
			$datetime1 = new DateTime($initialDate);
			$datetime2 = new DateTime($finalDate);
			$interval = $datetime1->diff($datetime2);
			$interval = $interval->format('%a');
			
			
			$filterOperatorsGoTo = $this->data['export_calls']['operatorFilterGoesTo'];
			//checkboxes filterFor$k, where $k = filterOperatorsGoTo, start on 0 and go until we have checkboxes
			$arrOperators = array();
			for ($i=0; $i < $filterOperatorsGoTo; $i++){
				if (isset($this->data['filterFor' . $i])){
					$arrOperators[] = trim($this->data['filterFor' . $i]);
				}
			}
			
			//generating days array
			$k=0;
			for($i=$interval; $i>=0; $i--){
				$today = new DateTime(date("Y-m-d"));
				$today = $today->sub(new DateInterval("P" . $i . "D"));
				$today = $today->format("d-m-y");
				$arrDays[$k] = $today;
				$k++;
			}
			
			$finalArray = array();
			$i=0; //helpers
			$k=0; //helpers
			foreach($arrOperators as $operator){
				$finalArray[$i]['operator']['name'] = $operator;
				$finalArray[$i]['operator']['total_interval'] = 0;
				foreach($arrDays as $day){
					$this->Controle->create();
					$finalArray[$i]['operator']['days'][$k] = $this->Controle->find('all',array('conditions'=>array('operadora'=>$operator,'data'=>$day)));
					$finalArray[$i]['operator']['total_interval'] += count($this->Controle->find('all',array('conditions'=>array('operadora'=>$operator,'data'=>$day))));
					$k++;
				}
				$k=0;
				$i++;
			}
			
			
			$html = '<h3>Total de liga&ccedil;&otilde;es  e logs entre ' . $this->data['export_calls']['firstDate'] . ' e ' . $this->data['export_calls']['secondDate'] .'</h3>';
			$html .= "<br />";
			$html .= '<table cellspacing="0" cellpadding="5" border="1">';
			$html .= '<thead><tr><th><strong>Operadora</strong></th><th><strong>Total</strong></th></tr></thead>';
			foreach($finalArray as $operatorNow){
				$html .= 	"<tr>" . 
								"<td><strong>" . $operatorNow['operator']['name'] . "</strong></td>" . 
								"<td><font color='FF0000'>" . $operatorNow['operator']['total_interval']  . "</font></td>" . 
							"</tr>";
			}
			$html .= "</table>";
			$html .= "<br />";
			$html .= '<table cellspacing="0" cellpadding="5" border="1">';
			$html .= "<thead>
							<th width='80'><strong>ID</strong></th>
							<th width='150'><strong>Numero</strong></th>
							<th width='150'><strong>Operadora</strong></th>
							<th width='100'><strong>Data</strong></th>
							<th width='100'><strong>Hora</strong></th>
					</thead>";
			foreach($finalArray as $operatorNow){
				foreach($operatorNow['operator']['days'] as $row){
					foreach($row as $entry){
						$html .= 	"<tr>" . 
										"<td>" . $entry['Controle']['id'] . "</td>" . 
										"<td>" . $entry['Controle']['numero'] . "</td>" .
										"<td>" . $entry['Controle']['operadora'] . "</td>" .
										"<td>" . $entry['Controle']['data'] . "</td>" .
										"<td>" . $entry['Controle']['hora'] . "</td>" .
									"</tr>";
					}
				}
			}
			$html .="</table>";
			$file = "Export logs de " . $this->data['export_calls']['firstDate'] . ' a ' . $this->data['export_calls']['secondDate'] . ".xls";
			header ("Cache-Control: no-cache, must-revalidate");
			header ("Pragma: no-cache");
			header ("Content-type: application/x-msexcel");
			header ("Content-Disposition: attachment; filename=\"{$file}\"" );
			header ("Content-Description: PHP Generated Data" );
			
			echo $html;
			exit;

			
			
		}
	}
	
	function load_cdr(){
		$this->set('thisUser',$this->Session->read('User'));
		$this->set('page','cdr');
		
	}
	
}
