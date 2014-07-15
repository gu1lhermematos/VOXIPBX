<?php
App::uses('AppController', 'Controller');

class UsersController extends AppController {
	public $name = 'Users';
	public $helpers = array('Form','Session','Html');
	
	function login() {
		$this->set('page','login');
		
	}
	
	function do_auth(){
		
		if ($this->request->isPost()){
			$username = $this->data['user_auth']['username'];
			$password = md5($this->data['user_auth']['password']);
			$thisUser = $this->User->find('first',array('conditions'=>array('username'=>$username,'password'=>$password)));
			if (count($thisUser) > 0){
				$this->Session->write('User',$thisUser);
				$this->redirect(array('controller'=>'dashboard','action'=>'dashboard'));
			}
			else{
				$this->Session->setFlash($this->check_alert_msg('alert alert-danger','Usuário ou senha incorretos.'));
				$this->redirect(array('controller'=>'Users','action'=>'login'));
			}
		}
		
	}
	
	function logout(){
		$this->Session->delete("User");
		$this->Session->setFlash($this->check_alert_msg('alert alert-info','Voce foi desconectado.'));
		$this->Redirect(array('controller' => 'Users', 'action' => 'login'));
	}
	
	function remove_user($userID=null){
		$this->autoRender = false;
		$this->loadModel('User');
		//check how many users we have first
		$users = $this->User->find('all');
		if (count($users) <= 1){
			$this->Session->setFlash($this->check_alert_msg('alert alert-danger','Voce nao pode remover o unico usuario do sistema.'));
		}
		else if($this->User->delete($userID,false)){
			$this->Session->setFlash($this->check_alert_msg('alert alert-success','Usuario removido.'));
		}
		else{
			$this->Session->setFlash($this->check_alert_msg('alert alert-danger','Erro ao remover usuario .'));
		}
		$this->redirect(array('controller'=>'Dashboard','action'=>'list_users'));
	}
	
	
	function new_user(){
		$this->loadModel('User');
		$this->autoRender = false;
		if ($this->request->isPost()){
			$this->User->set('username',$this->data['new_user']['username']);
			$this->User->set('password',md5($this->data['new_user']['password']));
			if (!$this->User->validates()){
				$this->Session->setFlash($this->check_alert_msg('alert alert-warning','Usuario ja existe.'));
			}
			else if($this->User->save()){
				$this->Session->setFlash($this->check_alert_msg('alert alert-success','Usuario adicionado.'));
			}
			else{
				$this->Session->setFlash($this->check_alert_msg('alert alert-danger','Erro ao adicionar usuario.'));
			}
			$this->redirect(array('controller'=>'Dashboard','action'=>'list_users'));
		}
	}
	
	function update_user(){
		$this->loadModel('User');
		$this->autoRender = false;
		if ($this->request->isPost()){
			$this->User->id = $this->data['update_user']['userID'];
			$this->User->set('username',$this->data['update_user']['upd_username']);
			$this->User->set('password',md5($this->data['update_user']['upd_password']));
			if (!$this->User->validates()){
				$this->Session->setFlash($this->check_alert_msg('alert alert-warning','Usuario ja existe.'));
			}
			else if($this->User->save()){
				$this->Session->setFlash($this->check_alert_msg('alert alert-success','Usuario atualizado.'));
			}
			else{
				$this->Session->setFlash($this->check_alert_msg('alert alert-danger','Erro ao atualizar usuario.'));
			}
			$this->redirect(array('controller'=>'Dashboard','action'=>'list_users'));
		}
		
	}
	
	function afterFilter(){
		if( $this->action != 'login' ){
			$this->authenticate();
		}
	}
	
	function authenticate(){
		if(!$this->Session->check('User')){
			$this->Redirect(array('controller' => 'Users', 'action' => 'login'));
			exit();
		}	
	}	
}
