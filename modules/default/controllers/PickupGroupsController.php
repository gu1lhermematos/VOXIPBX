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
 * controller  extensions groups.
 */
class PickupGroupsController extends Zend_Controller_Action {

    /**
     *
     * @var Zend_Form
     */
    protected $form;

    /**
     *
     * @var array
     */
    protected $forms;

    /**
     * indexAction - List a pickup group
     */
    public function indexAction() {

        $db = Zend_Registry::get('db');

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $this->view->user = $username;

        $select = $db->select()->from("grupos");

        $this->view->filter = Snep_Filter::setSelect($select, array('nome'), $this->_request);
        $this->view->order = Snep_Order::setSelect($select, array('nome'), $this->_request);
        $this->view->limit = Snep_Limit::get($this->_request);

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );

        $this->view->filtro = $this->_request->getParam('filtro');

        $paginatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($paginatorAdapter);

        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage($this->view->limit);

        $this->view->pickupgroups = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/";
        $this->view->PAGE_URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/";

        // Formulário de filtro.
        $filter = new Snep_Form_Filter();
        $filter->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $filter->setValue($this->view->limit);
        $filter->setFieldValue($this->view->filter);
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/page/$page");

        $this->view->form_filter = $filter;
        $this->view->title = "Pickup Groups";
        $this->view->filter = array(array("url" => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/add",
                "display" => $this->view->translate("Add Pickup Group"),
                "css" => "include"),
        );
    }

    /**
     * addAction - Adds a group and their extensions in the database
     */
    public function addAction() {


        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form_xml = new Zend_Config_Xml("modules/default/forms/pickupGroup.xml");
        $form = new Snep_Form($form_xml);
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/add');

        $form->getElement('name')
                ->setLabel($this->view->translate('Name'));

        try {
            $extensionsAllGroup = Snep_PickupGroups_Manager::getExtensionsAll();
        } catch (Exception $e) {

            display_error($LANG['error'] . $e->getMessage(), true);
        }

        $extensions = array();

        foreach ($extensionsAllGroup as $key => $val) {

            $extensions[$val['name']] = $val['name'] . " ( " . $val['nome'] . " )";
        }
        $this->view->objSelectBox = "extensions";

        $form->setSelectBox($this->view->objSelectBox, $this->view->translate('Extensions'), $extensions);

        if ($this->getRequest()->getPost()) {

            $form_isValid = $form->isValid($_POST);

            $dados = $this->_request->getParams();

            if ($form_isValid) {

                $namegroup = array('nome' => $dados['name']);

                $groupId = Snep_PickupGroups_Manager::addGroup($namegroup);

                if ($dados['box_add'] && $groupId > 0) {

                    foreach ($dados['box_add'] as $id => $extensions) {

                        $extensionsGroup = array('pickupgroup' => $groupId,
                            'extensions' => $extensions
                        );

                        $this->view->extensions = Snep_PickupGroups_Manager::addExtensionsGroup($extensionsGroup);
                    }
                }

                $this->_redirect("/" . $this->getRequest()->getControllerName() . "/");
            }
        }

        $this->view->form = $form;
    }

    /**
     * editAction - Edit pickup group
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Manage"),
                    $this->view->translate("Pickup Groups"),
                    $this->view->translate("Edit Pickup Groups"),
        ));

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $xml = new Zend_Config_Xml("modules/default/forms/pickupGroup.xml");
        $form = new Snep_Form($xml);
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/edit');

        $id = $this->_request->getParam('id');

        $group = Snep_PickupGroups_Manager::getGroup($id);

        $groupId = $form->getElement('id')->setValue($id);
        $groupName = $form->getElement('name')->setValue($group['nome'])->setLabel($this->view->translate('Name'));


        $groupExtensions = array();
        foreach (Snep_PickupGroups_Manager::getExtensionsOnlyGroup($id) as $data) {

            $groupExtensions[$data['name']] = "{$data['name']}";
        }


        $groupAllExtensions = array();
        foreach (Snep_PickupGroups_Manager::getExtensionsAll() as $data) {

            if (!isset($groupExtensions[$data['name']])) {

                $groupAllExtensions[$data['name']] = $data['name'] . " ( " . $data['nome'] . " )";
            }
        }

        $this->view->objSelectBox = "extensions";

        $form->setSelectBox($this->view->objSelectBox, $this->view->translate('Extensions'), $groupAllExtensions, $groupExtensions);

        if ($this->_request->getPost()) {

            $form_isValid = $form->isValid($_POST);

            $dados = $this->_request->getParams();

            /* Remove todas as extensoes do grupo atual */
            $this->view->group = Snep_PickupGroups_Manager::editGroup(array('name' => $dados['name'], 'id' => $dados['id']));

            foreach (Snep_PickupGroups_Manager::getExtensionsOnlyGroup($dados['id']) as $extensionsGroup) {

                Snep_PickupGroups_Manager::addExtensionsGroup(array('extensions' => $extensionsGroup['name'], 'pickupgroup' => '1'));
            }

            if ($dados['box_add']) {

                foreach ($dados['box_add'] as $id => $dados['name']) {

                    $this->view->extensions = Snep_PickupGroups_Manager::addExtensionsGroup(array('extensions' => $dados['name'], 'pickupgroup' => $dados['id']));
                }
            }

            $this->_redirect($this->getRequest()->getControllerName());
        }

        $this->view->form = $form;
    }

    /**
     * deleteAction - Remove a Pickup Group
     */
    public function deleteAction() {

        $db = Zend_Registry::get('db');
        $id = $this->_request->getParam('id');
        $confirm = $this->_request->getParam('confirm');

        if ($confirm == 1) {
            Snep_PickupGroups_Manager::delete($id);
            $this->_redirect($this->getRequest()->getControllerName());
        }
        $extensions = Snep_PickupGroups_Manager::getExtensionsGroup($id);

        if (count($extensions) > 0) {
            $this->_redirect($this->getRequest()->getControllerName() . '/migration/id/' . $id);
        } else {
            $this->view->message = $this->view->translate("The pickup group will be deleted. Are you sure?.");
            $this->view->confirm = $this->getRequest()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/delete/id/' . $id . '/confirm/1';
        }
    }

    /**
     * migrationAction - Migrate extensions to other Pickup Group
     */
    public function migrationAction() {


        $id = $this->_request->getParam('id');

        $_allGroups = Snep_PickupGroups_Manager::getAllGroup();

        foreach ($_allGroups as $group => $nome) {

            if ($group != $id) {

                $allGroups[$group] = $nome;
            }
        }

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = new Snep_Form();
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/migration/');

        if (isset($allGroups)) {

            $groupSelect = new Zend_Form_Element_Select('select');
            $groupSelect->setMultiOptions($allGroups);
            $groupSelect->setLabel($this->view->translate($this->view->translate("New Group")));

            $groupSelect->setDecorators(array(
                'ViewHelper',
                'Description',
                'Errors',
                array(array('elementTd' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input')),
                array('Label', array('tag' => 'div', 'class' => 'label')),
                array(array('elementTr' => 'HtmlTag'), array('tag' => 'div', 'class' => 'line')),
            ));
            $form->addElement($groupSelect);
            $this->view->message = $this->view->translate("This groups has extensions associated. Select another group for these extensions. ");
        } else {

            $groupName = new Zend_Form_Element_Text('new_group');
            $groupName->setLabel($this->view->translate($this->view->translate("New Group")));

            $groupName->setDecorators(array(
                'ViewHelper',
                'Description',
                'Errors',
                array(array('elementTd' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input')),
                array('Label', array('tag' => 'div', 'class' => 'label')),
                array(array('elementTr' => 'HtmlTag'), array('tag' => 'div', 'class' => 'line')),
            ));
            $form->addElement($groupName);
            $this->view->message = $this->view->translate("This is the only group and it has extensions associated. You can migrate these extensions to a new group.");
        }

        $id_exclude = new Zend_Form_Element_Hidden("id");
        $id_exclude->setValue($id);

        $form->addElement($id_exclude);

        if ($this->_request->getPost()) {

            if (isset($_POST['select'])) {
                $toGroup = $_POST['select'];
            } else {
                $new_group = array('group' => $_POST['new_group']);
                $toGroup = Snep_PickupGroups_Manager::addGroup($new_group);
            }


            $extensions = Snep_PickupGroups_Manager::getExtensionsOnlyGroup($id);

            foreach ($extensions as $extension) {
                Snep_PickupGroups_Manager::addExtensionsGroup(array('extensions' => $extension['name'], 'pickupgroup' => $toGroup));
            }

            Snep_PickupGroups_Manager::delete($id);

            $this->_redirect($this->getRequest()->getControllerName());
        }


        $this->view->form = $form;
    }
    
    /**
     * importAction - Import archive CSV
     */
    public function importAction() {
        $ie = new Snep_CsvIE('groups');
        $this->view->form = $ie->getForm();
        $this->view->title = "Import";
        $this->render('import_export');
    }
    
    /**
     * exportAction - export archive CSV
     */
    public function exportAction() {
        $ie = new Snep_CsvIE('groups');
        if ($this->_request->getParam('download')) {
            $this->_helper->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            $ie->export();
        } else {
            $this->view->form = $ie->exportResult();
            $this->view->title = "Export";
            $this->render('import-export');
        }
    }

    /**
     * permissionAction - Permission Action
     */
    public function permissionAction() {

        $id = $this->_request->getParam('id');

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = new Snep_Form();
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/permission/id/' . $id);


        $currentResources = Snep_Permission_Manager::getAllGroup($id);
        $modules = Snep_Permission_Manager::getAll();
        $resources = array();
        $selected = array();

        /* foreach($modules as $moduleKey=>$module){
          foreach($module['controllers'] as $controllerKey=>$controller){
          foreach($controller['actions'] as $actionKey=>$action){
          $resource = $moduleKey.'_'.$controllerKey.'_'.$actionKey;
          $label = $module['label'].' - '.$controller['label'].' - '.$action;
          $resources[$resource] = $label;
          if(array_search($resource, $currentResources) !== FALSE) $selected[$resource] = $label;
          }
          }
          } */
        foreach ($modules as $moduleKey => $module) {
            foreach ($module as $controllerKey => $controller) {
                foreach ($controller as $actionKey => $action) {
                    $resource = $moduleKey . '_' . $controllerKey . '_' . $actionKey;
                    $label = Snep_Modules::$modules[$moduleKey]->getName() . " - " . $action;
                    $resources[$resource] = $label;
                    if (array_search($resource, $currentResources) !== FALSE)
                        $selected[$resource] = $label;
                }
            }
        }

        $form->setSelectBox("permissions", $this->view->translate('Permission'), $resources, $selected, 'bigMultiselect');

        if ($this->getRequest()->getPost()) {

            $form_isValid = $form->isValid($_POST);
            $dados = $this->_request->getParams();

            $update = array();

            foreach ($resources as $key => $value) {
                if (array_search($key, $dados['box_add']) !== FALSE) {
                    $update[$key] = true;
                } else {
                    $update[$key] = false;
                }
            }

            Snep_Permission_Manager::update($update, $id);

            if ($form_isValid) {

                $this->_redirect("/" . $this->getRequest()->getControllerName() . "/");
            }
        }

        $this->view->form = $form;
    }

}
