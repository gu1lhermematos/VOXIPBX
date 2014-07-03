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
 * Suporte Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2013 OpenS Tecnologia
 */
class SuporteController extends Zend_Controller_Action {
    
    /**
     * indexAction - List data of integrator
     */
    public function indexAction() {
       
        $id = $this->_request->getParam('id');
 
        // Flag para ver se usuario quer visualizar ou nao dados do integrador
        $configFile = APPLICATION_PATH . "/includes/setup.conf";
        $config = new Zend_Config_Ini($configFile, null, true);       
        $Flag_integrador = $config->system->integrator;
        
        if ($Flag_integrador == "0") {
               $integrator = Snep_Suporte_Manager::getDefault($id);    
         }else {        
               $integrator = Snep_Suporte_Manager::getIntegrador($id);
         }
        $form_xml = new Zend_Config_Xml( Zend_Registry::get("config")->system->path->base .
                                              "/modules/default/forms/suporte.xml" );
        $form = new Snep_Form( $form_xml);
        $form->getElement($this->view->translate('nome'))->setValue( $integrator["nome"] );
        $form->getElement('city')->setValue( $integrator["city"] );
        $form->getElement('state')->setValue( $integrator["state"] );
        $form->getElement('address')->setValue( $integrator["address"] );
        $form->getElement($this->view->translate('zip'))->setValue( $integrator["cep"] );
        $form->getElement($this->view->translate('phone_1'))->setValue( $integrator["phone_1"] );
        $form->getElement($this->view->translate('phone_2'))->setValue( $integrator["phone_2"] );
        $form->getElement($this->view->translate('cell_1'))->setValue( $integrator["cell_1"]  );
        $form->getElement($this->view->translate('email'))->setValue( $integrator["email"] );
       
        $this->view->form = $form;
    }
}
