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
 * Billing Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti
 */
class BillingController extends Zend_Controller_Action {

    /**
     * List all Billing
     */
    public function indexAction() {

        $this->view->url = $this->getFrontController()->getBaseUrl() . "/" . $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from("tarifas_valores", array('DATE_FORMAT(data,\'%d/%m/%Y %T\') as data', 'vcel', 'vfix'))
                ->from("tarifas")
                ->from("operadoras", array('nome'))
                ->where("operadoras.codigo = tarifas.operadora")
                ->where("tarifas_valores.codigo = tarifas.codigo");

        $this->view->filter_value = Snep_Filter::setSelect($select, array("nome", "pais", "estado", "cidade", "prefixo", "ddd"), $this->_request);
        $this->view->order = Snep_Order::setSelect($select, array("nome", "pais", "estado", "cidade", "prefixo", "ddd", "data", "vcel", "vfix"), $this->_request);
        $this->view->limit = Snep_Limit::get($this->_request);

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );
        $this->view->filtro = $this->_request->getParam('filtro');

        $paginatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage($this->view->limit);

        $this->view->billing = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->PAGE_URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/";

        $filter = new Snep_Form_Filter();
        $filter->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $filter->setValue($this->view->limit);
        $filter->setFieldValue($this->view->filter_value);
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/page/$page");

