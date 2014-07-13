<?php

/*
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
 * Controller for extension management
 */
class ExtensionsController extends Zend_Controller_Action {

    protected $form;
    protected $boardData;
    
    /**
     * preDispatch
     */
    public function preDispatch() {
        $all_writable = true;
        $files = array(
            "snep-sip.conf" => false,
            "snep-sip-trunks.conf" => false,
            "snep-iax2.conf" => false,
            "snep-iax2-trunks.conf" => false
        );

        $config = Zend_Registry::get('config');
        $asteriskDirectory = $config->system->path->asterisk->conf;

        foreach ($files as $file => $status) {
            $files[$file] = is_writable($asteriskDirectory . "/snep/" . $file);
            if ($files[$file] === false && $all_writable === true) {
                $all_writable = false;
            }
        }

        $this->view->all_writable = $all_writable;
        if (!$all_writable) {
            $this->view->writable_files = $files;
        }
    }
    
    /**
     * indexAction - List extensions
     */
    public function indexAction() {
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $this->view->user = $username;

        $select = $db->select()->from("peers", array('id' => 'id',
            'exten' => 'name',
            'nome' => 'callerid',
            'channel' => 'canal',
            'group'));
        $select->where("peer_type='R'");

        $this->view->filter_value = Snep_Filter::setSelect($select, array("name", "callerid", "group"), $this->_request);
        $this->view->order = Snep_Order::setSelect($select, array("name", "callerid", "group"), $this->_request);
        $this->view->limit = Snep_Limit::get($this->_request);

        $page = $this->_request->getParam('page');

        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );
        $this->view->filtro = $this->_request->getParam('filtro');

        $paginatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($this->view->page);

        $this->view->limit = Snep_Limit::get($this->_request);
        $paginator->setItemCountPerPage($this->view->limit);

        $this->view->extensions = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->PAGE_URL = $this->getFrontController()->getBaseUrl() . "/extensions/index/";

        $baseUrl = $this->getFrontController()->getBaseUrl();

        // Formulário de filtro.
        $filter = new Snep_Form_Filter();
        $filter->setAction($baseUrl . '/extensions/index');
        $filter->setValue($this->view->limit);
        $filter->setFieldValue($this->view->filter_value);
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/page/$page");

