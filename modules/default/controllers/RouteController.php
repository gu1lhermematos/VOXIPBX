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
 * Route controller.
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Henrique Grolli Bassotto
 */
class RouteController extends Zend_Controller_Action {

    /**
     * Add/Edit form for routes
     *
     * @var Zend_Form
     */
    protected $form;

    /**
     * Sub-form for Action Rules
     *
     * @var array
     */
    protected $forms;
    
    /**
     * cleanSrcDst
     * @param <string> $string
     * @return <string>
     */
    protected function cleanSrcDst($string) {
        $item = explode(",", $string);

        $search = array(
            "/^G:/",
            "/^AL:/",
            "/^S$/",
            "/^X$/",
            "/^T:/",
            "/^RX:/",
            "/^R:/"
        );
        $replace = array(
            $this->view->translate("Group") . " ",
            $this->view->translate("No Destiny"),
            $this->view->translate("Any"),
            $this->view->translate("Trunk") . " ",
            "",
            $this->view->translate("Extension") . " ",
        );

        foreach ($item as $key => $entry) {
            //Zend_Debug::Dump($entry);exit;
            if (substr($entry, 0, 1) == "T") {
                $entry = "T:" . PBX_Trunks::get(substr($entry, 2))->getName();
            }
            if (substr($entry, 0, 1) == "X") {
                $entry = "Qualquer";
            }
            if (substr($entry, 0, 2) == "RX") {
                $expression = explode(":", $entry);
                $result = $expression[1];
                $entry = "Expressao: " . $result;
            }

            if (substr($entry, 0, 1) == "A") {
                $alias = explode(":", $entry);
                $idalias = $alias[1];

                $db = Zend_Registry::get('db');
                $sql = "SELECT name from  expr_alias where  expr_alias.aliasid = '$idalias'";
                $result = $db->query($sql)->fetchAll();
                $result = $result[0]["name"];

                $entry = "Alias: " . $result;
            }
            if (substr($entry, 0, 1) == "G") {
                $grupo = explode(":", $entry);

                $idgrupo = $grupo[1];
                if ($idgrupo == "all") {
                    $result = " " . $this->view->translate("all");
                } else
                if ($idgrupo == "users") {
                    $result = " " . $this->view->translate("users");
                } else {
                    $result = $idgrupo;
                }

                $entry = $this->view->translate("Peers group") ." :" .$result;
            }

            $item[$key] = preg_replace($search, $replace, $entry);
        }

        return implode("<br />", $item);
    }

    /**
     * indexAction - List all Routes of the system
     */
    public function indexAction() {


        $db = Zend_Registry::get('db');
        $select = $db->select()->from("regras_negocio", array("id", "origem", "destino", "desc", "ativa", "prio")
        );

        $this->view->filter_value = Snep_Filter::setSelect($select, array("id", "origem", "destino", "desc", "prio"), $this->_request);
        $this->view->order = Snep_Order::setSelect($select, array("prio", "id", "origem", "destino", "desc"), $this->_request);
        $this->view->limit = Snep_Limit::get($this->_request);

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );
        $this->view->filtro = $this->_request->getParam('filtro');

        $paginatorAdapter = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage($this->view->limit);

        $this->view->contacts = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->PAGE_URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/";

        $routes = $db->query($select)->fetchAll();

        foreach ($routes as $key => $route) {

            $routes[$key]['origem'] = $this->cleanSrcDst($route['origem']);
            $routes[$key]['destino'] = $this->cleanSrcDst($route['destino']);
        }

        $this->view->routes = $routes;

        // Formulário de filtro.
        $filter = new Snep_Form_Filter();
        $filter->setAction($this->getFrontController()->getBaseUrl() . '/route');
        $filter->setValue($this->view->limit);
        $filter->setFieldValue($this->view->filter_value);
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/");

