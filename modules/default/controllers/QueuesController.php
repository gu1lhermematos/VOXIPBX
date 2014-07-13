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
 * Queues Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti
 */
class QueuesController extends Zend_Controller_Action {

    /**
     * indexAction - List all Queues
     */
    public function indexAction() {

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from("queues");

        $this->view->filter_value = Snep_Filter::setSelect($select, array('name', 'musiconhold', 'strategy', 'servicelevel', 'timeout'), $this->_request);
        $this->view->order = Snep_Order::setSelect($select, array('name', 'musiconhold', 'strategy', 'servicelevel', 'timeout'), $this->_request);
        $this->view->limit = Snep_Limit::get($this->_request);

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );

        $paginatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage($this->view->limit);

        $this->view->queues = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->PAGE_URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/";

        $filter = new Snep_Form_Filter();
        $filter->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $filter->setValue($this->view->limit);
        $filter->setFieldValue($this->view->filter_value);
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/page/$page");
        $this->view->title = "Queues";
        $this->view->form_filter = $filter;
        $this->view->filter = array(array("url" => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/add/",
                "display" => $this->view->translate("Add Queue"),
                "css" => "include"));
    }

    /**
     *  addAction - Add Queue
     */
    public function addAction() {

        $sections = new Zend_Config_Ini('/etc/asterisk/snep/snep-musiconhold.conf');
        $_section = array_keys($sections->toArray());
        $section = array();
        foreach ($_section as $value) {
            $section[$value] = $value;
        }
        $config = Zend_Registry::get('config');
        $language = $config->system->language;

        $files = '/var/lib/asterisk/sounds/' . $language;
        if (file_exists($files)) {

            $files = scandir($files);

            $sounds = array("" => "");

            foreach ($files as $i => $value) {
                if (substr($value, 0, 1) == '.') {
                    unset($files[$i]);
                    continue;
                }
                if (is_dir($files . '/' . $value)) {
                    unset($files[$i]);
                    continue;
                }
                $sounds[$value] = $value;
            }
        }

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = new Snep_Form();
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        $essentialData = new Zend_Config_Xml('./modules/default/forms/queues.xml', 'essential', true);
        $essential = new Snep_Form_SubForm($this->view->translate("General Configuration"), $essentialData);

        $essential->getElement('musiconhold')->setMultiOptions($section);
        $essential->getElement('timeout')->setValue(0);
        $essential->getElement('announce_frequency')->setValue(0);
        $essential->getElement('retry')->setValue(0);
        $essential->getElement('wrapuptime')->setValue(0);
        $essential->getElement('servicelevel')->setValue(0);
        $essential->getElement('strategy')->setMultiOptions(array('ringall' => $this->view->translate('For all agents available (ringall)'),
            'roundrobin' => $this->view->translate('Search for a available agent (roundrobin)'),
            'leastrecent' => $this->view->translate('For the agent idle for the most time (leastrecent)'),
            'random' => $this->view->translate('Randomly (random)'),
            'fewestcalls' => $this->view->translate('For the agent that answered less calls (fewestcalls)'),
            'rrmemory' => $this->view->translate('Equally (rrmemory)')));

        $form->addSubForm($essential, "essential");

        $advancedData = new Zend_Config_Xml('./modules/default/forms/queues.xml', 'advanced', true);
        $advanced = new Snep_Form_SubForm($this->view->translate("Advanced Configuration"), $advancedData);

        $boolOptions = array(1 => $this->view->translate('Yes'),
            0 => $this->view->translate('No'));

        $advanced->getElement('announce')->setMultiOptions($sounds);
        $advanced->getElement('queue_youarenext')->setMultiOptions($sounds);
        $advanced->getElement('queue_thereare')->setMultiOptions($sounds);
        $advanced->getElement('queue_callswaiting')->setMultiOptions($sounds);
        $advanced->getElement('queue_thankyou')->setMultiOptions($sounds);
        $advanced->getElement('leavewhenempty')->setMultiOptions($boolOptions)->setValue(0);
        $advanced->getElement('reportholdtime')->setMultiOptions($boolOptions)->setValue(0);
        $advanced->getElement('memberdelay')->setValue(0);
        $advanced->getElement('joinempty')
                ->setMultiOptions(array('yes' => $this->view->translate('Yes'),
                    'no' => $this->view->translate('No'),
                    'strict' => $this->view->translate('Restrict')))
                ->setValue('no');
        $form->addSubForm($advanced, "advanced");


        if ($this->_request->getPost()) {

            $dados = array('name' => $_POST['essential']['name'],
                'musiconhold' => $_POST['essential']['musiconhold'],
                'announce' => $this->dropFileExtension($_POST['advanced']['announce']),
                'context' => $_POST['advanced']['context'],
                'timeout' => $_POST['essential']['timeout'],
                'queue_youarenext' => $this->dropFileExtension($_POST['advanced']['queue_youarenext']),
                'queue_thereare' => $this->dropFileExtension($_POST['advanced']['queue_thereare']),
                'queue_callswaiting' => $this->dropFileExtension($_POST['advanced']['queue_callswaiting']),
                'queue_thankyou' => $this->dropFileExtension($_POST['advanced']['queue_thankyou']),
                'announce_frequency' => $_POST['essential']['announce_frequency'],
                'retry' => $_POST['essential']['retry'],
                'wrapuptime' => $_POST['essential']['wrapuptime'],
                'maxlen' => $_POST['essential']['maxlen'],
                'servicelevel' => $_POST['essential']['servicelevel'],
                'strategy' => $_POST['essential']['strategy'],
                'joinempty' => $_POST['advanced']['joinempty'],
                'leavewhenempty' => $_POST['advanced']['leavewhenempty'],
                'reportholdtime' => $_POST['advanced']['reportholdtime'],
                'memberdelay' => $_POST['advanced']['memberdelay'],
                'weight' => $_POST['advanced']['weight'],
            );

            $form_isValid = $form->isValid($_POST);

            if ($form_isValid) {

                Snep_Queues_Manager::add($dados);

                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {

                    $id = $dados["name"];
                    $acao = "Adicionou Fila";
                    self::salvaLog($acao, $id);
                    $action = "ADD";
                    $add = self::getQueue($id);
                    self::insertLogQueue($action, $add);
                }

                Snep_Queues_Manager::addQueuPeers($dados['name']); //Permite como Default o usuario admin visualizar fila

                $this->_redirect($this->getRequest()->getControllerName());
            }
        }
        $this->view->form = $form;
    }

    /**
     * editAction - Edit Queues
     */
    public function editAction() {

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');

        $id = $this->_request->getParam("id");

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Manage"),
                    $this->view->translate("Queues"),
                    $this->view->translate("Edit $id")
        ));

        $queue = Snep_Queues_Manager::get($id);

        $sections = new Zend_Config_Ini('/etc/asterisk/snep/snep-musiconhold.conf');
        $_section = array_keys($sections->toArray());
        $section = array();
        foreach ($_section as $value) {
            $section[$value] = $value;
        }

        $config = Zend_Registry::get('config');
        $language = $config->system->language;

        $files = '/var/lib/asterisk/sounds/' . $language;
        if (file_exists($files)) {

            $files = scandir($files);
            $sounds = array("" => "");

            foreach ($files as $i => $value) {
                if (substr($value, 0, 1) == '.') {
                    unset($files[$i]);
                    continue;
                }
                if (is_dir($files . '/' . $value)) {
                    unset($files[$i]);
                    continue;
                }
                $sounds[$value] = $value;
            }
        }

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = new Snep_Form();
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/edit/id/' . $id);

        $essentialData = new Zend_Config_Xml('./modules/default/forms/queues.xml', 'essential', true);
        $essential = new Snep_Form_SubForm($this->view->translate("General Configuration"), $essentialData);

        $essential->getElement('name')->setValue($queue['name'])->setAttrib('readonly', true);
        $essential->getElement('musiconhold')->setMultiOptions($section)->setValue($queue['musiconhold']);
        $essential->getElement('timeout')->setValue($queue['timeout']);
        $essential->getElement('announce_frequency')->setValue($queue['announce_frequency']);
        $essential->getElement('retry')->setValue($queue['retry']);
        $essential->getElement('wrapuptime')->setValue($queue['wrapuptime']);
        $essential->getElement('maxlen')->setValue($queue['maxlen']);
        $essential->getElement('servicelevel')->setValue($queue['servicelevel']);
        $essential->getElement('strategy')
                ->addMultiOptions(array('ringall' => $this->view->translate('For all agents available (ringall)'),
                    'roundrobin' => $this->view->translate('Search for a available agent (roundrobin)'),
                    'leastrecent' => $this->view->translate('For the agent idle for the most time (leastrecent)'),
                    'random' => $this->view->translate('Randomly (random)'),
                    'fewestcalls' => $this->view->translate('For the agent that answerd less calls (fewestcalls)'),
                    'rrmemory' => $this->view->translate('Equally (rrmemory)')))
                ->setValue($queue['strategy']);


        $form->addSubForm($essential, "essential");

        $advancedData = new Zend_Config_Xml('./modules/default/forms/queues.xml', 'advanced', true);
        $advanced = new Snep_Form_SubForm($this->view->translate("Advanced Configuration"), $advancedData);

        $boolOptions = array(1 => $this->view->translate('Yes'),
            0 => $this->view->translate('No'));

        $advanced->getElement('announce')->setMultiOptions($sounds)->setValue($this->addFileExtension($sounds,$queue['announce']));
        $advanced->getElement('context')->setValue($queue['context']);
        $advanced->getElement('queue_youarenext')->setMultiOptions($sounds)->setValue($this->addFileExtension($sounds,$queue['queue_youarenext']));
        $advanced->getElement('queue_thereare')->setMultiOptions($sounds)->setValue($this->addFileExtension($sounds,$queue['queue_thereare']));
        $advanced->getElement('queue_callswaiting')->setMultiOptions($sounds)->setValue($this->addFileExtension($sounds,$queue['queue_callswaiting']));
        $advanced->getElement('queue_thankyou')->setMultiOptions($sounds)->setValue($this->addFileExtension($sounds,$queue['queue_thankyou']));
        $advanced->getElement('joinempty')
                ->setMultiOptions(array('yes' => $this->view->translate('Yes'),
                    'no' => $this->view->translate('No'),
                    'strict' => $this->view->translate('Restrict')))
                ->setValue($queue['joinempty']);
        $advanced->getElement('leavewhenempty')->setMultiOptions($boolOptions)->setValue($queue['leavewhenempty']);
        $advanced->getElement('reportholdtime')->setMultiOptions($boolOptions)->setValue($queue['reportholdtime']);
        $advanced->getElement('memberdelay')->setValue($queue['memberdelay']);
        $advanced->getElement('weight')->setValue($queue['weight']);

        $form->addSubForm($advanced, "advanced");

        if ($this->_request->getPost()) {

            $dados = array('name' => $_POST['essential']['name'],
                'musiconhold' => $_POST['essential']['musiconhold'],
                'announce' => $this->dropFileExtension($_POST['advanced']['announce']),
                'context' => $_POST['advanced']['context'],
                'timeout' => $_POST['essential']['timeout'],
                'queue_youarenext' => $this->dropFileExtension($_POST['advanced']['queue_youarenext']),
                'queue_thereare' => $this->dropFileExtension($_POST['advanced']['queue_thereare']),
                'queue_callswaiting' => $this->dropFileExtension($_POST['advanced']['queue_callswaiting']),
                'queue_thankyou' => $this->dropFileExtension($_POST['advanced']['queue_thankyou']),
                'announce_frequency' => $_POST['essential']['announce_frequency'],
                'retry' => $_POST['essential']['retry'],
                'wrapuptime' => $_POST['essential']['wrapuptime'],
                'maxlen' => $_POST['essential']['maxlen'],
                'servicelevel' => $_POST['essential']['servicelevel'],
                'strategy' => $_POST['essential']['strategy'],
                'joinempty' => $_POST['advanced']['joinempty'],
                'leavewhenempty' => $_POST['advanced']['leavewhenempty'],
                'reportholdtime' => $_POST['advanced']['reportholdtime'],
                'memberdelay' => $_POST['advanced']['memberdelay'],
                'weight' => $_POST['advanced']['weight'],

            );


            $form_isValid = $form->isValid($_POST);

            if ($form_isValid) {

                Snep_Queues_Manager::edit($dados);

                $this->_redirect($this->getRequest()->getControllerName());
            }
        }
        $this->view->form = $form;
    }

    /**
     * removeAction - Remove a queue
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Manage"),
                    $this->view->translate("Queues"),
                    $this->view->translate("Delete")
        ));
        $db = Zend_Registry::get('db');
        $id = $this->_request->getParam('id');
        $confirm = $this->_request->getParam('confirm');

        // check if the cost center is used in the rule 
        $rules_query = "SELECT rule.id, rule.desc FROM regras_negocio as rule, regras_negocio_actions_config as rconf WHERE (rconf.regra_id = rule.id AND rconf.value = '$id' AND (rconf.key = 'queue'))";
        $regras = $db->query($rules_query)->fetchAll();

        //Tratamento de membros na fila
        $exten_member = "SELECT `membername` FROM `queue_members` WHERE `queue_name` = '$id'";
        $exten_members = $db->query($exten_member)->fetchAll();

        $agent_member = "SELECT `agent_id` FROM `queues_agent` WHERE `queue` = '$id'";
        $agent_members = $db->query($agent_member)->fetchAll();

        if (count($exten_members) > 0 || count($agent_members) > 0) {
            $msg = $this->view->translate("The following members make use of this queue, remove before deleting:") . "<br />\n";

            if (count($exten_members) > 0) {
                $valida = 1;

                foreach ($exten_members as $membros) {
                    $member = explode("/", $membros['membername']);
                    $member = $member[1];
                    $msg .= $this->view->translate("Extension:") . $member . "<br/>\n";
                }
            }

            if (count($agent_members) > 0) {
                $valida = 1;
                foreach ($agent_members as $member_agent) {
                    $msg .= $this->view->translate("Agent:") . $member_agent['agent_id'] . "<br/>\n";
                }
            }
            $this->view->error = $msg . "<br />";
            $this->_helper->viewRenderer('error');
        }

        if (count($regras) > 0) {

            $this->view->error = $this->view->translate("Cannot remove. The following routes are using this queues: ") . "<br />";
            foreach ($regras as $regra) {

                $this->view->error .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
            }

            $this->_helper->viewRenderer('error');
        } else {
            if ($confirm == 1) {

                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {
                    $add = self::getQueue($id);
                }

                Snep_Queues_Manager::removeQueuePeers($id);
                Snep_Queues_Manager::remove($id);
                Snep_Queues_Manager::removeQueues($id);


                if ($tabela == true) {
                    $acao = "Excluiu Fila";
                    self::salvaLog($acao, $id);
                    $action = "DEL";
                    self::insertLogQueue($action, $add);
                }


                $this->_redirect($this->getRequest()->getControllerName());
            }

            $this->view->message = $this->view->translate("Are you sure you want to delete this record?");

            $this->view->confirm = $this->getRequest()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/remove/id/' . $id . '/confirm/1';
        }
    }

    /**
     * verificaLog - Verify if exists module Loguser
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
     * salvaLog - Insert log in database
     * @param <string> $acao
     * @param <string> $queues
     * @return <boolean>
     */
    function salvaLog($acao, $queues) {
        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');
        $tipo = 7;

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $acao = mysql_escape_string($acao);

        $sql = "INSERT INTO `logs` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $queues . "', '" . $tipo . "' , '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . NULL . "')";

        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * membersAction - Set member queue
     * 
     */
    public function membersAction() {

        $queue = $this->_request->getParam("id");

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Manage"),
                    $this->view->translate("Queues"),
                    $this->view->translate("Members $queue")
        ));

        $members = Snep_Queues_Manager::getMembers($queue);
        $mem = array();
        foreach ($members as $m) {
            $mem[$m['interface']] = $m['interface'];
        }

        $_allMembers = Snep_Queues_Manager::getAllMembers();
        $notMem = array();
        foreach ($_allMembers as $row) {
            $cd = explode(";", $row['canal']);
            foreach ($cd as $canal) {
                if (preg_match("/MANUAL/i", $canal))
                    $canal = str_replace("MANUAL/", "", $canal);
                if (strlen($canal) > 0) {
                    if (!array_key_exists($canal, $mem)) {
                        $notMem[$canal] = $row['callerid'] . " ($canal)({$row['group']})";
                    }
                }
            }
        }

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = new Snep_Form();

        $this->view->objSelectBox = 'members';
        $form->setSelectBox($this->view->objSelectBox, $this->view->translate("Add Member"), $notMem, $mem);

        $queueId = new Zend_Form_Element_Hidden('id');
        $queueId->setvalue($queue);
        $form->addElement($queueId);

        $this->view->form = $form;

        if ($this->_request->getPost()) {
            Snep_Queues_Manager::removeAllMembers($queue);

            if (isset($_POST['box_add'])) {
                foreach ($_POST['box_add'] as $add) {

                    Snep_Queues_Manager::insertMember($queue, $add);
                }
            }

            $this->_redirect($this->getRequest()->getControllerName() . '/');
        }
    }

    /**
     * cidadeACTION - Depracated method
     * PALEATIVOS para adaptação da interface.     *
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
     * importAction - Import archive CSV
     */
    public function importAction() {
        $ie = new Snep_CsvIE('queues');
        $this->view->form = $ie->getForm();
        $this->view->title = "Import";
        $this->render('import_export');
    }

    /**
     * exportAction - Export archive CSV
     */
    public function exportAction() {
        $ie = new Snep_CsvIE('queues');
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
     * getQueue - Monta array com todos dados da fila
     * @param <int> $id - Nome da fila
     * @return <array> $fila - Dados da fila
     */
    function getQueue($id) {

        $fila = array();

        $db = Zend_Registry::get("db");
        $sql = "SELECT name, musiconhold, context from  queues where name='$id'";
        $stmt = $db->query($sql);
        $fila = $stmt->fetch();

        return $fila;
    }

    /**
     * insertLogFila - insere na tabela logs_users os dados das filas
     * @param <string> $acao
     * @param <array> $add
     */
    function insertLogQueue($acao, $add) {

        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $sql = "INSERT INTO `logs_users` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $add["name"] . "', '" . $add["musiconhold"] . "', '" . $add["context"] . "', '" . NULL . "', '" . "Fila" . "', '" . $acao . "')";
        $db->query($sql);
    }
    
     /**
     * dropFileExtension - Retira Extensao dos arquivos de audio
     * @param <string> $arquivo
     * @return <string> $arquivo - nome do arquivo sem a extensao
     */
    function dropFileExtension($arquivo) {
      // primeiro obtemos apenas o nome do arquivo
      $nome = pathinfo($arquivo, PATHINFO_BASENAME);
      $nome = explode('.', $arquivo); 
      // agora retiramos a extensão
      return $nome[0] ;
    }
    
    /**
     * addFileExtension - Coloca a Extensao dos arquivos de audio gravados no Banco
     * @param <array> $sounds - Lista de arquivos lidos do sistema
     * @param <string> $arquivo
     * @return <string> $arquivo - nome do arquivo com a extensao
     */
    function addFileExtension($sounds,$arquivo) {
      if (array_key_exists($arquivo.".gsm", $sounds)) { 
         $nome = $arquivo.".gsm";
      } elseif (array_key_exists($arquivo.".wav", $sounds)) {
         $nome = $arquivo.".wav";
      } else {
          $nome = "" ;
      }  
      return $nome ;
    }
}
