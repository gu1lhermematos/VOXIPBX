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
class ExtensionsGroupsController extends Zend_Controller_Action {

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
     * indexAction - List all Extensions groups
     */
    public function indexAction() {


        $db = Zend_Registry::get('db');

        $this->view->tra = array("admin" => $this->view->translate("Administrators"),
            "users" => $this->view->translate("Users"),
            "NULL" => $this->view->translate("None"),
            "all" => $this->view->translate("All"));

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $this->view->user = $username;

        $select = $db->select()
                ->from("groups", array("name", "inherit"))
                ->where("name not in ('all','users','administrator','NULL') ");

        $this->view->filter = Snep_Filter::setSelect($select, array('name'), $this->_request);
        $this->view->order = Snep_Order::setSelect($select, array('name', 'inherit'), $this->_request);
        $this->view->limit = Snep_Limit::get($this->_request);

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );

        $this->view->filtro = $this->_request->getParam('filtro');

        $paginatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($paginatorAdapter);

        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage($this->view->limit);

        $this->view->extensionsgroups = $paginator;
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
        $this->view->title = "Extension Groups";
        $this->view->filter = array(array("url" => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/add",
                "display" => $this->view->translate("Add Extension Group"),
                "css" => "include"),
        );
    }

    /**
     * addAction - Adds a group and their extensions in the database
     */
    public function addAction() {

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form_xml = new Zend_Config_Xml("modules/default/forms/extensions_groups.xml");
        $form = new Snep_Form($form_xml);
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/add');

        $form->getElement('name')
                ->setLabel($this->view->translate('Name'));

        $form->getElement('type')
                ->setRequired(true)
                ->setLabel($this->view->translate('Type'))
                ->setMultiOptions(array('all' => $this->view->translate('Administrator'),
                    'users' => $this->view->translate('User'),
                    'NULL' => $this->view->translate('None')));


        try {
            $extensionsAllGroup = Snep_ExtensionsGroups_Manager::getExtensionsAll();
        } catch (Exception $e) {

            display_error($LANG['error'] . $e->getMessage(), true);
        }

        $extensions = array();

        foreach ($extensionsAllGroup as $key => $val) {

            $extensions[$val['name']] = $val['name'] . " ( " . $val['group'] . " )";
        }
        $this->view->objSelectBox = "extensions";

        $form->setSelectBox($this->view->objSelectBox, $this->view->translate('Extensions'), $extensions);

        if ($this->getRequest()->getPost()) {


            $form_isValid = $form->isValid($_POST);

            $dados = $this->_request->getParams();

            if ($form_isValid) {

                $group = array('name' => $dados['name'],
                    'inherit' => $dados['type']
                );

                $this->view->group = Snep_ExtensionsGroups_Manager::addGroup($group);

                if ($dados['box_add'] && $this->view->group) {

                    foreach ($dados['box_add'] as $id => $extensions) {

                        $extensionsGroup = array('group' => $dados['name'],
                            'extensions' => $extensions
                        );

                        $this->view->extensions = Snep_ExtensionsGroups_Manager::addExtensionsGroup($extensionsGroup);
                    }
                }

                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {

                    $acao = "Adicionou Grupo de ramal";
                    $nome = $dados['name'];
                    self::salvaLog($acao, $nome);
                    $action = "ADD";

                    $add = self::getGroup($nome);

                    self::insertLogGroup($action, $add);
                }

                $this->_redirect("/" . $this->getRequest()->getControllerName() . "/");
            }
        }

        $this->view->form = $form;
    }
    
    /**
     * editAction - Edit extensions groups
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Manage"),
                    $this->view->translate("Extension Groups"),
                    $this->view->translate("Edit Extension Groups"),
        ));

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $xml = new Zend_Config_Xml("modules/default/forms/extensions_groups.xml");
        $form = new Snep_Form($xml);
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/edit');

        $id = $this->_request->getParam('id');

        $tabela = self::verificaLog($tabela);
        if ($tabela == true) {

            $action = "OLD";
            $add = self::getGroup($id);
            self::insertLogGroup($action, $add);
        }

        $group = Snep_ExtensionsGroups_Manager::getGroup($id);

        $groupId = $form->getElement('id')->setValue($id);
        $groupName = $form->getElement('name')->setValue($group['name'])->setLabel($this->view->translate('Name'));
        ;

        $groupType = $form->getElement('type');
        $groupType->setRequired(true)
                ->setLabel($this->view->translate('Type'))
                ->setMultiOptions(array('all' => $this->view->translate('Administrator'),
                    'users' => $this->view->translate('User'),
                    'NULL' => $this->view->translate('None')))
                ->setValue($group['inherit']);

        $groupExtensions = array();
        foreach (Snep_ExtensionsGroups_Manager::getExtensionsOnlyGroup($id) as $data) {

            $groupExtensions[$data['name']] = "{$data['name']}";
        }

        $groupAllExtensions = array();
        foreach (Snep_ExtensionsGroups_Manager::getExtensionsAll() as $data) {

            if (!isset($groupExtensions[$data['name']])) {

                $groupAllExtensions[$data['name']] = "{$data['name']}" . " ( " . "{$data['group']}" . " )";
            }
        }

        $this->view->objSelectBox = "extensions";

        $form->setSelectBox($this->view->objSelectBox, $this->view->translate('Extensions'), $groupAllExtensions, $groupExtensions);

        if ($this->_request->getPost()) {

            $form_isValid = $form->isValid($_POST);

            $dados = $this->_request->getParams();
            $idGroup = $dados['id'];

            $this->view->group = Snep_ExtensionsGroups_Manager::editGroup(array('name' => $dados['name'], 'type' => $dados['type'], 'id' => $idGroup));

            foreach (Snep_ExtensionsGroups_Manager::getExtensionsOnlyGroup($id) as $extensionsGroup) {

                Snep_ExtensionsGroups_Manager::addExtensionsGroup(array('extensions' => $extensionsGroup['name'], 'group' => 'all'));
            }

            if ($dados['box_add']) {

                foreach ($dados['box_add'] as $id => $dados['name']) {

                    $this->view->extensions = Snep_ExtensionsGroups_Manager::addExtensionsGroup(array('extensions' => $dados['name'], 'group' => $idGroup));
                }
            }
            
            //log-user
            if ($tabela == true) {
                
                $acao = "Editou Grupo de ramal";
                self::salvaLog($acao, $idGroup);
                $action = "NEW";
                $add = self::getGroup($idGroup);

                self::insertLogGroup($action, $add);
            }

            $this->_redirect($this->getRequest()->getControllerName());
        }

        $this->view->form = $form;
    }

    /**
     * deleteAction - Remove a Extensions Group
     */
    public function deleteAction() {

        $db = Zend_Registry::get('db');
        $id = $this->_request->getParam('id');
        $confirm = $this->_request->getParam('confirm');

        //verifica se grupo é usado em alguma regra
        $rules_query = "SELECT id, `desc` FROM regras_negocio WHERE origem LIKE '%G:$id' OR destino LIKE '%G:$id'";
        $regras = $db->query($rules_query)->fetchAll();

        if (count($regras) > 0) {

            $this->view->error = $this->view->translate("Cannot remove. The following routes are using this extensions group: ") . "<br />";
            foreach ($regras as $regra) {
                $this->view->error .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
            }

            $this->_helper->viewRenderer('error');
        } else {

            if ($confirm == 1) {

                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {

                    $acao = "Excluiu Grupo de ramal";
                    self::salvaLog($acao, $id);
                    $action = "DEL";
                    $add = self::getGroup($id);

                    self::insertLogGroup($action, $add);
                }

                Snep_ExtensionsGroups_Manager::delete($id);
                $this->_redirect($this->getRequest()->getControllerName());
            }

            $extensions = Snep_ExtensionsGroups_Manager::getExtensionsGroup($id);

            if (count($extensions) > 0) {
                $this->_redirect($this->getRequest()->getControllerName() . '/migration/id/' . $id);
            } else {

                $this->view->message = $this->view->translate("The extension group will be deleted. Are you sure?.");
                $this->view->confirm = $this->getRequest()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/delete/id/' . $id . '/confirm/1';
            }
        }
    }

    /**
     * migrationAction - Migrate extensions to other Extensions Group
     */
    public function migrationAction() {


        $id = $this->_request->getParam('id');

        //log-user
        $add = self::getGroup($id);

        $_allGroups = Snep_ExtensionsGroups_Manager::getAllGroup();

        foreach ($_allGroups as $group) {

            if ($group['name'] != $id) {

                $allGroups[$group['name']] = $group['name'];
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
                $toGroup = Snep_ExtensionsGroups_Manager::addGroup($new_group);
            }

            $extensions = Snep_ExtensionsGroups_Manager::getExtensionsOnlyGroup($id);

            foreach ($extensions as $extension) {
                Snep_ExtensionsGroups_Manager::addExtensionsGroup(array('extensions' => $extension['name'], 'group' => $toGroup));
            }

            //log-user
            $tabela = self::verificaLog();
            if ($tabela == true) {

                $acao = "Excluiu Grupo de ramal";
                self::salvaLog($acao, $id);
                $action = "DEL";

                self::insertLogGroup($action, $add);
            }

            Snep_ExtensionsGroups_Manager::delete($id);

            $this->_redirect($this->getRequest()->getControllerName());
        }


        $this->view->form = $form;
    }
    
    /**
     * importAction - Import CSV
     */
    public function importAction() {
        $ie = new Snep_CsvIE('groups');
        $this->view->form = $ie->getForm();
        $this->view->title = "Import";
        $this->render('import_export');
    }
    
    /**
     * exportAction - Export CSV
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
     * verificaLog - Verifica se existe módulo Loguser.
     * @return <boolean> True ou false
     */
    function verificaLog() {
        if (class_exists("Loguser_Manager")) {
            $tabela = true;
        } else {
            $tabela = false;
        }
        return $tabela;
    }

    /**
     * salvalog - Insere dados da ação na tabela logs.
     * @global type $id_user
     * @param <string> $ação Ação feita pelo usuário
     * @param <string> $sounds id do arquivo de som
     * @return <boolean> True ou false
     */
    function salvaLog($acao, $sounds) {
        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');
        $tipo = 11;
        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $acao = mysql_escape_string($acao);

        $sql = "INSERT INTO `logs` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $sounds . "', '" . $tipo . "' , '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . NULL . "')";

        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getGroup - Monta array com todos dados do grupo de ramal
     * @param <int> $id - codigo da expressao
     * @return <array> $archive - Dados da expressao
     */
    function getGroup($id) {

        $archive = array();

        $db = Zend_Registry::get("db");
        $sql = "SELECT * from  groups where name='$id'";
        $stmt = $db->query($sql);
        $archive = $stmt->fetch();

        $sql = "SELECT name as member from  peers where peers.group='$id'";
        $stmt = $db->query($sql);
        $expressions = $stmt->fetchall();
        $archive["member"] = "";

        foreach ($expressions as $expr) {
            $archive["member"] .= $expr["member"] . " ";
        }

        return $archive;
    }

    /**
     * insertLogGroup - insere na tabela logs_users os dados do grupo de ramal
     * @global <int> $id_user
     * @param <array> $add
     */
    function insertLogGroup($acao, $add) {

        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        global $id_user;

        $select = "SELECT name from peers where id = '$id_user'";
        $stmt = $db->query($select);
        $id = $stmt->fetch();

        $sql = "INSERT INTO `logs_users` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $id["name"] . "', '" . $add["name"] . "', '" . $add["inherit"] . "', '" . $add["member"] . "', '" . NULL . "', '" . "GRP" . "', '" . $acao . "')";
        $db->query($sql);
    }

    
    /**
     * permissionAction - Add permission of extensions
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
