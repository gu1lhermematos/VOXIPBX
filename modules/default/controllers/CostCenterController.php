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
 * Cost Center Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti
 */
class CostCenterController extends Zend_Controller_Action {

    /**
     * indexAction - List all Cost Center's
     */
    public function indexAction() {

        $this->view->url = $this->getFrontController()->getBaseUrl() . "/" . $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from("ccustos", array("codigo", "tipo", "nome", "descricao"));


        $this->view->filter_value = Snep_Filter::setSelect($select, array("codigo", "tipo", "nome", "descricao"), $this->_request);
        $this->view->order = Snep_Order::setSelect($select, array("codigo", "tipo", "nome", "descricao"), $this->_request);
        $this->view->limit = Snep_Limit::get($this->_request);

        $this->view->types = array('E' => $this->view->translate('Incoming'),
            'S' => $this->view->translate('Outgoing'),
            'O' => $this->view->translate('Others'));

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );
        $paginatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage($this->view->limit);

        $this->view->costcenter = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->PAGE_URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/";

        $filter = new Snep_Form_Filter();
        $filter->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $filter->setFieldValue($this->view->filter_value);
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/page/$page");
        $filter->setValue($this->view->limit);

        $this->view->form_filter = $filter;
        $this->view->title = $this->view->translate("Cost Center");
        $this->view->filter = array(array("url" => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/add",
                "display" => $this->view->translate("Add Cost Center"),
                "css" => "include"),
        );
    }

    /**
     * addAction - Add new Cost Center's
     */
    public function addAction() {

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = new Snep_Form(new Zend_Config_Xml("modules/default/forms//cost_center.xml"));

        if ($this->_request->getPost()) {

            $form_isValid = $form->isValid($_POST);
            $dados = $this->_request->getParams();

            $newId = Snep_CostCenter_Manager::get($dados['id']);

            if (count($newId) > 1) {
                $form_isValid = false;
                $form->getElement('id')->addError($this->view->translate('Code already exists.'));
            }

            if ($form_isValid) {
                $dados = $this->_request->getParams();
                Snep_CostCenter_Manager::add($dados);

                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {
                    $id = $dados["id"];
                    $acao = "Adicionou Centro de Custos";
                    self::salvaLog($acao, $id);
                    $action = "ADD";
                    $add = self::getCcustos($id);

                    self::insertLogCcustos($action, $add);
                }

                $this->_redirect($this->getRequest()->getControllerName());
            }
        }

        $this->view->form = $form;
    }

    /**
     * removeAction - Remove Cost Center's
     */
    public function removeAction() {

        $db = Zend_Registry::get('db');
        $id = $this->_request->getParam("id");
        $confirm = $this->_request->getParam('confirm');
        $name = $this->_request->getParam("name");

        // check if the cost center is used in the rule 
        $rules_query = "SELECT rule.id, rule.desc FROM regras_negocio as rule, regras_negocio_actions_config as rconf WHERE (rconf.regra_id = rule.id AND rconf.value = '$id' AND (rconf.key = 'ccustos'))";
        $regras = $db->query($rules_query)->fetchAll();

        if (count($regras) > 0) {

            $this->view->error = $this->view->translate("Cannot remove. The following routes are using this cost center: ") . "<br />";
            foreach ($regras as $regra) {

                $this->view->error .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
            }

            $this->_helper->viewRenderer('error');
        } else {
            if ($confirm == 1) {

                $id = $this->_request->getParam('id');

                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {
                    $acao = "Excluiu Centro de Custos";
                    self::salvaLog($acao, $id);
                    $action = "DEL";
                    $add = self::getCcustos($id);
                    self::insertLogCcustos($action, $add);
                }

                Snep_CostCenter_Manager::remove($id);

                $this->_redirect($this->getRequest()->getControllerName());
            }
            $this->view->message = $this->view->translate("The cost center will be deleted. Are you sure?");

            $this->view->confirm = $this->getRequest()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/remove/id/' . $id . '/name/' . $name . '/confirm/1';
        }
    }

    /**
     * editAction - Edit Cost Center's
     */
    public function editAction() {
        $id = $this->_request->getParam('id');
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Manage"),
                    $this->view->translate("Cost Center"),
                    $this->view->translate("Edit")
        ));

        $costCenter = Snep_CostCenter_Manager::get($id);

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = new Snep_Form(new Zend_Config_Xml("modules/default/forms/cost_center.xml"));
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/edit/id/' . $id);
        $form->getElement('id')->setValue($costCenter['codigo'])->setAttrib('readonly', true);
        $form->getElement('name')->setValue($costCenter['nome']);
        $form->getElement('description')->setValue($costCenter['descricao']);
        $form->getElement('type')->setValue($costCenter['tipo']);

        if ($this->_request->getPost()) {

            $form_isValid = $form->isValid($_POST);
            $dados = $this->_request->getParams();

            if ($form_isValid) {

                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {
                    $action = "OLD";
                    $add = self::getCcustos($id);
                    self::insertLogCcustos($action, $add);
                }

                Snep_CostCenter_Manager::edit($dados);

                if ($tabela == true) {
                    $acao = "Editou CCCentro de Custos";
                    self::salvaLog($acao, $id);
                    $action = "NEW";
                    $add = self::getCcustos($id);
                    self::insertLogCcustos($action, $add);
                }

                $this->_redirect($this->getRequest()->getControllerName());
            }
        }



        $this->view->form = $form;
    }

    /**
     * verificaLog - Verifica se existe módulo loguser
     * @return <boolean>
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
     * salvaLog - Insere dados no banco do loguser
     * @param <string> $acao
     * @param <string> $costcenter
     * @return <boolean>
     */
    function salvaLog($acao, $costcenter) {
        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');
        $tipo = 6;

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $acao = mysql_escape_string($acao);

        $sql = "INSERT INTO `logs` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $costcenter . "', '" . $tipo . "' , '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . NULL . "')";

        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * inuseAction - Verifica se Cost center já existe
     * @return type
     */
    public function inuseAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        if (isset($_POST['id']) && $_POST['id'] != "")
            $id = $_POST['id'];
        else
            return;
        $inuse = Snep_CostCenter_Manager::getInUse($id);
        $options = '';
        if (count($inuse) > 0) {
            foreach ($inuse as $cod)
                $options .= ", " . $cod['codigo'];
            echo "Codigos já em uso: " . substr($options, 2);
        }
    }

    /**
     * importAction - Importa CSV
     */
    public function importAction() {
        $ie = new Snep_CsvIE('ccustos');
        $this->view->form = $ie->getForm();
        $this->view->title = "Import";
        $this->render('import_export');
    }

    /**
     * exportAction - Exporta CSV
     */
    public function exportAction() {
        $ie = new Snep_CsvIE('ccustos');
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
     * getCcustos - Monta array com todos dados do centro de custo
     * @param <int> $id - Nome da fila
     * @return <array> $custo - Dados da fila
     */
    function getCcustos($id) {

        $custo = array();

        $db = Zend_Registry::get("db");
        $sql = "SELECT * from  ccustos where codigo='$id'";

        $stmt = $db->query($sql);
        $custo = $stmt->fetch();

        return $custo;
    }

    /**
     * insertLogCcustos - insere na tabela logs_users os dados do centro de custo
     * @global <int> $id_user
     * @param <array> $add
     */
    function insertLogCcustos($acao, $add) {

        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        global $id_user;

        $select = "SELECT name from peers where id = '$id_user'";
        $stmt = $db->query($select);
        $id = $stmt->fetch();

        $sql = "INSERT INTO `logs_users` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $id["name"] . "', '" . $add["codigo"] . "', '" . $add["tipo"] . "', '" . $add["nome"] . "', '" . $add["descricao"] . "', '" . "CCUSTOS" . "', '" . $acao . "')";
        $db->query($sql);
    }

}