        $this->view->title = $this->view->translate("Extension");
        $this->view->form_filter = $filter;
        $this->view->filter = array(array("url" => $baseUrl . "/extensions/add",
                "display" => $this->view->translate("Add Extension"),
                "css" => "include"));
    }
    
    /**
     * addAction - Add extensions
     */
    public function addAction() {

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        $this->view->subTitle = $this->view->translate("Add Extension");
        $form = $this->getForm();
        $this->view->boardData = $this->boardData;

        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            $postData = $this->_request->getParams();

            if ($this->view->form->isValid($_POST)) {

                $ret = $this->execAdd($postData);


                if (!is_string($ret)) {
                    
                    //log-user
                    $tabela = self::verificaLog();
                    if ($tabela == true) {
                        
                        $id = $_POST["extension"]["exten"];
                        $acao = "Adicionou Ramal";
                        self::salvaLog($acao, $id);
                        $action = "ADD";
                        $add = self::getPeer($id);
                        self::insertLogRamal($action, $add);
                    }
                    $this->_redirect('/extensions/');
                } else {
                    $this->view->error = $ret;
                    $this->view->form->valid(false);
                }
            }
        }

        $this->renderScript("extensions/add_edit.phtml");
    }
    
    /**
     * editAction - edit extensions
     * @return type
     */
    public function editAction() {
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();
        $id = $this->_request->getParam("id");
        $this->view->subTitle = $this->view->translate("Edit Extension");

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = $this->getForm();
        if (!$this->view->all_writable) {
            $form->getElement("submit")->setAttrib("disabled", "disabled");
        }


        $this->view->objSelectBox = "extensions";

        $this->view->form = $form;
        $this->view->boardData = $this->boardData;

        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        if ($this->getRequest()->isPost()) {
            $postData = $this->_request->getParams();

            
            if ($this->view->form->isValid($_POST)) {

                if (!is_string($ret)) {

                    $postData["extension"]["exten"] = $this->_request->getParam("id");
                    $ret = $this->execAdd($postData, true);

                    $this->_redirect('/extensions/');
                } else {
                    $this->view->error = $ret;
                    $this->view->form->valid(false);
                }
            }
        }

        $extenUtil = new Snep_Extensions();
        $exten = $extenUtil->ExtenDataAsArray($extenUtil->get($id));

        $name = $exten["name"];
        $nameField = $form->getSubForm('extension')->getElement('exten');
        $nameField->setValue($name);
        $nameField->setAttrib('readonly', true);
        //$nameField->setAttrib('disabled', true);

        if (!$exten["canal"] || $exten["canal"] == 'INVALID' || substr($exten["canal"], 0, strpos($exten["canal"], '/')) == '') {
            $techType = 'manual';
        } else {
            $techType = strtolower(substr($exten["canal"], 0, strpos($exten["canal"], '/')));
        }
        $form->getSubForm('technology')->getElement('type')->setValue($techType);

        $password = $exten["password"];
        $form->getSubForm('extension')->getElement('password')->setValue($password);
        $form->getSubForm('extension')->getElement('password')->renderPassword = true;

        $callerid = $exten["callerid"];
        $form->getSubForm('extension')->getElement('name')->setValue($callerid);

        $extenGroup = $exten["group"];
        $form->getSubForm('extension')->getElement('exten_group')->setValue($extenGroup);

        $pickupGroup = $exten["pickupgroup"];
        $form->getSubForm('extension')->getElement('pickup_group')->setValue($pickupGroup);

        $voiceMail = $exten["usa_vc"];
        if ($voiceMail) {
            $form->getSubForm('advanced')->getElement('voicemail')->setAttrib('checked', 'checked');
        }

        $email = $exten["email"];
        $form->getSubForm('advanced')->getElement('email')->setValue($email);

        $padlock = $exten["authenticate"];
        if ($padlock) {
            $form->getSubForm('advanced')->getElement('padlock')->setAttrib('checked', 'checked');
        }

        $timeTotal = $exten["time_total"];
        if (!empty($timeTotal)) {
            $form->getSubForm('advanced')->getElement('minute_control')->setAttrib('checked', 'checked');
            $timeTotal = $timeTotal / 60;
            $form->getSubForm('advanced')->getElement('timetotal')->setValue($timeTotal);
            $ctrlType = $exten["time_chargeby"];
            $form->getSubForm('advanced')->getElement('controltype')->setValue($ctrlType);
        }
        switch ($techType) {
            case "sip":
                $pass = $exten["secret"];
                $simCalls = $exten["call-limit"];
                $nat = $exten["nat"];
                $qualify = $exten["qualify"];
                $directmedia = $exten["directmedia"];
                
                $dtmfMode = $exten["dtmfmode"];
                $form->getSubForm('sip')->getElement('password')->setValue($pass);
                $form->getSubForm('sip')->getElement('password')->renderPassword = true;
                $form->getSubForm('sip')->getElement('calllimit')->setValue($simCalls);
                $form->getSubForm('sip')->getElement('directmedia')->setValue($directmedia);
                if ($nat == 'yes') {
                    $form->getSubForm('sip')->getElement('nat')->setAttrib('checked', 'checked');
                }
                if ($qualify == 'yes') {
                    $form->getSubForm('sip')->getElement('qualify')->setAttrib('checked', 'checked');
                }
                
                $form->getSubForm('sip')->getElement('dtmf')->setValue($dtmfMode);

                $codecs = explode(";", $exten['allow']);
                $form->getSubForm('sip')->getElement('codec')->setValue($codecs[0]);
                $form->getSubForm('sip')->getElement('codec1')->setValue($codecs[1]);
                $form->getSubForm('sip')->getElement('codec2')->setValue($codecs[2]);
                $form->getSubForm('sip')->getElement('codec3')->setValue($codecs[3]);
                $form->getSubForm('sip')->getElement('codec4')->setValue($codecs[4]);
                break;

            case "iax2":
                $pass = $exten["secret"];
                $simCalls = $exten["call-limit"];
                $nat = $exten["nat"];
                $qualify = $exten["qualify"];
                $typeIp = "friend" ;
                $dtmfMode = $exten["dtmfmode"];
                $directmedia = $exten["directmedia"];
                
                $form->getSubForm('iax2')->getElement('password')->setValue($pass);
                $form->getSubForm('iax2')->getElement('password')->renderPassword = true;
                $form->getSubForm('iax2')->getElement('calllimit')->setValue($simCalls);
                if ($nat == 'yes') {
                    $form->getSubForm('iax2')->getElement('nat')->setAttrib('checked', 'checked');
                }
                if ($qualify == 'yes') {
                    $form->getSubForm('iax2')->getElement('qualify')->setAttrib('checked', 'checked');
                }
                $form->getSubForm('iax2')->getElement('type')->setValue($typeIp);
                $form->getSubForm('iax2')->getElement('dtmf')->setValue($dtmfMode);
                $form->getSubForm('sip')->getElement('directmedia')->setValue($directmedia);

                $codecs = explode(";", $exten['allow']);
                $form->getSubForm('sip')->getElement('codec')->setValue($codecs[0]);
                $form->getSubForm('sip')->getElement('codec1')->setValue($codecs[1]);
                $form->getSubForm('sip')->getElement('codec2')->setValue($codecs[2]);
                $form->getSubForm('sip')->getElement('codec3')->setValue($codecs[3]);
                $form->getSubForm('sip')->getElement('codec4')->setValue($codecs[4]);
                break;

            case "khomp":
                $khompInfo = substr($exten["canal"], strpos($exten["canal"], '/') + 1);
                $khompBoard = substr($khompInfo, strpos($khompInfo, 'b') + 1, strpos($khompInfo, 'c') - 1);
                $khompChannel = substr($khompInfo, strpos($khompInfo, 'c') + 1);

                try {
                    $khompInfo = new PBX_Khomp_Info();
                } catch (Exception $e) {
                    $this->_redirect("/extensions/asterisk-error");
                    return;
                }

                if ($khompInfo->hasWorkingBoards()) {
                    foreach ($khompInfo->boardInfo() as $board) {
                        if (isset($board['model']))
                            if (preg_match("/FXS/", $board['model']) || preg_match("/Modular/", $board['model'])) {
                                $channels = range(0, $board['channels']);
                                $form->getSubForm('khomp')->getElement('board')->addMultiOption($board['id'], $board['id']);
                                $boardList[$board['id']] = $channels;

                                if ($board['id'] == $khompBoard) {
                                    foreach ($channels as $value) {
                                        $form->getSubForm('khomp')->getElement('channel')->addMultiOption($value, $value);
                                    }
                                }
                            }
                    }
                    $form->getSubForm('khomp')->getElement('board')->setValue($khompBoard);
                    $form->getSubForm('khomp')->getElement('channel')->setValue($khompChannel);
                }
                break;

            case "virtual":
                $virtualTrunk = substr($exten["canal"], strpos($exten["canal"], '/') + 1);
                $form->getSubForm('virtual')->getElement('virtual')->setValue($virtualTrunk);
                break;

            case "manual":
                $manualComp = substr($exten["canal"], strpos($exten["canal"], '/') + 1);
                $form->getSubForm('manual')->getElement('manual')->setValue($manualComp);
                break;
        }

        $this->renderScript("extensions/add_edit.phtml");
    }
    
    /**
     * execAdd
     * @param <array> $postData
     * @param <boolean> $update
     * @return type
     */
    protected function execAdd($postData, $update = false) {

        $formData = $postData;        
        $db = Zend_Registry::get('db');

        $exten = $formData["extension"]["exten"];
        $sqlValidName = "SELECT name,id from peers where name = '$exten'";
        $selectValidName = $db->query($sqlValidName);
        $resultGetId = $selectValidName->fetch();

        if ($resultGetId && !$update) {
            return $this->view->translate('Extension already taken. Please, choose another denomination.');
        } else if ($update) {
            $idExt = $resultGetId['id'];
        }

        $context = 'default';
        $extenPass = $formData["extension"]["password"];
        $extenName = $formData["extension"]["name"];
        $extenGroup = $formData["extension"]["exten_group"];
        $extenPickGrp = $formData["extension"]["pickup_group"] == '' ? "NULL" : $formData["extension"]["pickup_group"];
        $peerType = "R";

        $techType = $formData["technology"]["type"];
        $secret = $formData[$techType]["password"];
        $type = "friend";
        $dtmfmode = $formData[$techType]["dtmf"];
        $directmedia = $formData[$techType]["directmedia"];
        $callLimit = $formData[$techType]["calllimit"];

        $nat = 'no';
        if ($techType == 'sip' || $techType == 'iax2') {
            if ($formData[$techType]['nat']) {
                $nat = 'yes';
            }
        }

        $qualify = 'no';
        if ($techType == 'sip' || $techType == 'iax2') {
            if ($formData[$techType]['qualify']) {
                $qualify = 'yes';
            }
        }

        $channel = strtoupper($techType);
        if ($channel == "KHOMP") {
            $khompBoard = $formData[$techType]['board'];

            if ($khompBoard == null || $khompBoard == '') {
                return $this->view->translate('Select a Khomp board from the list');
            }

            if ($formData['action'] != 'multiadd') {
                $khompChannel = $formData[$techType]['channel'];
                if ($khompChannel == null || $khompChannel == '') {
                    return $this->view->translate('Select a Khomp channel from the list');
                }
            } else {
                $khompChannel = $formData['extension']['khomp_channel'];
            }


            $channel .= "/b" . $khompBoard . 'c' . $khompChannel;
        } else if ($channel == "VIRTUAL") {
            $virtualInfo = $formData[$techType]['virtual'];
            $channel .= "/" . $virtualInfo;
        } else if ($channel == "MANUAL") {
            $manualManual = $formData[$techType]['manual'];
            $channel = $manualManual;
        } else {
            $channel .= "/" . $exten;
        }

        $advVoiceMail = 'no';
        //if (key_exists("voicemail", $formData["advanced"])) {
        if ($formData["advanced"]["voicemail"]) {
            $advVoiceMail = 'yes';
        } else {
            $advVoiceMail = 'no';
        }

        $advPadLock = '0';
        //if (key_exists("padlock", $formData["advanced"])) {
        if ($formData["advanced"]["padlock"]) {
            $advPadLock = '1';
        } else {
            $advPadLock = '0';
        }

        //if (key_exists("minute_control", $formData["advanced"])) {
        if ($formData["advanced"]["minute_control"]) {
            $advMinCtrl = true;
            $advTimeTotal = $formData["advanced"]["timetotal"] * 60;
            $advTimeTotal = $advTimeTotal == 0 ? "NULL" : "'$advTimeTotal'";
            $advCtrlType = $formData['advanced']['controltype'];
        } else {
            $advMinCtrl = false;
            $advTimeTotal = 'NULL';
            $advCtrlType = 'N';
        }

        $defFielsExten = array("accountcode" => "''", 
            "amaflags" => "''", "defaultip" => "''", "host" => "'dynamic'", 
            "insecure" => "''", "language" => "'pt_BR'", "deny" => "''", 
            "permit" => "''", "mask" => "''", "port" => "''", 
            "restrictcid" => "''", "rtptimeout" => "''", 
            "rtpholdtimeout" => "''", "musiconhold" => "'cliente'", 
            "regseconds" => 0, "ipaddr" => "''", "regexten" => "''", 
            "cancallforward" => "'yes'", "setvar" => "''", 
            "disallow" => "'all'");

        $sqlFieldsExten = $sqlDefaultValues = "";
        foreach ($defFielsExten as $key => $value) {
            $sqlFieldsExten .= ",$key";
            $sqlDefaultValues .= ",$value";
        }

        $advEmail = $formData["advanced"]["email"];

        if ($techType == "sip" || $techType == "iax2") {
            $allow = sprintf("%s;%s;%s;%s;%s", $formData[$techType]['codec'], $formData[$techType]['codec1'], $formData[$techType]['codec2'], $formData[$techType]['codec3'], $formData[$techType]['codec4']);
        } else {
            $allow = "ulaw";
        }

        if ($update) {
            $sql = "UPDATE peers ";
            $sql.=" SET name='$exten',password='$extenPass' , callerid='$extenName', ";
            $sql.= "context='$context',mailbox='$exten',qualify='$qualify',";
            $sql.= "secret='$secret',type='$type', allow='$allow', fromuser='$exten',";
            $sql.= "username='$exten',fullcontact='',dtmfmode='$dtmfmode',";
            $sql.= "email='$advEmail', `call-limit`='$callLimit',";
            $sql.= "outgoinglimit='1', incominglimit='1',";
            $sql.= "usa_vc='$advVoiceMail',pickupgroup=$extenPickGrp,callgroup='$extenPickGrp',";
            $sql.= "nat='$nat',canal='$channel', authenticate=$advPadLock, ";
            $sql.= "`group`='$extenGroup', `directmedia`='$directmedia',";
            $sql.= "time_total=$advTimeTotal, time_chargeby='$advCtrlType'  WHERE id=$idExt";
        } else {
            $sql = "INSERT INTO peers (";
            $sql.= "name, password,callerid,context,mailbox,qualify,";
            $sql.= "secret,type,allow,fromuser,username,fullcontact,";
            $sql.= "dtmfmode,email,`call-limit`,incominglimit,";
            $sql.= "outgoinglimit, usa_vc, pickupgroup, canal,nat,peer_type, authenticate,";
            $sql.= "trunk, `group`, callgroup, time_total, directmedia, ";
            $sql.= "time_chargeby " . $sqlFieldsExten;
            $sql.= ") values (";
            $sql.= "'$exten','$extenPass','$extenName','$context','$exten','$qualify',";
            $sql.= "'$secret','$type','$allow','$exten','$exten','$fullcontact',";
            $sql.= "'$dtmfmode','$advEmail','$callLimit','1',";
            $sql.= "'1', '$advVoiceMail', $extenPickGrp ,'$channel','$nat', '$peerType',$advPadLock,";
            $sql.= "'no','$extenGroup','$extenPickGrp', $advTimeTotal, '$directmedia',";
            $sql.= " '$advCtrlType' " . $sqlDefaultValues;
            $sql.= ")";
        }

        $stmt = $db->query($sql);

        $idExten = $db->lastInsertId();


        if ($advVoiceMail == 'yes') {
            if ($update) {
                $db->delete("voicemail_users", " mailbox='$exten' ");
            }
            $sql = "INSERT INTO voicemail_users ";
            $sql.= " (fullname, email, mailbox, password, customer_id, `delete`) VALUES ";
            $sql.= " ('$extenName', '$advEmail','$exten','$extenPass','$exten', 'yes')";
            $stmt = $db->prepare($sql);
            $stmt->execute();
        }

        Snep_InterfaceConf::loadConfFromDb();
    }
    
    /**
     * deleteAction - Delete extensions
     * @return type
     */
    public function deleteAction() {

        try {
            $khompInfo = new PBX_Khomp_Info();
        } catch (Exception $e) {
            $this->_redirect("/extensions/asterisk-error");
            return;
        }

        $db = Zend_Registry::get('db');

        $id = $this->_request->getParam("id");

        // Fazendo procura por referencia a esse ramal em regras de negócio.
        $rulesQuery = "SELECT id, `desc` FROM regras_negocio WHERE origem LIKE '%R:$id%' OR destino LIKE '%R:$id%'";
        $rules = $db->query($rulesQuery)->fetchAll();

        $rulesQuery = "SELECT rule.id, rule.desc FROM regras_negocio as rule, regras_negocio_actions_config as rconf WHERE (rconf.regra_id = rule.id AND rconf.value = '$id')";
        $rules = array_merge($rules, $db->query($rulesQuery)->fetchAll());
        
        
        if (count($rules) > 0) {
            $errMsg = $this->view->translate('The following routes use this extension, modify them prior to remove this extension') . ":<br />\n";
            foreach ($rules as $regra) {
                $errMsg .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
            }
            $this->view->error = $errMsg;
            $this->view->back = $this->view->translate("Back");
            $this->_helper->viewRenderer('error');
        }else{
        
        //log-user
        $tabela = self::verificaLog();
        if ($tabela == true) {
            $acao = "Excluiu Ramal";
            self::salvaLog($acao, $id);
            $action = "DEL";
            $add = self::getPeer($id);
            self::insertLogRamal($action, $add);
        }

        $sql = "SELECT id FROM peers WHERE name=" . $id . "";
        $rules = $db->query($sql)->fetchAll();

        $sql = "DELETE FROM peers WHERE name='" . $id . "'";

        $db->beginTransaction();

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $sql = "delete from voicemail_users where customer_id='$id'";
        $stmt = $db->prepare($sql);
        $stmt->execute();

        $id_peer = $rules[0]['id'];

        $sql = "delete from queue_peers where ramal='$id_peer'";
        $stmt = $db->prepare($sql);
        $stmt->execute();

        try {
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            $this->view->error = $this->view->translate("DB Delete Error: ") . $e->getMessage();
            $this->view->back = $this->view->translate("Back");
            $this->_helper->viewRenderer('error');
        }

        $return = Snep_InterfaceConf::loadConfFromDb();

        If ($return != true) {
            $this->view->error = $return;
            $this->view->back = $this->view->translate("Back");
            $this->_helper->viewRenderer('error');
        }

        $this->_redirect("default/extensions");
    }
}
    
    /**
     * removeId - delete from peer
     * @param <string> $int
     * @return type
     */
    protected function removeId($int) {

        try {
            $khompInfo = new PBX_Khomp_Info();
        } catch (Exception $e) {
            $this->_redirect("/extensions/asterisk-error");
            return;
        }

        $db = Zend_Registry::get('db');

        $id = $db->query("SELECT name FROM peers WHERE CONVERT(name, signed) = '$int'");
        $id = $id->fetch();
        $id = $id['name'];

        if (!$id)
            return;

        // Fazendo procura por referencia a esse ramal em regras de negócio.
        $rulesQuery = "SELECT id, `desc` FROM regras_negocio WHERE origem LIKE '%R:$id%' OR destino LIKE '%R:$id%'";
        $rules = $db->query($rulesQuery)->fetchAll();

        $rulesQuery = "SELECT rule.id, rule.desc FROM regras_negocio as rule, regras_negocio_actions_config as rconf WHERE (rconf.regra_id = rule.id AND rconf.value = '$id')";
        $rules = array_merge($rules, $db->query($rulesQuery)->fetchAll());

        if (count($rules) > 0) {
            $msg = $id . " - " . $this->view->translate('The following routes use this extension, modify them prior to remove this extension') . ":";
            foreach ($rules as $regra) {
                $msg .= $regra['id'] . " - " . $regra['desc'] . ", ";
            }
            return substr($msg, 0, strlen($msg) - 2) . "<br />";
        }

        $sql = "DELETE FROM peers WHERE name='" . $id . "'";

        $db->beginTransaction();

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $sql = "delete from voicemail_users where customer_id='$id'";
        $stmt = $db->prepare($sql);
        $stmt->execute();

        try {
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            return $id . " - " . $this->view->translate("DB Delete Error: ") . $e->getMessage() . "<br />\n";
        }

        Snep_InterfaceConf::loadConfFromDb();

        return $id . " - " . $this->view->translate("Successfully Removed") . "<br />\n";
    }

    /**
     * getForm - get form extensions
     * @return <object> Snep_Form
     */
    protected function getForm() {

        if ($this->form === Null) {
            Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
            $form_xml = new Zend_Config_Xml(Zend_Registry::get("config")->system->path->base . "/modules/default/forms/extensions.xml");
            $form = new Snep_Form();
            $form->addSubForm(new Snep_Form_SubForm($this->view->translate("Extension"), $form_xml->extension), "extension");
            $form->addSubForm(new Snep_Form_SubForm($this->view->translate("Interface Technology"), $form_xml->technology), "technology");
            $form->addSubForm(new Snep_Form_SubForm(null, $form_xml->ip, "sip"), "sip");
            $form->addSubForm(new Snep_Form_SubForm(null, $form_xml->ip, "iax2"), "iax2");
            $form->addSubForm(new Snep_Form_SubForm(null, $form_xml->manual, "manual"), "manual");
            $subFormVirtual = new Snep_Form_SubForm(null, $form_xml->virtual, "virtual");
            if (PBX_Trunks::getAll() == null) {
                $subFormVirtual->removeElement('virtual');
                $subFormVirtual->addElement(new Snep_Form_Element_Html("extensions/trunk_error.phtml", "err", false, null, "virtual"));
            }
            $form->addSubForm($subFormVirtual, "virtual");
            $subFormKhomp = new Snep_Form_SubForm(null, $form_xml->khomp, "khomp");
            $selectFill = $subFormKhomp->getElement('board');
            $selectFill->addMultiOption(null, ' ');
            // Monta informações para placas khomp
            $boardList = array();
            try {
                $khompInfo = new PBX_Khomp_Info();
            } catch (Exception $e) {
                $this->_redirect("/extensions/asterisk-error");
                return;
            }
            if ($khompInfo->hasWorkingBoards()) {
                foreach ($khompInfo->boardInfo() as $board) {
                    if (isset($board['model']))
                        if (preg_match("/FXS/", $board['model']) || preg_match("/Modular/", $board['model'])) {
                            $channels = range(0, $board['channels']);
                            $selectFill->addMultiOption($board['id'], $board['id']);
                            $boardList[$board['id']] = $channels;
                        }
                }
                $subFormKhomp->getElement('channel')->setRegisterInArrayValidator(false);
                $boardTmp = Zend_Json_Encoder::encode($boardList);
                $this->boardData = $boardTmp;
            } else {
                $subFormKhomp->removeElement('board');
                $subFormKhomp->removeElement('channel');
                $subFormKhomp->addElement(new Snep_Form_Element_Html("extensions/khomp_error.phtml", "err", false, null, "khomp"));
            }
            $form->addSubForm($subFormKhomp, "khomp");
            $form->addSubForm(new Snep_Form_SubForm($this->view->translate("Advanced"), $form_xml->advanced), "advanced");
            $this->form = $form;
        }

        return $this->form;
    }
    
    /**
     * getmultiaddForm - get form multi add
     * @return <object> Snep_Form
     */
    protected function getmultiaddForm() {
        if ($this->form === Null) {
            $form_xml = new Zend_Config_Xml(Zend_Registry::get("config")->system->path->base . "/modules/default/forms/extensionsMulti.xml");
            $form = new Snep_Form();
            $form->addSubForm(new Snep_Form_SubForm($this->view->translate("Extension"), $form_xml->extension), "extension");
            $form->addSubForm(new Snep_Form_SubForm($this->view->translate("Interface Technology"), $form_xml->technology), "technology");
            $form->addSubForm(new Snep_Form_SubForm(null, $form_xml->ip, "sip"), "sip");
            $form->addSubForm(new Snep_Form_SubForm(null, $form_xml->ip, "iax2"), "iax2");
            $form->addSubForm(new Snep_Form_SubForm(null, $form_xml->virtual, "virtual"), "virtual");
            $subFormKhomp = new Snep_Form_SubForm(null, $form_xml->khomp, "khomp");
            $selectFill = $subFormKhomp->getElement('board');
            $selectFill->addMultiOption(null, ' ');
            // Monta informações para placas khomp

            $boardList = array();

            try {
                $khompInfo = new PBX_Khomp_Info();
            } catch (Exception $e) {
                $this->_redirect("/extensions/asterisk-error");
                return;
            }

            if ($khompInfo->hasWorkingBoards()) {
                foreach ($khompInfo->boardInfo() as $board) {
                    if (isset($board['model']))
                        if (preg_match("/FXS/", $board['model']) || preg_match("/Modular/", $board['model'])) {
                            $channels = range(0, $board['channels']);
                            $selectFill->addMultiOption($board['id'], $board['id']);
                            $boardList[$board['id']] = $channels;
                        }
                }
                // $subFormKhomp->getElement('channel')->setRegisterInArrayValidator(false);
                $boardTmp = Zend_Json_Encoder::encode($boardList);
                $this->boardData = $boardTmp;
            } else {
                $subFormKhomp->removeElement('board');
                $subFormKhomp->removeElement('channel');
                $subFormKhomp->addElement(new Snep_Form_Element_Html("extensions/khomp_error.phtml", "err", false, null, "khomp"));
            }
            $form->addSubForm($subFormKhomp, "khomp");
            //$form->addSubForm(new Snep_Form_SubForm($this->view->translate("Advanced"), $form_xml->advanced), "advanced");
            $this->form = $form;
        }

        return $this->form;
    }
    
    /**
     * multiaddAction - Add extensions
     */
    public function multiaddAction() {

        $this->view->form = $this->getmultiaddForm();
        if (!$this->view->all_writable) {
            $this->view->form->getElement("submit")->setAttrib("disabled", "disabled");
        }
        $this->view->boardData = $this->boardData;

        if ($this->getRequest()->isPost()) {
            $postData = $this->_request->getParams();

            if ($this->view->form->isValid($_POST)) {

                $range = explode(";", $postData["extension"]["exten"]);
                
                $this->view->error = "";

                $khomp_iface = false;
                if (strtoupper($postData["technology"]["type"]) == 'KHOMP') {
                    $khompInfo = new PBX_Khomp_Info();
                    $khompChannels = array();
                    $khomp_iface = true;
                    $boardInfo = $khompInfo->boardInfo($postData["khomp"]["board"]);
                    for ($i = 0; $i < $boardInfo['channels']; $i++) {
                        $khompChannels[$i] = $i; //"KHOMP/b{$boardInfo['id']}c$i";
                    }
                }

                
              
                
                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {

                    $acao = "Adicionou Ramais multiplos";
                    self::salvaLog($acao, $_POST["extension"]["exten"]);
                    $tech = $_POST["technology"]["type"];
                    $codecs = $_POST[$tech]["codec"] . ";" . $_POST[$tech]["codec1"] . ";" . $_POST[$tech]["codec2"] . ";" . $_POST[$tech]["codec3"];
                    $action = "ADD R";
                    $add = array();
                    $add["name"] = $_POST["extension"]["exten"];
                    $add["canal"] = $tech;
                    $add["allow"] = $codecs;
                    $add["dtmfmode"] = $_POST[$tech]["dtmf"];
                    $add["directmedia"] = $_POST[$tech]["directmedia"];
                    self::insertLogRamal($action, $add);
                }
                
                foreach ($range as $exten) {
                    
                    if ($this->view->error)
                        break;

                    if (is_numeric($exten)) {

                        $postData["extension"]["exten"] = $exten;
                        $postData["extension"]["password"] = $exten . $exten;
                        $postData["extension"]["name"] = 'Ramal' . $exten . '<' . $exten . '>';
                        $postData["sip"]["password"] = $exten;
                        $postData["iax"]["password"] = $exten;
                        

                        $ret = $this->execAdd($postData);

                        if (is_string($ret)) {
                            $this->view->error = $exten . " - " . $ret;
                            $this->view->form->valid(false);
                            break;
                        }
                    } else {

                        $exten = explode(";", $exten);

                        foreach ($exten as $range) {

                            $rangeToAdd = explode('-', $range);


                            if (is_numeric($rangeToAdd[0]) && is_numeric($rangeToAdd[1])) {
                                $i = $rangeToAdd[0];
                                while ($i <= $rangeToAdd[1]) {

                                    $postData["id"] = $i;
                                    $postData["extension"]["exten"] = $i;
                                    $postData["extension"]["password"] = $i . $i;
                                    $postData["extension"]["name"] = 'Ramal ' . $i . '<' . $i . '>';
                                    $postData["sip"]["password"] = $i . $i;
                                    $postData["iax2"]["password"] = $i . $i;

                                    if ($khomp_iface && count($khompChannels) > 0) {
                                        $channel = array_shift($khompChannels);
                                        $postData["extension"]["khomp_channel"] = $channel;
                                    }
                                    $ret = $this->execAdd($postData);
                                    $i++;

                                    if (is_string($ret)) {
                                        $this->view->error = $i . " - " . $ret;
                                        $this->view->form->valid(false);
                                        break;
                                    }
                                }
                            }
                            if ($this->view->error)
                                break;
                        }
                    }
                }
                if (!$this->view->error) {
                    $this->_redirect('/extensions/');
                }
            }
        }
    }
    
    /**
     * multiremoveAction - Delete Extensions
     */
    public function multiRemoveAction() {

        $form = new Snep_Form(new Zend_Config_Xml("modules/default/forms/extensionsMultiRemove.xml"));
        $this->view->form = $form;

        $date = $this->_request->getParam('exten');

        if ($date) {

            $range = explode(';', $date);
            $retornos = "";

            foreach ($range as $exten) {

                if (is_numeric($exten)) {
                    $retornos .= $this->removeId($exten);
                } else {
                    $exten = explode('-', $exten);
                    $exten[0] = (int) $exten[0];
                    $exten[1] = (int) $exten[1];

                    if ($exten[0] < 1)
                        $exten[0] = 1;

                    if ($exten[1] < 1)
                        $exten[1] = 1;

                    if ($exten[0] > $exten[1]) {
                        $i = $exten[0];
                        $exten[0] = $exten[1];
                        $exten[1] = $i;
                    }

                    $i = $exten[0];

                    while ($i <= $exten[1]) {
                        $retornos .= $this->removeId($i);
                        $i++;
                    }
                }
            }

            $this->view->retornos = $retornos;
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
     * salvaLog - log inserts in the database
     * @param <string> $acao
     * @param <int> $extension
     * @return <boolean>
     */
    function salvaLog($acao, $extension) {
        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');
        $tipo = 5;

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();


        $acao = mysql_escape_string($acao);

        $sql = "INSERT INTO `logs` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $extension . "', '" . $tipo . "' , '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . NULL . "')";

        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * importAction - Import CSV
     */
    public function importAction() {
        $ie = new Snep_CsvIE('peers');
        $this->view->form = $ie->getForm();
        $this->view->title = "Import";
        $this->render('import_export');
    }
    
    /**
     * exportAction - Export CSV
     */
    public function exportAction() {
        $ie = new Snep_CsvIE('peers');
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

    public function asteriskErrorAction() {
        
    }

    /**
     * getPeer - Monta array com todos dados do ramal
     * @param <int> $id - Código do ramal
     * @return <array> $ramal - Dados do ramal
     */
    function getPeer($id) {

        $ramal = array();

        $db = Zend_Registry::get("db");
        $sql = "SELECT id, name, canal, allow, dtmfmode from  peers where name='$id'";
        $stmt = $db->query($sql);
        $ramal = $stmt->fetch();

        return $ramal;
    }

    /**
     * insertLogRamal - insere na tabela logs_users os dados dos ramais
     * @global <int> $id_user
     * @param <array> $add
     */
    function insertLogRamal($acao, $add) {

        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        global $id_user;

        $select = "SELECT name from peers where id = '$id_user'";
        $stmt = $db->query($select);
        $id = $stmt->fetch();

        $sql = "INSERT INTO `logs_users` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $id["name"] . "', '" . $add["name"] . "', '" . $add["canal"] . "', '" . $add["allow"] . "', '" . $add["dtmfmode"] . "', '" . "Ramal" . "', '" . $acao . "')";
        $db->query($sql);
    }
    
    /**
     * getKhompAction
     */
    public function getKhompAction() {

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $board = $this->_request->getParam('board');
        $channels = array();

        $khompInfo = new PBX_Khomp_Info();
        $boardInfo = $khompInfo->boardInfo($board);
        if ($khompInfo->hasWorkingBoards()) {
            foreach (range(0, $boardInfo['channels'] - 1) as $channel) {
                $channels[$channel] = $channel;
            }
        }

        header('Content-type: application/json');
        echo Zend_Json::encode($channels);
    }
    
    /**
     * vinculosAction - verify bond of extensions
     */
    public function vinculosAction() {

        $name = $this->_request->getParam('id');

        $vinculados = Snep_Vinculos::getVinculados($name);
        $arrVinculados = array();
        if ($vinculados) {
            foreach ($vinculados as $vinculado) {
                $arrVinculados["r-" . $vinculado['id_vinculado']] = "Ramal: " . $vinculado['id_vinculado'];
            }
        }

        /* Agentes Vinculados ao ramal */
        $agentes_vinculados = Snep_Vinculos::getVinculadosAgente($name);
        if ($agentes_vinculados) {
            foreach ($agentes_vinculados as $id => $agentes_vinculado) {
                /* Inclui agente, a lista de ramais vinculados */
                $arrVinculados["a-" . $agentes_vinculado['id_vinculado']] = "Agente: {$agentes_vinculado['id_vinculado']} ";
            }
        }

        /* Desvinculados ao ramal */
        $desvinculados = Snep_Vinculos::getDesvinculados($name);
        $arrDesvinculados = $arrVinculados;
        if ($desvinculados) {
            foreach ($desvinculados as $desvinculado) {
                $arrDesvinculados["r-" . $desvinculado['name']] = "Ramal: " . $desvinculado['name'];
            }
        }

        /* Agentes Desvinculados ao ramal */
        $agentes_desvinculados = Snep_Vinculos::getDesvinculadosAgente($name);
        if ($agentes_desvinculados) {
            foreach ($agentes_desvinculados as $ida => $agentes_desvinculado) {
                //$arrAgentesDesvinculados[$id] = "Agente: ". $agentes_desvinculado ;
                $arrDesvinculados["a-" . $ida] = "Agente: $agentes_desvinculado";
            }
        }

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = new Snep_Form();
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/vinculos/id/' . $name);
        $form->setSelectBox("vinculos", '', $arrDesvinculados, $arrVinculados, 'bigMultiselect');

        $this->view->form = $form;

        if ($this->getRequest()->getPost()) {

            $form_isValid = $form->isValid($_POST);
            $dados = $this->_request->getParams();

            if ($form_isValid) {

                /* Remove qualquer referencia de vinculo a este ramal */
                Snep_Vinculos::resetVinculos($name);

                /* Cadastro de ramais vinculados */
                $vinculos = ( isset($_POST['box_add']) ? $_POST['box_add'] : null );
                if ($vinculos) {
                    foreach ($vinculos as $vinculo) {
                        $tipo = substr($vinculo, 0, 1);
                        $numero = substr($vinculo, strpos($vinculo, "-") + 1);

                        if ($tipo == "r") {
                            Snep_Vinculos::setVinculos($name, 'R', $numero);
                        } else {
                            Snep_Vinculos::setVinculos($name, 'A', $numero);
                        }
                    }
                }

                $this->_redirect("/" . $this->getRequest()->getControllerName() . "/");
            }
        }
    }

}

