<?php

/**
 *  This file is part of SNEP.
 *  Para território Brasileiro leia LICENCA_BR.txt
 *  All other countries read the following disclaimer
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as
 *  published by the Free Software Foundation, either version 3 of
 *  the License, or (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/lgpl.txt>.
 */

/**
 * Classe para controle de Permissão
 *
 * @see Snep_Permission
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Iago Uilian Berndt <iagouilian@gmail.com>
 * 
 */
class Snep_PermissionPlugin extends Zend_Controller_Plugin_Abstract {

    public function __construct() {
        
    }

    /**
     * preDispatch - Verifica se o usuaria tem permissão para acesso a view,
     * Se não tiver permissão é redirecionado e força o zend a finaliziar imediatamente
     * @param Zend_Controller_Request_Abstract $request
     * @return type
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request) {

        //$request = Zend_Controller_Front::getInstance()->getRequest();
        $group = Snep_ExtensionsGroups_Manager::getGroupPeer($_SESSION['name_user']);

        if ($group == "admin")
            return;

        if (Snep_Permission_Manager::checkExistenceCurrentResource()) {

            $result = Snep_Permission_Manager::get($group, ($request->getModuleName() ? $request->getModuleName() : "default") . '_' . $request->getControllerName() . '_' . $request->getActionName());

            if (!$result) {
                $redirect = new Zend_Controller_Action_Helper_Redirector();
                $redirect->gotoSimpleAndExit("error-unset", "permission", "default");
            } elseif (!$result['allow']) {
                $redirect = new Zend_Controller_Action_Helper_Redirector();
                $redirect->gotoSimpleAndExit("error", "permission", "default");
            }
        }
    }

}