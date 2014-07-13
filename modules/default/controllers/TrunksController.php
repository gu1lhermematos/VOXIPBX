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
 * Trunk management
 */
class TrunksController extends Zend_Controller_Action {

    protected $form;
    protected $boardData;

    /**
     * indexAction - List trunks
     */
    public function indexAction() {
        $this->view->url = $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName();

        $db = Zend_Registry::get('db');

        $select = "SELECT t.id, t.callerid, t.name, t.type, t.trunktype, t.time_chargeby, t.time_total,
                            (
                                SELECT th.used
                                FROM time_history AS th
                                WHERE th.owner = t.id AND th.owner_type='T'
                                ORDER BY th.changed DESC limit 1
                            ) as used,
                            (
                                SELECT th.changed
                                FROM time_history AS th
                                WHERE th.owner = t.id AND th.owner_type='T'
                                ORDER BY th.changed DESC limit 1
                            ) as changed
                     FROM trunks as t ";

        $request = $this->_request;
        if ($request->getPost('filtro'))
            $filtro = $request->getPost('filtro');
        elseif ($request->getParam('filter'))
            $filtro = $request->getParam('filter');
        if (isset($filtro)) {
            $query = mysql_escape_string($filtro);
            $opcoes = array("name", "callerid");
            foreach ($opcoes as $key => $value)
                $opcoes[$key] = " `$value` like '%$query%' ";
            $select.="WHERE" . (implode($opcoes, " OR "));
            $this->view->filter_value = $filtro;
        }

        $this->view->limit = Snep_Limit::get($this->_request);

        $opcoes = array("name", "callerid", "type", "trunktype");
        if ($request->getParam('order'))
            $order = $request->getParam('order');
        else
            $order = $opcoes[0];
        if (!in_array($order, $opcoes))
            $order = $opcoes[0];
        if ($request->getParam('desc'))
            $desc = "DESC";
        else
            $desc = "";
        $select.="ORDER BY " . $order . ($desc ? " " . $desc : "");
        $this->view->order = array($order, $desc);

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );
        $this->view->filtro = $this->_request->getParam('filtro');

        $datasql = $db->query($select);
        $trunks = $datasql->fetchAll();

        foreach ($trunks as $id => $val) {

            $trunks[$id]['saldo'] = null;

            if (!is_null($val['time_total'])) {
                $ligacao = $val['changed'];
                $anoLigacao = substr($ligacao, 6, 4);
                $mesLigacao = substr($ligacao, 3, 2);
                $diaLigacao = substr($ligacao, 0, 2);
                $saldo = "";
                switch ($val['time_chargeby']) {
                    case 'Y':
                        if ($anoLigacao == date('Y')) {
                            $saldo = $val['time_total'] - $val['used'];
                            if ($val['used'] >= $val['time_total']) {
                                $saldo = 0;
                            }
                        } else {
                            $saldo = $val['time_total'];
                        }
                        break;
                    case 'M':
                        if ($anoLigacao == date('Y') && $mesLigacao == date('m')) {
                            $saldo = $val['time_total'] - $val['used'];
                            if ($val['used'] >= $val['time_total']) {
                                $saldo = 0;
                            }
                        } else {
                            $saldo = $val['time_total'];
                        }
                        break;
                    case 'D':
                        if ($anoLigacao == date('Y') && $mesLigacao == date('m') && $diaLigacao == date('d')) {
                            $saldo = $val['time_total'] - $val['used'];
                        } else {
                            $saldo = $val['time_total'];
                        }
                        break;
                }
                $trunks[$id]['saldo'] = $saldo;
            }
        }

        $paginatorAdapter = new Zend_Paginator_Adapter_Array($trunks);
        $paginator = new Zend_Paginator($paginatorAdapter);

        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage($this->view->limit);

        $this->view->trunks = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->PAGE_URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/";

        // Formulário de filtro.
        $filter = new Snep_Form_Filter();
        $filter->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $filter->setValue($this->view->limit);
        $filter->setFieldValue($this->view->filter_value);
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/page/$page");