        $this->view->form_filter = $filter;
        $this->view->title = $this->view->translate("Billing");
        $this->view->filter = array(array("url" => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/add/",
                "display" => $this->view->translate("Add Billing"),
                "css" => "include"));
    }

    /**
     *  addAction - Add Queue
     */
    public function addAction() {

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = new Snep_Form(new Zend_Config_Xml("modules/default/forms/billing.xml"));

        foreach (Snep_Carrier_Manager::getAll() as $_carrier) {
            $carriers[$_carrier['codigo']] = $_carrier['nome'];
        }
        if (count($carriers)) {
            $form->getElement('operadora')->setMultiOptions($carriers);
        }

        $form->getElement('pais')->setMultiOptions(array("BRASIL" => "BRASIL"));
        $states[] = '----';
        foreach (Snep_Billing_Manager::getStates() as $state) {
            $states[$state['cod']] = $state['name'];
        }
        if (count($states)) {
            $form->getElement('estado')->setMultiOptions($states);
        }
        $cities[] = '----';
        foreach (Snep_Billing_Manager::getCity('AC') as $city) {
            $cities[$city['name']] = $city['name'];
        }
        if (count($cities)) {
            $form->getElement('cidade')->setMultiOptions($cities);
        }


        $dados = $this->_request->getParams();

        if ($this->_request->getPost()) {

            $form_isValid = true;

            $this->view->error = array();

            if (!preg_match('/[0-9]+$/', $dados['ddd']) || $dados['ddd'] == "") {
                $form->getElement('ddd')->addError($this->view->translate("City Code not numeric"));
                $form_isValid = false;
            }

            if (!preg_match('/[0-9]+$/', $dados['ddi']) || $dados['ddi'] == "") {
                $form->getElement('ddi')->addError($this->view->translate("Country Code not numeric"));
                $form_isValid = false;
            }

            if (!preg_match('/[0-9]+$/', $dados['prefixo']) || $dados['prefixo'] == "") {
                $form->getElement('prefixo')->addError($this->view->translate("Prefix not numeric"));
                $form_isValid = false;
            }
            if ($dados['operadora'] == "") {
                $form->getElement('operadora')->addError($this->view->translate("Carrier not selected "));
                $form_isValid = false;
            }

            if ($form_isValid) {

                if ($_POST['ddd'] == "") {
                    $_POST['ddd'] = 0;
                }
                if ($_POST['ddi'] == "") {
                    $_POST['ddi'] = 0;
                }

                $billing = Snep_Billing_Manager::getPrefix($_POST);

                if ($billing) {
                    $form_isValid = false;
                    $this->view->message = $this->view->translate("This bill is already set");
                }
            }

            if ($form_isValid) {

                $xdados = array('data' => $_POST['data'],
                    'carrier' => $_POST['operadora'],
                    'country_code' => $_POST['ddi'],
                    'country' => $_POST['pais'],
                    'city_code' => $_POST['ddd'],
                    'city' => $_POST['cidade'],
                    'state' => $_POST['estado'],
                    'prefix' => $_POST['prefixo'],
                    'tbf' => $_POST['vfix'],
                    'tbc' => $_POST['vcel']);

                Snep_Billing_Manager::add($xdados);

                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {

                    $cidade = $xdados["city"];
                    $db = Zend_Registry::get('db');
                    $sql = "SELECT codigo from tarifas where tarifas.cidade = '$cidade'";
                    $stmt = $db->query($sql);
                    $cidade = $stmt->fetchAll();
                    $id = $cidade[0]["codigo"];
                    $add = self::getTarifa($id);
                    $lastId = self::getLastId();
                    $add["codigo"] = $lastId;
                    $action = "ADD";
                    self::insertLogTarifa($action, $add);
                    $acao = "Adicionou tarifa";
                    self::salvalog($acao, $lastId);
                }

                $this->_redirect($this->getRequest()->getControllerName());
            }

            $this->view->dados = ( isset($dados) ? $dados : null);
        }

        $this->view->form = $form;
    }

    /**
     * editAction - Edit Billing
     */
    public function editAction() {

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');

        $id = $this->_request->getParam("id");

        $_carriers = Snep_Carrier_Manager::getAll();

        foreach ($_carriers as $_carrier) {
            $carriers[$_carrier['codigo']] = $_carrier['nome'];
        }
        $this->view->carriers = $carriers;

        $this->view->Carrier = Snep_Billing_Manager::get($id);

        $this->view->billingValues = Snep_Billing_Manager::getBillingValues($id);

        $_estado = Snep_Billing_Manager::getStates();
        foreach ($_estado as $estado) {
            if ($estado['cod'] == $this->view->Carrier['estado']) {
                $this->view->billingState = $estado;
            }
        }

        if ($this->_request->getPost()) {

            if (isset($_POST['action'])) {
                foreach ($_POST['action'] as $ida => $num) {
                    if ($num < count($this->view->billingValues)) {

                        $values = array('data' => $_POST['data'][$num],
                            'vcel' => $_POST['vcel'][$num],
                            'vfix' => $_POST['vfix'][$num]);

                        //log-user
                        $tabela = self::verificaLog();
                        if ($tabela == true) {

                            $old = self::getTarifa($id);
                            $action_ = "OLD";
                            $old['codigo'] = $id;
                            self::insertLogTarifa($action_, $old);
                        }

                        Snep_Billing_Manager::editBilling($id, $values);

                        //log-user
                        if ($tabela == true) {

                            $acao = "Editou tarifa";
                            $new = self::getTarifa($id);
                            $action = "NEW E";
                            $new['codigo'] = $id;
                            self::insertLogTarifa($action, $new);
                            self::salvaLog($acao, $id);
                        }
                        
                    } else {

                        $values = array('data' => $_POST['data'][$num],
                            'vcel' => $_POST['vcel'][$num],
                            'vfix' => $_POST['vfix'][$num]);

                        Snep_Billing_Manager::addBilling($id, $values);
                        
                        //log-user
                        $tabela = self::verificaLog();
                        if ($tabela == true) {
                            
                            $acao = "Adicionou nova tarifa";
                            $new = self::getTarifa($id);
                            $action = "NEW N";
                            $new['codigo'] = $id;
                            self::insertLogTarifa($action, $new);
                            self::salvaLog($acao, $id);
                        }
                        
                    }
                }
            }
            
            $this->_redirect($this->getRequest()->getControllerName() . '/edit/id/' . $id);

        }
    }

    /**
     * removeAction - Remove a Billing
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Billing"),
                    $this->view->translate("Delete")
        ));

        $id = $this->_request->getParam('id');

        //log-user
        $tabela = self::verificaLog();
        if ($tabela == true) {
            $acao = "Excluiu tarifa";
            self::salvaLog($acao, $id);
            $del = self::getTarifa($id);
            $action = "DEL";
            $del["codigo"] = $id;
            self::insertLogTarifa($action, $del);
        }

        Snep_Billing_Manager::remove($id);


        $this->_redirect($this->getRequest()->getControllerName());
    }

    /**
     * Get cities from state
     * POST Array state
     */
    public function dataAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $data = $_POST;

        if (isset($data['state'])) {
            $_states = Snep_Billing_Manager::getCity($data['state']);

            $states = array();
            foreach ($_states as $state) {
                $states[] = $state['name'];
            }
        }

        echo Zend_Json::encode($states);
    }
    
    /**
     * verificalog - Verifica se existe módulo loguser
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
     * salvaLog - Insere dados do loguser
     * @param <array> $acao
     * @param <int> $idregratronco
     * @return <boolean>
     */
    function salvaLog($acao, $idregratronco) {
        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');
        $tipo = 4;

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $acao = mysql_escape_string($acao);

        $sql = "INSERT INTO `logs` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $idregratronco . "', '" . $tipo . "', '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . NULL . "')";

        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getTarifa - Monta array com todos dados da tarifa
     * @param <int> $id - Código da tarifa
     * @return <array> $tarifa - Dados da tarifa
     */
    function getTarifa($id) {

        $tarifa = array();

        $db = Zend_Registry::get("db");
        $sql = "SELECT operadora, ddi, pais, ddd, cidade, estado, prefixo, data, vcel, vfix from  tarifas, tarifas_valores where tarifas.codigo = '$id' AND tarifas_valores.codigo ='$id' order by tarifas_valores.data desc";
        $stmt = $db->query($sql);
        $tarifa = $stmt->fetch();
        return $tarifa;
    }

    /**
     * insertLogTarifa - insere na tabela logs_tarifas as tarifas
     * @global <int> $id_user
     * @param <array> $add
     */
    function insertLogTarifa($acao, $add) {

        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $tipo = 4;
        $operadora = $add["operadora"];

        $select = "SELECT nome from operadoras where codigo = '$operadora'";
        $stmt = $db->query($select);
        $operadora = $stmt->fetch();

        $sql = "INSERT INTO `logs_tarifas` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $operadora['nome'] . "', '" . $add["ddi"] . "', '" . $add["pais"] . "', '" . $add["ddd"] . "', '" . $add["cidade"] . "', '" . $add["estado"] . "', '" . $add["prefixo"] . "', '" . $add["codigo"] . "', '" . $add["data"] . "', '" . $add["vcel"] . "', '" . $add["vfix"] . "', '" . $acao . "')";
        $db->query($sql);
    }

    /**
     * getLastId - Busca ID da ultima tarifa adicionada
     * @return <int> $result - Código da última tarifa
     */
    function getLastId() {

        $db = Zend_Registry::get("db");
        $sql = "SELECT codigo from  tarifas order by codigo desc limit 1";
        $stmt = $db->query($sql);
        $result = $stmt->fetch();

        return $result["codigo"];
    }

    /**
     * cidadeAction - METODOS PALEATIVOS para adaptação da interface.
     */
    public function cidadeAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $estado = isset($_POST['uf']) && $_POST['uf'] != "" ? $_POST['uf'] : display_error($LANG['msg_nostate'], true);
        $municipios = Snep_Cnl::get($estado);

        $options = '';
        if (count($municipios > 0)) {
            foreach ($municipios as $cidades) {
                $options .= "<option  value='{$cidades['municipio']}' > {$cidades['municipio']} </option> ";
            }
        } else {
            $options = "<option> {$LANG['select']} </option>";
        }

        echo $options;
    }
    
    /**
     * importAction - Import CSV
     */
    public function importAction() {
        $ie = new Snep_CsvIE(array('tarifas', 'tarifas_valores', 'tarifas_valores.codigo = tarifas.codigo'), 'cartesiano');
        $this->view->form = $ie->getForm();
        $this->view->title = "Import";
        $this->render('import_export');
    }
    
    /**
     * exportAction - Export CSV
     */
    public function exportAction() {
        $ie = new Snep_CsvIE(array('tarifas', 'tarifas_valores', 'tarifas_valores.codigo = tarifas.codigo'), 'cartesiano');
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

}