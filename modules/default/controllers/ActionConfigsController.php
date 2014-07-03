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
class ActionConfigsController extends Zend_Controller_Action {

    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
            $this->view->translate("Routing"),
            $this->view->translate("Default Configs")
        ));

        $actionsDisp = PBX_Rule_Actions::getInstance();
        $infoActions = array();

        foreach ($actionsDisp->getInstalledActions() as $actionTmp) {
            $action = new $actionTmp;
            if ($action->getDefaultConfigXML() != "") {
                $infoActions[] = array(
                    "id" => $actionTmp,
                    "name" => $action->getName(),
                    "description" => $action->getDesc()
                );
            }
        }
        $this->view->infoAcoes = $infoActions;
    }

    public function editAction() {

        $idAction = $this->getRequest()->getParam('id');

        if (!class_exists($idAction)) {
            throw new PBX_Exception_BadArg("Invalid Argument");
        } else {
            $action = new $idAction();
            $registry = PBX_Registry::getInstance($idAction);
            

            if ($action->getDefaultConfigXML() != "") {
                $actionConfig = new PBX_Rule_ActionConfig($action->getDefaultConfigXML());

                if ($this->getRequest()->isPost()) {

                    $newConfig = $actionConfig->parseConfig($_POST);

                    foreach ($newConfig as $key => $value) {
                        $registry->{$key} = $value;
                        $registry->setContext(get_class($action));
                    }

                    // Cleaning values no longer used
                    $previousValues = $registry->getAllValues();
                    foreach ($previousValues as $key => $value) {
                        if (!key_exists($key, $newConfig)) {
                            unset($registry->{$key});
                        }
                    }              
                    $this->view->success = true;

                }
                
                $action->setDefaultConfig( $registry->getAllValues() );
                $actionConfig = new PBX_Rule_ActionConfig($action->getDefaultConfigXML());
                $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
            $this->view->translate("Routing"),
            $this->view->translate("Default Configs"),
            $action->getName()
        ));
                
                $actionForm = $actionConfig->getForm();
                $actionForm->removeElement('cancel');
                $actionForm->removeElement('submit');
                $actionForm->setElementDecorators(array(
                		'ViewHelper',
                		'Description',
                		'Errors',
                		array(array('elementTd' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input')),
                		array('Label', array('tag' => 'div', 'class'=>'label')),
                		array(array('elementTr' => 'HtmlTag'), array('tag' => 'div', 'class' => 'line')),
                ));
                
                $actionForm->setDecorators(array(
                		'FormElements',
                		array('HtmlTag', array('tag' => 'div')),
                		array('Form', array('class' => 'snep_form'))
                ));
        		$actionForm->setAttrib('id', 'snep_form');
                $this->view->form = $actionForm;
                
            } else {
                throw new PBX_Exception_BadArg("No Configurable Action");
            }
        }
    }

}