        $this->view->form_filter = $filter;
        $this->view->title = "Routes";
        $this->view->filter = array(
            array(
                "url" => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/frame/actionkey/add/",
                "display" => $this->view->translate("Add Rule"),
                "css" => "include"
            )
        );
    }

    /**
     * getForm - Generate the form for routes
     * @return <object> Zend_Form
     */
    protected function getForm() {
        if ($this->form === Null) {
            $form_xml = new Zend_Config_Xml(Zend_Registry::get("config")->system->path->base . "/modules/default/forms/route.xml");
            $form = new Snep_Form($form_xml);

            $actions = PBX_Rule_Actions::getInstance();
            $installed_actions = array();
            foreach ($actions->getInstalledActions() as $action) {
                $action_instance = new $action();
                $installed_actions[$action] = $action_instance->getName();
            }
            asort($installed_actions);
            $this->view->actions = $installed_actions;

            $src = new Snep_Form_Element_Html("route/elements/src.phtml", "src", false);
            $src->setLabel($this->view->translate("Source"));
            $src->setOrder(1);
            $form->addElement($src);

            $dst = new Snep_Form_Element_Html("route/elements/dst.phtml", "dst", false);
            $dst->setLabel($this->view->translate("Destiny"));
            $dst->setOrder(2);
            $form->addElement($dst);

            $time = new Snep_Form_Element_Html("route/elements/time.phtml", "time", false);
            $time->setOrder(4);
            $time->setLabel($this->view->translate("Valid times"));
            $form->addElement($time);

            $form->addElement(new Snep_Form_Element_Html("route/elements/actions.phtml", "actions"));

            $this->form = $form;

            $groups = new Snep_GruposRamais();
            $groups = $groups->getAllRules();
            foreach ($groups as $group) {
                switch ($group['name']) {
                    case 'all':
                        $gnt = $this->view->translate("All");
                        break;
                    case 'admin':
                        $gnt = $this->view->translate("Administrator");
                        break;
                    case 'users':
                        $gnt = $this->view->translate("Users");
                        break;
                    default:
                        $gnt = $group['name'];
                }
                //$group_list .= "[\"{$group['name']}\", \"{$group['name']}\"],";
                $group_list .= "[\"{$group['name']}\", \"{$gnt}\"],";
            }
            $group_list = "[" . trim($group_list, ",") . "]";

            $this->view->group_list = $group_list;

            $alias_list = "";
            foreach (PBX_ExpressionAliases::getInstance()->getAll() as $alias) {
                $alias_list .= "[\"{$alias['id']}\", \"{$alias['name']}\"],";
            }
            $alias_list = "[" . trim($alias_list, ",") . "]";
            $this->view->alias_list = $alias_list;

            $trunks = "";
            foreach (PBX_Trunks::getAll() as $trunk) {
                $trunks .= "[\"{$trunk->getId()}\", \"{$trunk->getName()}\"],";
            }
            $trunks = "[" . trim($trunks, ",") . "]";
            $this->view->trunk_list = $trunks;

            $cgroup_list = "";
            $cgroup_manager = new Snep_ContactGroups_Manager();
            foreach ($cgroup_manager->getAll() as $cgroup) {
                $cgroup_list .= "[\"{$cgroup['id']}\", \"{$cgroup['name']}\"],";
            }
            $cgroup_list = "[" . trim($cgroup_list, ",") . "]";
            $this->view->contact_groups_list = $cgroup_list;
        }

        return $this->form;
    }

    /**
     * editAction - Edit Route
     */
    public function editAction() {
        $id = $this->getRequest()->getParam('id');
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Routing"),
                    $this->view->translate("Routes"),
                    $this->view->translate("Edit Route %s", $id)
        ));

        $form = $this->getForm();
        $this->view->form = $form;

        try {
            $rule = PBX_Rules::get(mysql_escape_string($id));
        } catch (PBX_Exception_NotFound $ex) {
            throw new Zend_Controller_Action_Exception('Page not found.', 404);
        }

        if ($_POST) {
            if ($this->isValidPost()) {
                
                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {
                    $acao = "Editou Regra";
                    $this->salvalog($acao, $rule->getId());
                }
                
                $new_rule = $this->parseRuleFromPost();
                $new_rule->setId($id);
                $new_rule->setActive($rule->isActive());
                PBX_Rules::update($new_rule);
                //$this->_redirect("route");

                echo "<div style='display:none' id='goto'>" . $this->getFrontController()->getBaseUrl() . "/route</div>";
                exit();
            } else {
                $actions = "";
                foreach ($this->forms as $form_id => $form) {
                    $actions .= "addAction(" . json_encode(array(
                                "id" => $form_id,
                                "status" => $form['status'],
                                "type" => $form['type'],
                                "form" => $form['formData']
                            )) . ")\n";
                }
                $actions .= "setActiveAction($('actions_list').firstChild)\n";
                $this->view->rule_actions = $actions;
            }
        }

        $this->populateFromRule($rule);

        if (!isset($actions)) {
            $actions = "getRuleActions({$rule->getId()});\n";
            $this->view->rule_actions = $actions;
        }

        $this->_helper->layout()->disableLayout();
        $this->renderScript('route/add_edit.phtml');
    }

    /**
     * duplicateAction - Duplicate Route
     */
    public function duplicateAction() {
        $form = $this->getForm();
        $this->view->form = $form;

        $id = $this->getRequest()->getParam('id');
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Routing"),
                    $this->view->translate("Routes"),
                    $this->view->translate("Duplicate Route %s", $id)
        ));

        try {
            $rule = PBX_Rules::get(mysql_escape_string($id));
        } catch (PBX_Exception_NotFound $ex) {
            throw new Zend_Controller_Action_Exception('Page not found.', 404);
        }

        if ($_POST) {
            if ($this->isValidPost()) {
                
                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {
                    $acao = "Duplicou Regra";
                    $this->salvalog($acao, $rule->getId());
                }
                
                $new_rule = $this->parseRuleFromPost();
                $new_rule->setActive($rule->isActive());
                PBX_Rules::register($new_rule);
                
                //log-user
                if($tabela == true){
                 $lastId = $this->getLastId();
                 $dpl = $this->getRegra($lastId);
                 $this->insertLogRegra($acao,$dpl);
                }
                
                //$this->_redirect("route");
                echo "<div style='display:none' id='goto'>" . $this->getFrontController()->getBaseUrl() . "/route</div>";
                exit();
            } else {
                $actions = "";
                foreach ($this->forms as $form_id => $form) {
                    $actions .= "addAction(" . json_encode(array(
                                "id" => $form_id,
                                "status" => $form['status'],
                                "type" => $form['type'],
                                "form" => $form['formData']
                            )) . ")\n";
                }
                $actions .= "setActiveAction($('actions_list').firstChild)\n";
                $this->view->rule_actions = $actions;
            }
        }

        $rule->setDesc($this->view->translate("Copy of %s", $rule->getDesc()));

        $this->populateFromRule($rule);

        if (!isset($actions)) {
            $actions = "getRuleActions({$rule->getId()});\n";
            $this->view->rule_actions = $actions;
        }

        $this->_helper->layout()->disableLayout();
        $this->renderScript('route/add_edit.phtml');
    }

    /**
     * addAction - Action for adding a route
     */
    public function addAction() {
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Routing"),
                    $this->view->translate("Routes"),
                    $this->view->translate("Add Route")
        ));

        $form = $this->getForm();
        $form->getElement('week')->setValue(true);
        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            if ($this->isValidPost()) {

                $rule = $this->parseRuleFromPost();
                PBX_Rules::register($rule);
                
                //log-user
                $tabela = self::verificaLog();
                if ($tabela == true) {
                    $acao = "Adicionou Regra";
                    $this->salvalog($acao, $rule->getId());
                    $add = $this->getRegra($rule->getId());
                    $this->insertLogRegra($acao, $add);
                }

                //$this->_redirect("route");
                echo "<div style='display:none' id='goto'>" . $this->getFrontController()->getBaseUrl() . "/route</div>";
                exit();
            } else {
                $actions = "";
                foreach ($this->forms as $id => $form) {
                    $actions .= "addAction(" . json_encode(array(
                                "id" => $id,
                                "status" => $form['status'],
                                "type" => $form['type'],
                                "form" => $form['formData']
                            )) . ")\n";
                }
                $actions .= "setActiveAction($('actions_list').firstChild)\n";

                $this->view->rule_actions = $actions;

                unset($_POST['actions_order']);
                $rule = $this->parseRuleFromPost($_POST);
                $this->populateFromRule($rule);
            }
        } else {
            $this->view->dt_agirules = array(
                "dst" => "dstObj.addItem();\n",
                "src" => "origObj.addItem();\n",
                "time" => "timeObj.addItem();\n",
            );
        }
        $this->_helper->layout()->disableLayout();
        $this->renderScript('route/add_edit.phtml');
    }

    /**
     * isValidPost - Validates $_POST for the required fields of the form.
     *
     * This method is implemented to validate the fields that can't be validated by
     * Zend_Form like the fields of Action Rules.
     *
     * @param array $post
     * @return boolean
     */
    protected function isValidPost($post = null) {

        return true; //bug js
        /*
          $post = $post === null ? $_POST : $post;
          $assert = true;

          parse_str($post['actions_order'], $actions_order);
          $forms = array();
          foreach ($actions_order['actions_list'] as $action) {
          $real_action = new $post["action_$action"]["action_type"]();
          $action_config = new Snep_Rule_ActionConfig($real_action->getConfig());
          $action_config->setActionId("action_$action");

          $form = $action_config->getForm();
          $form->removeElement("submit");
          $form->removeElement("cancel");

          $action_type_element = new Zend_Form_Element_Hidden("action_type");
          $action_type_element->setValue(get_class($real_action));
          $action_type_element->setDecorators(array("ViewHelper"));
          $form->addElement($action_type_element);

          if (!$form->isValid($post["action_$action"])) {
          $assert = false;
          $status = "error";
          } else {
          $status = "success";
          }

          $form->setView(new Zend_View);
          $forms["action_$action"] = array(
          "type" => $post["action_$action"]["action_type"],
          "formData" => $form->render(),
          "status" => $status
          );
          }

          if (!$this->form->isValid($_POST)) {
          $assert = false;
          $status = "error";
          }

          if (!$assert) {
          $this->forms = $forms;
          return false;
          } else {
          $this->forms = null;
          return true;
          } */
    }
    
    /**
     * getRegra - Get data in the route
     * @param <int> $id - Code route
     * @return <array> $regra - data of rout
     */
    function getRegra($id) {

        $regra = array();
        $action = array();

        $db = Zend_Registry::get("db");
        $sql = "SELECT * from  regras_negocio where id='$id'";
        $stmt = $db->query($sql);
        $regra = $stmt->fetch();

        $sql = "SELECT * FROM `regras_negocio_actions` where `regra_id`='$id'";
        $stmt = $db->query($sql);
        $acoes = $stmt->fetchall();

        $sql = "SELECT * FROM `regras_negocio_actions_config` where `regra_id`='$id'";
        $stmt = $db->query($sql);
        $valores = $stmt->fetchall();

        foreach ($acoes as $item => $acao) {
            foreach ($valores as $key => $valor) {

                $regra["acoes"][$item]["prio"] = $acao["prio"];
                $regra["acoes"][$item]["action"] = $acao["action"];
                if ($acao["prio"] == $valor["prio"]) {

                    $regra["acoes"][$item]["key"] .= $valor["key"] . " | ";
                    $regra["acoes"][$item]["value"] .= $valor["value"] . " | ";
                }
            }
        }
        return $regra;
    }

    
    /**
     * getLastId - get id of last route
     * @return <int> $regra - Id last route
     */
    function getLastId() {

        $db = Zend_Registry::get("db");
        $sql = "SELECT id from  regras_negocio order by id desc limit 1";
        $stmt = $db->query($sql);
        $result = $stmt->fetch();
                        
        return $result["id"];
    } 
    
    /**
     * insertLogRegra - insert log on table logs_regra
     * @param <array> $add
     */
    function insertLogRegra($acao,$add) {

        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');
        
        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        
        $tipo = 1;
        
        if($acao == "Adicionou Regra"){
            $valor = "ADD";
        }else if($acao == "Excluiu Regra"){
            $valor = "DEL";
        }else{
            $valor = "DPL";
        }
        
        $actions_add = $add['acoes'];


        //add historico
        foreach ($actions_add as $number => $item) {

            // Pega somente nome da ação. Ex: DiscarRamal de PBX_Rule_Action_DiscarRamal
            if (strpos($item['action'], "_") !== false) {
                $action = $item['action'];
                $action = explode("_", $action);
                $action = $action[3];
            } else {
                // Ação ARS não possui PBX_Rule_Action_ no nome da ação
                $action = $item['action'];
            }

            $sql = "INSERT INTO `logs_regra` VALUES (NULL, '" . $add["id"] . "', '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $add["prio"] . "' , '" . $add["desc"] . "', '" . $add["origem"] . "', '" . $add["destino"] . "', '" . $add["validade"] . "', '" . $add["diasDaSemana"] . "', '" . $add["record"] . "', '" . $add["ativa"] . "', '" . $action . "', '" . $item["prio"] . "', '" . $item["key"] . "', '" . $item["value"] . "', '" . $valor . "')";
            $db->query($sql);
        }
    }

    /**
     * populateFromRule - Populate the fields based on a specific route
     * @param <object> PBX_Rule $rule
     */
    protected function populateFromRule(PBX_Rule $rule) {
        $srcList = $rule->getSrcList();
        $src = "origObj.addItem(" . count($srcList) . ");";
        foreach ($srcList as $index => $_src) {
            $src .= "origObj.widgets[$index].type='{$_src['type']}';\n";
            $src .= "origObj.widgets[$index].value='{$_src['value']}';\n";
        }

        $dstList = $rule->getDstList();
        $dst = "dstObj.addItem(" . count($dstList) . ");";
        foreach ($dstList as $index => $_dst) {
            $dst .= "dstObj.widgets[$index].type='{$_dst['type']}';\n";
            $dst .= "dstObj.widgets[$index].value='{$_dst['value']}';\n";
        }

        $timeList = $rule->getValidTimeList();
        $time = "timeObj.addItem(" . count($timeList) . ");";
        foreach ($timeList as $index => $_time) {
            $_time = explode('-', $_time);
            $time .= "timeObj.widgets[$index].startTime='{$_time[0]}';\n";
            $time .= "timeObj.widgets[$index].endTime='{$_time[1]}';\n";
        }

        // Treatment of the active time of the route
        $horario = $rule->getValidTimeList();
        $data = explode("-", $horario['0']);

        $this->view->dt_agirules = array(
            "dst" => $dst,
            "src" => $src,
            "time" => $time
        );

        $form = $this->getForm();

        $form->getElement('desc')->setValue($rule->getDesc());
        $form->getElement('record')->setValue($rule->isRecording());
        $form->getElement('prio')->setValue("p" . $rule->getPriority());

        $form->getElement('week')->setValue($rule->getValidWeekDays());
    }

    /**
     * parseRuleFromPost - Parse a route based on it's POST.
     * It's assumed here that all fields are already validated
     *
     * @param <array> $postData optional for ovewrite post data
     * @return <object> PBX_Rule
     */
    protected function parseRuleFromPost($post = null) {
        $post = $post === null ? $_POST : $post;

        $rule = new PBX_Rule();

        // Adicionando dias da semana
        $weekDays = array("sun", "mon", "tue", "wed", "thu", "fri", "sat");
        $rule->cleanValidWeekList();
        foreach ($weekDays as $day) {
            if (in_array($day, $post['week'])) {
                $rule->addWeekDay($day);
            }
        }

        // Adicionando Origens
        foreach (explode(',', $post['srcValue']) as $src) {
            if (!strpos($src, ':')) {
                $rule->addSrc(array("type" => $src, "value" => ""));
            } else {
                $info = explode(':', $src);
                if (!is_array($info) OR count($info) != 2) {
                    throw new PBX_Exception_BadArg("Valor errado para origem da regra de negocio.");
                }

                if ($info[0] == "T") {
                    try {
                        PBX_Trunks::get($info[1]);
                    } catch (PBX_Exception_NotFound $ex) {
                        throw new PBX_Exception_BadArg("Tronco inválido para origem da regra");
                    }
                }

                $rule->addSrc(array("type" => $info[0], "value" => $info[1]));
            }
        }

        // Adding destinys
        foreach (explode(',', $post['dstValue']) as $dst) {
            if (!strpos($dst, ':')) {
                $rule->addDst(array("type" => $dst, "value" => ""));
            } else {
                $info = explode(':', $dst);
                if (!is_array($info) OR count($info) != 2) {
                    throw new PBX_Exception_BadArg("Valor errado para destino da regra de negocio.");
                }

                if ($info[0] == "T") {
                    try {
                        PBX_Trunks::get($info[1]);
                    } catch (PBX_Exception_NotFound $ex) {
                        throw new PBX_Exception_BadArg("Tronco inválido para destino da regra");
                    }
                }

                $rule->addDst(array("type" => $info[0], "value" => $info[1]));
            }
        }

        // Adding time
        $rule->cleanValidTimeList();
        foreach (explode(',', $post['timeValue']) as $time_period) {
            $rule->addValidTime($time_period);
        }

        // Adding Description
        $rule->setDesc($post['desc']);

        // Defining recording order
        if (isset($post['record']) && $post['record']) {
            $rule->record();
        }

        // Defining priority
        $rule->setPriority(substr($post['prio'], 1));

        if (isset($post['actions_order'])) {
            parse_str($post['actions_order'], $actions_order);
            if (is_array($actions_order['actions_list']))
                foreach ($actions_order['actions_list'] as $action) {
                    $real_action = new $post["action_$action"]["action_type"]();
                    $action_config = new Snep_Rule_ActionConfig($real_action->getConfig());
                    $real_action->setConfig($action_config->parseConfig($post["action_$action"]));
                    $rule->addAction($real_action);
                }
        }

        return $rule;
    }
    
    /**
     * salvaLog - insert log un database
     * @param <string> $acao
     * @param <int> $rule
     * @return <boolean>
     */
    public function salvaLog($acao, $rule) {
        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $tipo = 1;
        $acao = mysql_escape_string($acao);

        $sql = "INSERT INTO `logs` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $rule . "', '" . $tipo . "' , '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . NULL . "')";

        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * salvaLogAction - insert log of action in the route
     * @param <string> $acao
     * @param <int> $rule
     * @param <string> $result
     * @return <boolean>
     */
    public function salvaLogAction($acao, $rule, $result) {
        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();
        $tipo = 1;
        $acao = mysql_escape_string($acao);

        $sql = "INSERT INTO `logs` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $rule . "', '" . $tipo . "' , '" . $result . "', '" . NULL . "', '" . NULL . "', '" . NULL . "')";

        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * verificalog - verify if exists module Loguser
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
     * deleteAction - Delete route
     * @throws Zend_Controller_Action_Exception
     */
    public function deleteAction() {
        $id = mysql_escape_string($this->getRequest()->getParam('id'));

        try {
            $rule = PBX_Rules::get($id);
        } catch (PBX_Exception_NotFound $ex) {
            throw new Zend_Controller_Action_Exception('Page not found.', 404);
        }
        
        $del = $this->getRegra($rule->getId());
        
        PBX_Rules::delete($id);
        
        //log-user
        $tabela = self::verificaLog();
        if ($tabela == true) {
            $acao = "Excluiu Regra";
            $this->salvalog($acao, $rule->getId());
            $this->insertLogRegra($acao,$del);
        }
        
        $this->_redirect("route");
    }
    
    /**
     * toogleAction
     */
    public function toogleAction() {

        $route = $this->getRequest()->getParam('route');

        $regras = PBX_Rules::get($route);

        if ($regras->isActive()) {
            $regras->disable();
        } else {
            $regras->enable();
        }

        PBX_Rules::update($regras);
    }
    
    /**
     * frameAction
     */
    public function frameAction() {
        if ($this->_request->getParam('actionkey') == "add")
            $this->view->subTitle = $this->view->translate("Add Route");
        else
            $this->view->subTitle = $this->view->translate("Edit Route");
        $this->view->action = $this->_request->getParam('actionkey');
    }

}