        $this->view->form_filter = $filter;
        $this->view->title = $this->view->translate("Trunks");
        $this->view->filter = array(array("url" => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/add/",
                "display" => $this->view->translate("Add Trunk"),
                "css" => "include"));
    }

    /*
     * asteriskErrorAction
     */

    public function asteriskErrorAction() {
        
    }

    /**
     * getForm - Get form of trunks
     * @return type
     */
    protected function getForm() {
        $this->form = null;

        if ($this->form === Null) {

            $form_xml = new Zend_Config_Xml(Zend_Registry::get("config")->system->path->base . "/modules/default/forms/trunks.xml");

            $form = new Snep_Form();

            $strunks = new Snep_Form_SubForm($this->view->translate("Trunk"), $form_xml->trunks);

            $stech = new Snep_Form_SubForm($this->view->translate("Interface Technology"), $form_xml->technology);
            $ssip = new Snep_Form_SubForm(null, $form_xml->ip, "sip");

            $form->addSubForm($strunks, "trunks");
            $form->addSubForm($stech, "technology");
            $form->addSubForm($ssip, "sip");

            $ip = new Snep_Form_SubForm(null, $form_xml->ip, "iax2");

            $iax = new Snep_Form_SubForm(null, $form_xml->iax2, "iax2");

            foreach ($iax as $_iax) {
                $ip->addElement($_iax);
            }
            $form->addSubForm($ip, "iax2");

            $ssnepsip = new Snep_Form_SubForm(null, $form_xml->snepsip, "snepsip");
            $form->addSubForm($ssnepsip, "snepsip");

            $snepsip = new Snep_Form_SubForm(null, $form_xml->snepsip, 'snepiax2');
            $snep_iax = new Snep_Form_SubForm(null, $form_xml->snepiax2, "snepiax2");


            foreach ($snepsip as $_snepsip) {
                $snep_iax->addElement($_snepsip);
            }
            $form->addSubForm($snep_iax, "snepiax2");

            $svirtual = new Snep_Form_SubForm(null, $form_xml->virtual, "virtual");
            $form->addSubForm($svirtual, "virtual");

            $subFormKhomp = new Snep_Form_SubForm(null, $form_xml->khomp, "khomp");
            // Informações de placas khomp


            try {
                $khomp_info = new PBX_Khomp_Info();
            } catch (Exception $e) {
                $this->_redirect("/trunks/asterisk-error");
                return;
            }


            $khomp_boards = array();
            if ($khomp_info->hasWorkingBoards()) {
                foreach ($khomp_info->boardInfo() as $board) {
                    if (!preg_match("/FXS/", $board['model'])) {
                        $khomp_boards["b" . $board['id']] = "{$board['id']} - " . $this->view->translate("Board") . " {$board['model']}";
                        $id = "b" . $board['id'];
                        if (preg_match("/E1/", $board['model'])) {
                            for ($i = 0; $i < $board['links']; $i++)
                                $khomp_boards["b" . $board['id'] . "l$i"] = $board['model'] . " - " . $this->view->translate("Link") . " $i";
                        } else {
                            for ($i = 0; $i < $board['channels']; $i++)
                                $khomp_boards["b" . $board['id'] . "c$i"] = $board['model'] . " - " . $this->view->translate("Channel") . " $i";
                        }
                    }
                }
                $subFormKhomp->getElement('board')->setMultiOptions($khomp_boards);
            }

            if (count($khomp_boards) == 0) {
                $subFormKhomp->removeElement('board');
                $subFormKhomp->addElement(new Snep_Form_Element_Html("extensions/khomp_error.phtml", "err", false, null, "khomp"));
            }

            $form->addSubForm($subFormKhomp, "khomp");

            $sadvaced = new Snep_Form_SubForm($this->view->translate("Advanced"), $form_xml->advanced);
            $form->addSubForm($sadvaced, "advanced");

            $this->form = $form;
        }

        return $this->form;
    }

    /**
     * preparePost
     * @param <string> $post
     * @return type
     */
    protected function preparePost($post = null) {
        $post = $post === null ? $_POST : $post;
        $tech = $post['technology']['type'];

        if ($tech === "sip" || $tech === "iax2") {

            // Ajuste porque Zend não reconhece nome "call-limit" (com hifem)
            $post[$tech]['call-limit'] = $post[$tech]['calllimit'];


            // verifica tipo de Qualify, (yes|no|specify)
            if ($post[$tech]['qualify'] === 'specify') {
                $post[$tech]['qualify'] = $post[$tech]['qualify_value'];
            }
        }

        $trunktype = $post['technology']['type'] = strtoupper($tech);
        $static_sections = array("trunks", "technology", "advanced", $tech);
        $ip_trunks = array("sip", "iax2", "snepsip", "snepiax2");
        $trunk_fields = array(// Only allowed fields for trunks table
            "callerid",
            "type",
            "username",
            "secret",
            "host",
            "dtmfmode",
            "reverse_auth",
            "domain",
            "insecure",
            "map_extensions",
            "dtmf_dial",
            "dtmf_dial_number",
            "time_total",
            "time_chargeby",
            "dialmethod",
            "trunktype",
            "context",
            "name",
            "allow",
            "id_regex",
            "channel"
            
        );

        $ip_fields = array(// Only allowed fields for peers table
            "name",
            "callerid",
            "context",
            "secret",
            "type",
            "allow",
            "username",
            "dtmfmode",
            "fromdomain",
            "fromuser",
            "canal",
            "host",
            "peer_type",
            "istrunk",
            "qualify",
            "nat",
            "call-limit",
            "port"
        );

        $sql = "SELECT name FROM trunks ORDER BY CAST(name as DECIMAL) DESC LIMIT 1";
        $row = Snep_Db::getInstance()->query($sql)->fetch();

        $trunk_data = array(
            "name" => trim($row['name'] + 1),
            "context" => "default",
            "trunktype" => (in_array($tech, $ip_trunks) ? "I" : "T"),
        );

        foreach ($post as $section_name => $section) {
            if (in_array($section_name, $static_sections)) {
                $trunk_data = array_merge($trunk_data, $section);
            }
        }

        if ($trunktype == "SIP" || $trunktype == "IAX2") {

            $trunk_data['dialmethod'] = strtoupper($trunk_data['dialmethod']);

            if ($trunk_data['dialmethod'] == 'NOAUTH') {
                $trunk_data['channel'] = $trunktype . "/@" . $trunk_data['host'];
            } else {
                $trunk_data['channel'] = $trunktype . "/" . $trunk_data['username'];
            }

            $trunk_data['id_regex'] = $trunktype . "/" . $trunk_data['username'];
            $trunk_data['allow'] = trim(sprintf("%s;%s;%s;%s;%s", $trunk_data['codec'], $trunk_data['codec1'], $trunk_data['codec2'], $trunk_data['codec3'], $trunk_data['codec4']), ";");
        } else if ($trunktype == "SNEPSIP" || $trunktype == "SNEPIAX2") {

            $trunk_data['peer_type'] = $trunktype == "SNEPSIP" ? "peer" : "friend";
            $trunk_data['username'] = $trunktype == "SNEPSIP" ? $trunk_data['host'] : $trunk_data['username'];
            $trunk_data['channel'] = $trunk_data['id_regex'] = substr($trunktype, 4) . "/" . $trunk_data['username'];
        } else if ($trunktype == "KHOMP") {

            $khomp_board = $trunk_data['board'];
            $trunk_data['channel'] = 'KHOMP/' . $khomp_board;
            $b = substr($khomp_board, 1, 1);
            if (substr($khomp_board, 2, 1) == 'c') {
                $config = array(
                    "board" => $b,
                    "channel" => substr($khomp_board, 3)
                );
            } else if (substr($khomp_board, 2, 1) == 'l') {
                $config = array(
                    "board" => $b,
                    "link" => substr($khomp_board, 3)
                );
            } else {
                $config = array(
                    "board" => $b
                );
            }
            $trunk = new PBX_Asterisk_Interface_KHOMP($config);
            $trunk_data['id_regex'] = $trunk->getIncomingChannel();
        } else { // VIRTUAL
            $trunk_data['id_regex'] = $trunk_data['id_regex'] == "" ? $trunk_data['channel'] : $trunk_data['id_regex'];
        }

        // Filter data and fields to allowed types
        $ip_data = array(
            "canal" => $trunk_data['channel'],
            "type" => $trunk_data['peer_type']
        );

        foreach ($trunk_data as $field => $value) {
            if (in_array($field, $ip_fields) && $field != "type") {
                $ip_data[$field] = $value;
            }

            if (!in_array($field, $trunk_fields)) {
                unset($trunk_data[$field]);
            }
        }
        $ip_data["peer_type"] = "T";
        $ip_data["trunk"] = "yes" ;
        unset($ip_data["istrunk"]) ;

        return array("trunk" => $trunk_data, "ip" => $ip_data);
    }

    /**
     * addAction - Add trunks
     * @throws Exception
     */
    public function addAction() {

        $this->view->subTitle = $this->view->translate("Add Trunk");

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = $this->getForm();

        if ($this->getRequest()->isPost()) {
            if ($this->form->isValid($_POST)) {
               

                $trunk_data = $this->preparePost();

                $db = Snep_Db::getInstance();
                $db->beginTransaction();
                try {
                    $db->insert("trunks", $trunk_data['trunk']);
                    if ($trunk_data['trunk']['trunktype'] == "I") {
                        $db->insert("peers", $trunk_data['ip']);
                    }
                    $db->commit();
                    //log-user
                    $tabela = self::verificaLog();
                    if ($tabela == true) {

                        $name = $trunk_data['trunk']['callerid'];
                        $db = Zend_Registry::get('db');
                        $sql = "SELECT id from trunks where trunks.callerid = '$name'";
                        $stmt = $db->query($sql);
                        $id = $stmt->fetchAll();
                        $id = $id[0]["id"];
                        $acao = "Adicionou tronco";
                        self::salvaLog($acao, $id);

                        $action = "ADD";
                        $add = self::getTrunk($id);
                        self::insertLogTronco($action, $add);
                    }
                } catch (Exception $ex) {
                    $db->rollBack();
                    throw $ex;
                }
                Snep_InterfaceConf::loadConfFromDb();
                $this->_redirect("trunks");
            }
        }

        $this->view->form = $form;
        $this->renderScript("trunks/add_edit.phtml");
    }

    /**
     * verificaLog - Verify if exists module loguser  
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
     * salvaLog - Inserts data in the database
     * @param type $acao
     * @param type $trunks
     * @return boolean
     */
    function salvaLog($acao, $trunks) {
        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');
        $tipo = 2;

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $acao = mysql_escape_string($acao);

        $sql = "INSERT INTO `logs` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $trunks . "', '" . $tipo . "' , '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . NULL . "')";

        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * populatefromTrunk
     * @param <object> Snep_Form $form
     * @param type $trunk_id
     */
    protected function populateFromTrunk(Snep_Form $form, $trunk_id) {
        $db = Snep_Db::getInstance();
        $info = $db->query("select * from trunks where id='$trunk_id'")->fetch();
        $form->getSubForm("trunks")->getElement("callerid")->setValue($info['callerid']);
        $form->getSubForm("sip")->getElement("secret")->setValue($info['secret']);
        $form->getSubForm("sip")->getElement("secret")->renderPassword = true;
        $form->getSubForm("iax2")->getElement("secret")->setValue($info['secret']);
        $form->getSubForm("iax2")->getElement("secret")->renderPassword = true;
        $form->getSubForm("technology")->getElement("type")->setValue(strtolower($info['type']));

        foreach ($form->getSubForm("advanced")->getElements() as $element) {
            if (key_exists($element->getName(), $info)) {
                $element->setValue($info[$element->getName()]);
            }
        }

        foreach ($form->getSubForm(strtolower($info['type']))->getElements() as $element) {
            if (key_exists($element->getName(), $info)) {
                $element->setValue($info[$element->getName()]);
            }
        }

        if ($info['trunktype'] === "I") {
            $ip_info = $db->query("select * from peers where name='{$info['name']}'")->fetch();

            // Ajuste em nome do campo porque zend não reconhece nomes com hifem
            // BY Flavio em 05/10/2013
            $ip_info['calllimit'] = $ip_info['call-limit'];


            // Faz uma verificação e instancia uma variavel de controle do Smarty
            if ($ip_info['qualify'] === "no" || $ip_info['qualify'] === "yes") {
                $ip_info['qualify_value'] = "";
            } else {
                $ip_info['qualify_value'] = $ip_info['qualify'];
                $ip_info['qualify'] = "specify";
            }

            foreach ($form->getSubForm(strtolower($info['type']))->getElements() as $element) {
                if (key_exists($element->getName(), $ip_info)) {
                    $element->setValue($ip_info[$element->getName()]);
                }
            }
            if ($info['type'] == "SIP" || $info['type'] == "IAX2") {
                $form->getSubForm(strtolower($info['type']))->getElement("dialmethod")->setValue(strtolower($info['dialmethod']));
                $form->getSubForm(strtolower($info['type']))->getElement("peer_type")->setValue($ip_info['type']);
                $form->getSubForm("sip")->getElement("insecure")->setValue($info['insecure']);
                $form->getSubForm("iax2")->getElement("insecure")->setValue($info['insecure']);

                $cd = explode(";", $ip_info['allow']);
                $form->getSubForm(strtolower($info['type']))->getElement("codec")->setValue($cd[0]);
                $form->getSubForm(strtolower($info['type']))->getElement("codec1")->setValue($cd[1]);
                $form->getSubForm(strtolower($info['type']))->getElement("codec2")->setValue($cd[2]);
                $form->getSubForm(strtolower($info['type']))->getElement("codec3")->setValue($cd[3]);
                $form->getSubForm(strtolower($info['type']))->getElement("codec4")->setValue($cd[4]);
            }
        } else if ($info['type'] == "KHOMP" && $form->getSubForm("khomp")->getElement("board") != NULL) {
            $form->getSubForm("khomp")->getElement("board")->setValue(substr($info['channel'], 6));
        }
    }

    /**
     * editAction - Edit trunks
     * @throws Exception
     */
    public function editAction() {

        $this->view->subTitle = $this->view->translate("Edit Trunk");

        $id = mysql_escape_string($this->getRequest()->getParam("trunk"));

        //log-user
        $tabela = self::verificaLog();
        if ($tabela == true) {
            $acao = "OLD";
            $edit = self::getTrunk($id);
            self::insertLogTronco($acao, $edit);
        }

        Zend_Registry::set('cancel_url', $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $form = $this->getForm();
        $form->setAction($this->view->baseUrl() . "/index.php/trunks/edit/trunk/$id");

        if ($this->getRequest()->isPost()) {
            if ($this->form->isValid($_POST)) {

                $trunk_data = $this->preparePost();
               
                $sql = "SELECT name FROM trunks WHERE id='{$id}' LIMIT 1";
                $name_data = Snep_Db::getInstance()->query($sql)->fetch();
                $trunk_data['trunk']['name'] = $trunk_data['ip']['name'] = $name_data['name'];


                $db = Snep_Db::getInstance();
                $db->beginTransaction();
                try {
                    $db->update("trunks", $trunk_data['trunk'], "id='$id'");
                    // Trunks of type IP (SIP, IAX, SNEPSIP and SNEPIAX
                    
                            
                    if ($trunk_data['trunk']['trunktype'] == "I") {
                        $db->update("peers", $trunk_data['ip'], "name='{$trunk_data['trunk']['name']}' and peer_type='T'");
                    }
                    $db->commit();

                    //log-user
                    if ($tabela == true) {

                        $name = $trunk_data['trunk']['callerid'];
                        $db = Zend_Registry::get('db');
                        $sql = "SELECT id from trunks where trunks.callerid = '$name'";
                        $stmt = $db->query($sql);
                        $id = $stmt->fetchAll();
                        $id = $id[0]["id"];
                        $acao = "Editou tronco";
                        self::salvaLog($acao, $id);
                        $action = "NEW";
                        $edit = self::getTrunk($id);
                        self::insertLogTronco($action, $edit);
                    }
                } catch (Exception $ex) {
                    $db->rollBack();
                    throw $ex;
                }
                Snep_InterfaceConf::loadConfFromDb();
                $this->_redirect("trunks");
            }
        }

        $this->populateFromTrunk($form, $id);
        $this->view->form = $form;
        $this->renderScript("trunks/add_edit.phtml");
    }

    /**
     * removeAction - Delete trunks
     * @return type
     */
    public function removeAction() {

        try {
            $khomp_info = new PBX_Khomp_Info();
        } catch (Exception $e) {
            $this->_redirect("/trunks/asterisk-error");
            return;
        }

        $db = Zend_Registry::get('db');
        $id = $this->_request->getParam("id");
        $confirm = $this->_request->getParam('confirm');
        $name = $this->_request->getParam("name");

        // Fazendo procura por referencia a esse ramal em regras de negócio.
        $rulesQuery = "SELECT id, `desc` FROM regras_negocio WHERE origem LIKE '%T:$id%' OR destino LIKE '%T:$id%'";
        $rules = $db->query($rulesQuery)->fetchAll();

        $rulesQuery = "SELECT rule.id, rule.desc FROM regras_negocio as rule, regras_negocio_actions_config as rconf WHERE (rconf.regra_id = rule.id AND rconf.value = '$id' AND (rconf.key = 'tronco' OR rconf.key = 'trunk'))";
        $rules = array_merge($rules, $db->query($rulesQuery)->fetchAll());

        if (count($rules) > 0) {

            $this->view->error = $this->view->translate("Cannot remove. The following routes are using this trunk: ") . "<br />";
            foreach ($rules as $rule) {
                $this->view->error .= $rule['id'] . " - " . $rule['desc'] . "<br />\n";
            }

            $this->_helper->viewRenderer('error');
        } else {
            if ($confirm == 1) {

                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {
                    $acao = "Excluiu tronco";
                    self::salvaLog($acao, $id);
                    $action = "DEL";
                    $add = self::getTrunk($id);

                    self::insertLogTronco($action, $add);
                }

                $db->beginTransaction();
                $sql = "DELETE FROM peers WHERE name='$name'";
                $db->exec($sql);
                $sql = "DELETE FROM trunks WHERE id='$id'";
                $db->exec($sql);
                $db->commit();

                Snep_InterfaceConf::loadConfFromDb();
                $this->_redirect("trunks");
                //$this->_redirect($this->getRequest()->getControllerName());
            }
            $this->view->message = $this->view->translate("The trunk will be deleted. Are you sure?");
            $this->view->confirm = $this->getRequest()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/remove/id/' . $id . '/name/' . $name . '/confirm/1';
            //Snep_InterfaceConf::loadConfFromDb();
            // $this->_redirect("trunks");
        }
    }

    /**
     * importAction - Import CSV
     */
    public function importAction() {
        $ie = new Snep_CsvIE('trunks');
        $this->view->form = $ie->getForm();
        $this->view->title = "Import";
        $this->render('import_export');
    }

    /**
     * exportAction - Export for CSV
     */
    public function exportAction() {
        $ie = new Snep_CsvIE(array('trunks', ' trunktype!="I    " ', 'Troncos que não podem ser exportados: '), 'ignore');
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
     * getLastId - get last id of trunk added
     * @return <int> $tronco - last id
     */
    function getLastId() {

        $db = Zend_Registry::get("db");
        $sql = "SELECT id from  trunks order by id desc limit 1";
        $stmt = $db->query($sql);
        $result = $stmt->fetch();

        return $result["id"];
    }

    /**
     * getTrunk - set array widh data of trunk
     * @param <int> $id - Trunk code 
     * @return <array> $tronco - Data of trunk
     */
    function getTrunk($id) {

        $tronco = array();

        $db = Zend_Registry::get("db");
        $sql = "SELECT id, name, callerid, dtmfmode, insecure, username, allow, type, host, map_extensions, reverse_auth, domain from  trunks where id='$id'";
        $stmt = $db->query($sql);
        $tronco = $stmt->fetch();

        if ($tronco["type"] != "KHOMP" && $tronco["type"] != "VIRTUAL") {

            $name = $tronco["name"];
            $sql = "SELECT fromuser, fromdomain, nat, port, qualify, type as type_peer, `call-limit` as call_limit from  peers where name='$name'";
            $stmt = $db->query($sql);
            $peer = $stmt->fetch();

            foreach ($peer as $item => $info) {
                $tronco[$item] = $info;
            }
        }

        return $tronco;
    }

    /**
     * insertLogTronco - Insert data in database
     * @param <string> $acao
     * @param <array> $add
     */
    function insertLogTronco($acao, $add) {

        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $tipo = 2;

        if ($acao == "Adicionou tronco") {
            $valor = "ADD";
        } else if ($acao == "Excluiu tronco") {
            $valor = "DEL";
        } else {
            $valor = $acao;
        }

        if ($add["type"] != "KHOMP" && $add["type"] != "VIRTUAL") {
            $sql = "INSERT INTO `logs_trunk` VALUES (NULL, '" . $add["id"] . "', '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $add["name"] . "', '" . $add["callerid"] . "', '" . $add["dtmfmode"] . "', '" . $add["insecure"] . "', '" . $add["username"] . "', '" . $add["allow"] . "', '" . $add["type"] . "', '" . $add["host"] . "', '" . $add["map_extensions"] . "', '" . $add["reverse_auth"] . "', '" . $add["domain"] . "', '" . $add["nat"] . "', '" . $add["port"] . "', '" . $add["qualify"] . "', '" . $add["call_limit"] . "', '" . $valor . "')";

            $db->query($sql);
        } else {

            $sql = "INSERT INTO `logs_trunk` VALUES (NULL, '" . $add["id"] . "', '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $add["name"] . "', '" . $add["callerid"] . "', '" . $add["dtmfmode"] . "', '" . $add["insecure"] . "', '" . $add["username"] . "', '" . $add["allow"] . "', '" . $add["type"] . "', '" . $add["host"] . "', '" . $add["map_extensions"] . "', '" . $add["reverse_auth"] . "', '" . $add["domain"] . "', '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . $valor . "')";
            $db->query($sql);
        }
    }

}

