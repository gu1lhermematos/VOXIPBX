<?php

/**
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * controller index
 */
class IndexController extends Zend_Controller_Action {
    
    /**
     * indexAction - List dashboard
     */
    public function indexAction() {
    	
        $modelos = Snep_Dashboard_Manager::getModelos();
        if ($this->_request->getParam("add") !== NULL && $modelos[$this->_request->getParam("add")]) {
        	Snep_Dashboard_Manager::add($this->_request->getParam("add"));
        }
        
        $this->view->dashboard = Snep_Dashboard_Manager::getArray($modelos);
        if(!$this->view->dashboard)$this->_helper->redirector('add', 'index');
        
    }
    
    /**
     * editAction - Edit dashboard
     */
    public function editAction() {

        
        $this->view->dashboard = Snep_Dashboard_Manager::getArray();
        if(!$this->view->dashboard)$this->_helper->redirector('add', 'index');
        
        
        if ($this->_request->getPost()) {
            $dados = $this->_request->getParams();
            $dashboard = explode("|", substr($dados['dashboard'], 1));
            Snep_Dashboard_Manager::set($dashboard);
            $this->_redirect($this->getRequest()->getControllerName());
        }
    }
        
    /**
     * addAction - Add item in dashboard
     */
    public function addAction() {
        
    	$i18n = Zend_Registry::get("i18n");
        $usados = Snep_Dashboard_Manager::get();
        $modelos = Snep_Dashboard_Manager::getModelos();
        $i = -1;
        $id = 0;
        $titles = 0;
        $group = "";
        foreach($modelos AS $key=>$value)if(isset($value['nome']) &&  $value['nome'] != $group){ $titles++; $group = $value['nome'];}
        $group = "";
        $coluns = array("","","","");
        foreach($modelos AS $key=>$value){
            if($value['nome'] != $group){
            	$i++;
            	if(isset($value['nome'])){
            		$coluns[$i%4] .= "<div class='title'>$value[nome]</div>";
            		$group = $value['nome'];
            	}
            }
        	$coluns[$i%4] .= "<div class='item'><input type='checkbox' id='i$id' class='newcheck' value='$key' ".(in_array($key, $usados)?"checked = 'checked'":"")." name='dash[]'/> <label for='i$id'>$value[descricao]</label></div>";
        	$id++;
        }
        $filters = "";
        foreach($usados as $key=>$value){
        	if(is_array($value)){
        		$filters .= "<div class='item'><input type='checkbox' id='i$value[id]' value='$value[id]' class='newcheck' checked = 'checked' name='dash[]'/> <label for='i$value[id]'>$value[nome] - $value[descricao]</label></div>";
        	}
        }
        
        $form = "<form class='form' id='addDashboard' action='' method='post'>";
        	if($filters) $form .= "<div class='filter'><div class='title'>".$i18n->translate('Custom Dashboards')."</div>$filters</div>";
        	$form .= "<div class='coluna'>$coluns[0]</div>";
        	$form .= "<div class='coluna'>$coluns[1]</div>";
        	$form .= "<div class='coluna'>$coluns[2]</div>";
        	$form .= "<div class='coluna'>$coluns[3]</div>";
        $form .= "</form>";
        
        
        if ($this->_request->getPost()) {
            
            $dados = $this->_request->getParams();
            
            Snep_Dashboard_Manager::set($dados['dash']);
            $this->_redirect($this->getRequest()->getControllerName());
            
        }
        $this->view->form = $form; 
    }
   
}

?>
