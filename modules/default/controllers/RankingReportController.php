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
 * Controller ranking report
 */
class RankingReportController extends Zend_Controller_Action {

    private $form;

    /**
     * inndexAction - Filter of rankong report
     */
    public function indexAction() {
        // Title
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Reports"),
                    $this->view->translate("Call Ranking"))
        );

        $config = Zend_Registry::get('config');
        $form = $this->getForm();

        if ($this->_request->getPost()) {

            $formIsValid = $form->isValid($_POST);
            $formData = $this->_request->getParams();

            $locale = Snep_Locale::getInstance()->getLocale();

            if ($locale == 'en_US') {
                $format = 'yyyy-MM-dd';
            } else {
                $format = Zend_Locale_Format::getDateFormat($locale);
            }

            $ini_date = explode(" ", $formData['period']['init_day']);
            $final_date = explode(" ", $formData['period']['till_day']);

            $ini_date_valid = Zend_Date::isDate($ini_date[0], $format);
            $final_date_valid = Zend_Date::isDate($final_date[0], $format);

            if (!$ini_date_valid) {
                $iniDateElem = $form->getSubForm('period')->getElement('init_day');
                $iniDateElem->addError($this->view->translate('Invalid Date'));
                $formIsValid = false;
            }
            if (!$final_date_valid) {
                $finalDateElem = $form->getSubForm('period')->getElement('till_day');
                $finalDateElem->addError($this->view->translate('Invalid Date'));
                $formIsValid = false;
            }

            if ($formIsValid) {
                $_SESSION['formDataRRC'] = $formData;
                $this->_helper->redirector('view');
            }
        }

        $this->view->form = $form;
    }

    /**
     * getForm - Get form ranking report
     * @return <object>\Snep_Form
     */
    protected function getForm() {

        $form = new Snep_Form();

        // Set form action
        $form->setAction($this->getFrontController()->getBaseUrl() . '/ranking-report/index');

        $form_xml = new Zend_Config_Xml('./modules/default/forms/ranking_report.xml');
        $config = Zend_Registry::get('config');
        $period = new Snep_Form_SubForm($this->view->translate("Period"), $form_xml->period);

        $locale = Snep_Locale::getInstance()->getLocale();
        $now = Zend_Date::now();

        if ($locale == 'en_US') {
            $init = $now->toString('YYYY-MM-01 00:00');
            $now = $now->toString('YYYY-MM-dd 23:59');
        } else {
            $init = $now->toString('01/MM/YYYY 00:00');
            $now = $now->toString('dd/MM/YYYY 23:59');
        }

        $yesterday = Zend_Date::now()->subDate(1);
        $initDay = $period->getElement('init_day');
        $initDay->setValue($init);

        $tillDay = $period->getElement('till_day');
        $tillDay->setValue($now);

        $form->addSubForm($period, "period");

        $rank = new Snep_Form_SubForm($this->view->translate("Ranking Options"), $form_xml->rank);
        $selectNumView = $rank->getElement('view');

        for ($index = 1; $index <= 30; $index++) {
            $selectNumView->addMultiOption($index, $index);
        }

        $form->addSubForm($rank, "rank");

        $form->getElement('submit')->setLabel($this->view->translate("Show Report"));
        $form->removeElement("cancel");
        return $form;
    }

    /**
     * getQuery - Get query for report
     * @param <array> $data
     * @param <boolean> $exportCsv
     * @return type
     * @throws Exception
     */
    protected function getQuery($data, $exportCsv = false) {

        $init_day = $data['period']['init_day'];
        $final_day = $data['period']['till_day'];

        $formated_init_day = new Zend_Date($init_day);
        $fromDay = $formated_init_day->toString('yyyy-MM-dd');
        $fromTime = $formated_init_day->toString('HH:mm');

        $formated_final_day = new Zend_Date($final_day);
        $tillDay = $formated_final_day->toString('yyyy-MM-dd');
        $tillTime = $formated_final_day->toString('HH:mm');


        $rankType = $data["rank"]["type"];
        $rankOrigins = $data["rank"]["origin"];
        $rankView = $data["rank"]["view"];


        $config = Zend_Registry::get('config');
        $db = Zend_Registry::get('db');

        $dateClause = " calldate >= '$fromDay $fromTime'";
        $dateClause.=" AND calldate <= '$tillDay $tillTime'";
        $dateClause.=" AND DATE_FORMAT(calldate,'%T') >= '$fromTime:00'";
        $dateClause.=" AND DATE_FORMAT(calldate,'%T') <= '$tillTime:59' ";

        $whereCond = " WHERE $dateClause";

        $prefixInout = $config->ambiente->prefix_inout;

        if (strlen($prefixInout) > 6) {
            $condPrefix = "";
            $prefixArray = explode(";", $prefixInout);

            foreach ($prefixArray as $valor) {
                $pair = explode("/", $valor);

                $prefixIn = $pair[0];
                $prefixOut = isset($pair[1]);

                $prefixInSize = strlen($prefixIn);
                $prefixOutSize = strlen($prefixOut);

                if (isset($condprefix)) {
                    $condPrefix .= " substr(dst,1,$prefixInSize) != '$prefixIn' ";
                    if (!$prefixOut == '') {
                        $condPrefix .= " AND substr(dst,1,$prefixOutSize) != '$prefixOut' ";
                    }
                    $condPrefix .= " AND ";
                }
            }
            if ($condPrefix != "")
                $whereCond .= " AND ( " . substr($condPrefix, 0, strlen($condPrefix) - 4) . " ) ";
        }

        $condDstExp = "";
        $dstExceptions = $config->ambiente->dst_exceptions;
        $dstExceptions = explode(";", $dstExceptions);

        foreach ($dstExceptions as $valor) {
            $condDstExp .= " dst != '$valor' ";
            $condDstExp .= " AND ";
        }
        $whereCond .= " AND ( " . substr($condDstExp, 0, strlen($condDstExp) - 4) . " ) ";

        /* Vinc */
        $name = Zend_Auth::getInstance()->getIdentity();
        $sql = "SELECT id_peer, id_vinculado FROM permissoes_vinculos WHERE id_peer ='$name'";
        $result = $db->query($sql)->fetchObject();

        $vincTable = "";
        $vincWhere = "";

        if ($result) {
            $vincTable = " ,permissoes_vinculos ";
            $vincWhere = " AND ( permissoes_vinculos.id_peer='{$result->id_peer}' AND (cdr.src = permissoes_vinculos.id_vinculado OR cdr.dst = permissoes_vinculos.id_vinculado) ) ";
        }
        $whereCond .= " AND ( locate('ZOMBIE',channel) = 0 ) ";

        $sql = "SELECT cdr.src, cdr.dst, cdr.disposition, cdr.duration, cdr.billsec, cdr.userfield ";
        $sql .= " FROM cdr " . $vincTable . $whereCond . " " . $vincWhere . " ORDER BY calldate,userfield,amaflags";


        $rankData = array();

        try {

            $flag = $disposition = "";
            $dst = "";
            $brk = False;

            //verificar se existe o módulo cc e tabela cc_configuration no banco
            $configuration = self::verificaCC();
            foreach ($db->query($sql) as $row) {

                if ($configuration != false) {

                    $listaAgentes = self::ListaAgentes();

                    //verifica se origem e destino são agentes para renomear
                    $srcAgente = $row['src'];
                    $dstAgente = $row['dst'];

                    foreach ($listaAgentes as $list => $agentes) {

                        if ($agentes["code"] == $srcAgente) {

                            if ($configuration == "ambos") {
                                $srcAgente = $agentes["code"] . " - (" . rtrim($agentes["name"]) . ")";
                            } else if ($configuration == "name") {
                                $srcAgente = rtrim($agentes["name"]);
                            } else {
                                $srcAgente = $agentes["code"];
                            }
                        }

                        if ($agentes["code"] == $dstAgente) {

                            if ($configuration == "ambos") {
                                $dstAgente = $agentes["code"] . " - (" . rtrim($agentes["name"]) . ")";
                            } else if ($configuration == "name") {
                                $dstAgente = rtrim($agentes["name"]);
                            } else {
                                $dstAgente = $agentes["code"];
                            }
                        }
                    }

                    $row['src'] = $srcAgente;
                    $row['dst'] = $dstAgente;
                }

                // Trata das Chamadas - Quantidades
                if ($flag == $row['userfield']) {
                    $disposition = $row['disposition'];
                    $src = $this->formatNumberAsPhone($row['src']);
                    $dst = $this->formatNumberAsPhone($row['dst']);
                    $brk = False;
                    continue;
                } else {
                    $dst = $row['dst'];
                    if (!isset($disposition) || $disposition == "") { // Primeira vez
                        $flag = $row['userfield'];
                        $disposition = $row['disposition'];
                        $src = $this->formatNumberAsPhone($row['src']);
                        $dst = $this->formatNumberAsPhone($row['dst']);
                        $brk = False;
                        continue;
                    }
                    $brk = True;
                }

                if (!isset($duration)) {
                    $duration = '';
                }

                if (!isset($rankData[$src])) {

                    $rankData[$src][$dst]["QA"] = 0;
                    $rankData[$src][$dst]["QN"] = 0;
                    $rankData[$src][$dst]["QT"] = 0;
                    $rankData[$src][$dst]["TA"] = 0;
                    $rankData[$src][$dst]["TN"] = 0;
                    $rankData[$src][$dst]["TT"] = 0;
                    $countTotal[$src] = 0;
                    $timeTotal[$src] = 0;
                }
                if (!isset($rankData[$src][$dst]["QA"]))
                    $rankData[$src][$dst]["QA"] = 0;
                if (!isset($rankData[$src][$dst]["QN"]))
                    $rankData[$src][$dst]["QN"] = 0;
                if (!isset($rankData[$src][$dst]["QT"]))
                    $rankData[$src][$dst]["QT"] = 0;
                if (!isset($rankData[$src][$dst]["TA"]))
                    $rankData[$src][$dst]["TA"] = 0;
                if (!isset($rankData[$src][$dst]["TN"]))
                    $rankData[$src][$dst]["TN"] = 0;
                if (!isset($rankData[$src][$dst]["TT"]))
                    $rankData[$src][$dst]["TT"] = 0;

                switch ($disposition) {
                    case "ANSWERED":
                        $rankData[$src][$dst]["QA"]++;
                        $rankData[$src][$dst]["TA"] += $duration;
                        break;
                    default:
                        $rankData[$src][$dst]["QN"]++;
                        $rankData[$src][$dst]["TN"] += $duration;
                        break;
                }

                $rankData[$src][$dst]["QT"]++;
                $rankData[$src][$dst]["TT"] += $duration;
                $countTotal[$src]++;
                $timeTotal[$src] += $duration;

                $disposition = $row['disposition'];
                $src = $row['src'];
                $dst = $row['dst'];
                $duration = $row['duration'];
                unset($brk);
            } // Fim do Foreach que varre o SELECT do CDR
            if (!isset($rankData)) {
                if (!isset($rankData[$src][$dst]["QA"]))
                    $rankData[$src][$dst]["QA"] = 0;
                if (!isset($rankData[$src][$dst]["QN"]))
                    $rankData[$src][$dst]["QN"] = 0;
                if (!isset($rankData[$src][$dst]["QT"]))
                    $rankData[$src][$dst]["QT"] = 0;
                if (!isset($rankData[$src][$dst]["TA"]))
                    $rankData[$src][$dst]["TA"] = 0;
                if (!isset($rankData[$src][$dst]["TN"]))
                    $rankData[$src][$dst]["TN"] = 0;
                if (!isset($rankData[$src][$dst]["TT"]))
                    $rankData[$src][$dst]["TT"] = 0;


                if (!isset($rankData[$src])) {
                    $rankData[$src][$dst]["QA"] = 0;
                    $rankData[$src][$dst]["QN"] = 0;
                    $rankData[$src][$dst]["QT"] = 0;
                    $rankData[$src][$dst]["TA"] = 0;
                    $rankData[$src][$dst]["TN"] = 0;
                    $rankData[$src][$dst]["TT"] = 0;
                    $countTotal[$src] = 0;
                    $timeTotal[$src] = 0;
                }

                switch ($disposition) {
                    case "ANSWERED":
                        $rankData[$src][$dst]["QA"]++;
                        $rankData[$src][$dst]["TA"] += $duration;
                        break;
                    default:
                        $rankData[$src][$dst]["QN"]++;
                        $rankData[$src][$dst]["TN"] += $duration;
                        break;
                } // Fim do switch
                $rankData[$src][$dst]["QT"]++;
                $rankData[$src][$dst]["TT"] += $duration;
                $countTotal[$src]++;
                $timeTotal[$src] += $duration;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (count($rankData) <= 1) {
            return;
        }
        arsort($countTotal);
        arsort($timeTotal);


        $totView = $rankOrigins - 1;
        if ($rankType == "num") {
            foreach ($countTotal as $src => $qtd) {
                $ctd = $rankView;
                if (isset($rankData[$src])) {
                    foreach ($rankData[$src] as $dst => $val) {
                        if ($ctd == 0)
                            break;
                        $ctd--;
                        $rank[$src] [$val['QT']] [$dst] = $val;
                    }
                }
                if ($totView == 0)
                    break;
                $totView--;
            }
        } else {
            foreach ($timeTotal as $src => $qtd) {
                $ctd = $rankView;
                if (isset($rankData[$src])) {
                    foreach ($rankData[$src] as $dst => $val) {
                        if ($ctd == 0)
                            break;
                        $ctd--;
                        $timeMinutes = $val['TT'];
                        $rank[$src] [$timeMinutes] [$dst] = $val;
                    }
                }
                if ($totView == 0)
                    break;
                $totView--;
            }
        }
        foreach ($rank as $src => $vqtd) {
            krsort($vqtd);
            foreach ($vqtd as $qtd => $vdst) {

                foreach ($vdst as $dst => $val) {
                    $val['TA'] = $this->formatSecondsAsTime($val['TA']);
                    $val['TN'] = $this->formatSecondsAsTime($val['TN']);
                    $val['TT'] = $this->formatSecondsAsTime($val['TT']);
                    $rank[$src][$qtd][$dst] = $val;
                }
            }
        }

        foreach ($timeTotal as $key => $value) {

            $timeTotal[$key] = $this->formatSecondsAsTime($value);
        }
        foreach ($rank as $key => $value) {
            krsort($rank[$key]);
        }

        if ($exportCsv) {


            $resultRank = array();

            foreach ($rank as $chaves => $valores) {
                $rankTmp = array();
                $rankTmp['origem'] = $chaves;
                foreach ($valores as $key => $value) {
                    foreach ($value as $k => $v) {
                        $rankTmp['destino'] = $k;
                        $rankTmp['QA'] = $v['QA'];
                        $rankTmp['QN'] = $v['QN'];
                        $rankTmp['TA'] = $v['TA'];
                        $rankTmp['TN'] = $v['TN'];
                        $resultRank[] = $rankTmp;
                    }
                }
            }
            $titulo = array(
                "origem" => $this->view->translate("SOURCE"),
                "destino" => $this->view->translate("DESTINATION"),
                "QA" => $this->view->translate("N. ANSWERD"),
                "QN" => $this->view->translate('N. UNANSWERD'),
                "TA" => $this->view->translate('TIME TOTAL ANSWERD'),
                "TN" => $this->view->translate('TIME TOTAL UNANSWERD')
            );

            $result = array(
                "data" => $resultRank,
                "cols" => $titulo
            );
        } else {
            $result = array(
                "timeTotal" => $timeTotal,
                "countTotal" => $countTotal,
                "rank" => $rank,
                "type" => $rankType
            );
        }

        //print_r($result);
        //exit;

        return $result;
    }

    /**
     * viewAction - View ranking report
     */
    public function viewAction() {

        if ($this->_request->getPost() && !$_POST['campo']) {
            $formData = $this->_request->getParams();
            $reportData = $this->getQuery($formData);
            $_SESSION['formDataRRC'] = $formData;
        } else {
            if ($this->_request->getParam('dashboard')) {
                $id = $this->_request->getParam('dashboard');
                $dashboard = Snep_Dashboard_Manager::get();
                foreach ($dashboard as $dash) {
                    if (is_array($dash) && $dash['id'] == $id) {
                        $_SESSION['formDataRRC'] = $dash['session'];
                    }
                }
            }
            $formData = $_SESSION['formDataRRC'];
            $page = $this->_request->getParam('page');
            $reportData = $this->getQuery($formData);
        }

        if ($reportData) {
            $this->view->title = ($this->view->translate("Call Ranking") ) . " {$formData["period"]["init_day"]} - {$formData["period"]["till_day"]}";
            $this->view->limit = Snep_Limit::get($this->_request);

            $paginatorAdapter = new Zend_Paginator_Adapter_Array($reportData["rank"]);

            $paginator = new Zend_Paginator($paginatorAdapter);
            if (!isset($page)) {
                $paginator->setCurrentPageNumber($this->view->page);
            } else {
                $paginator->setCurrentPageNumber($page);
            }
            $paginator->setItemCountPerPage($this->view->limit);

            $pages = $paginator->getPages();

            $i = 0;
            $data = array();
            foreach ($reportData["rank"] as $key => $value) {
                if ($i >= $pages->firstItemNumber - 1 && $i <= $pages->lastItemNumber - 1)
                    $data[$key] = $value;
                $i++;
            }

            $this->view->pages = $paginator->getPages();
            $this->view->PAGE_URL = $this->getFrontController()->getBaseUrl() . "/" . $this->getRequest()->getControllerName() . "/view/";
            $this->view->rank = $data;
            $this->view->type = $reportData["type"];
            $this->view->timeData = $reportData["timeTotal"];
            $this->view->countData = $reportData["countTotal"];
            $this->_helper->viewRenderer('view');

            $filter = new Snep_Form_Filter(true);
            $filter->setValue($this->view->limit, $this->view->PAGE_URL);
            $this->view->form_filter = $filter;
        } else {
            $this->view->error = $this->view->translate("No records found.");
            $this->view->back = $this->view->translate("Back");
            $this->_helper->viewRenderer('error');
        }
    }

    /**
     * verificaCC - verify if exists module CC and table cc_configuration
     * @return <boolean>
     */
    function verificaCC() {

        if (class_exists("Cc_AgentsInfo") || class_exists("Cc_Statistical")) {

            $db = Zend_Registry::get('db');
            $select = "SHOW TABLES LIKE 'cc_configuration'";
            $stmt = $db->query($select);
            $data = $stmt->fetch();

            if ($data != false) {

                $select = "Select preview from cc_configuration";

                $stmt = $db->query($select);
                $preview = $stmt->fetch();
                $result = $preview['preview'];

                return $result;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * ListaAgentes - Verify if origin or destination is agent
     * @return <array>
     */
    function ListaAgentes() {

        $agentFile = '/etc/asterisk/snep/snep-agents.conf';
        $agents = explode("\n", file_get_contents($agentFile));
        $agentsData = array();

        foreach ($agents as $agent) {
            if (preg_match('/^agent/', $agent)) {
                $info = explode(",", substr($agent, 9));
                $agentsData[] = array('code' => $info[0], 'password' => $info[1], 'name' => $info[2]);
            }
        }
        return $agentsData;
    }

    /**
     * csvAction - Export CSV 
     */
    public function csvAction() {
        if ($_SESSION['formDataRRC']) {
            $formData = $_SESSION['formDataRRC'];
            $reportData = $this->getQuery($formData, true);

            if ($reportData) {
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender();

                $csv = new Snep_Csv();
                $csvData = $csv->generate($reportData['data'], true, $reportData['cols']);

                $dateNow = new Zend_Date();
                $fileName = $this->view->translate('relatorio_ranking_csv_') . $dateNow->toString($this->view->translate(" dd-MM-yyyy_hh'h'mm'm' ")) . '.csv';

                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');

                echo $csvData;
            } else {
                $this->view->error = $this->view->translate("No records found.");
                $this->view->back = $this->view->translate("Back");
                $this->_helper->viewRenderer('error');
            }
        }
    }

    /**
     * pdfAction - Export for PDF
     */
    public function pdfAction() {
        if ($_SESSION['formDataRRC']) {
            $formData = $_SESSION['formDataRRC'];
            $reportData = $this->getQuery($formData, false);

            if ($reportData) {
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender();
                /*
                  $reportData["rank"]
                  $this->view->type = $reportData["type"];
                  $this->view->timeData = $reportData["timeTotal"];
                  $this->view->countData = $reportData["countTotal"];
                 */
                $data = array();
                $pos = 0;
                foreach ($reportData['rank'] as $source => $numCal) {


                    if (!isset($formData['type']))
                        $formData['type'] = '';

                    if (!isset($this->type))
                        $this->type = '';

                    if ($formData['type'] == 'num') {
                        $data[] = array(++$pos . ' - ' . $source . ' - Total: ' . $reportData['countTotal'][$source]);
                    } else {
                        $data[] = array(++$pos . ' - ' . $source . ' - Total: ' . $reportData['timeTotal'][$source]);
                    }
                    $subpos = 0;
                    foreach ($numCal as $caller => $dest) {
                        foreach ($dest as $destiny => $valueDest) {
                            $tmp = array();
                            $tmp[] = ++$subpos;
                            $tmp[] = $destiny;
                            if ($this->type == 'num') {
                                $tmp[] = ($valueDest['QA'] ? $valueDest['QA'] : 0);
                                $tmp[] = ($valueDest['QN'] ? $valueDest['QN'] : 0);
                                $tmp[] = ($valueDest['QT'] ? $valueDest['QT'] : 0);
                                $tmp[] = ($valueDest['TA'] ? $valueDest['TA'] : 0);
                                $tmp[] = ($valueDest['TN'] ? $valueDest['TN'] : 0);
                                $tmp[] = ($valueDest['TT'] ? $valueDest['TT'] : 0);
                            } else {
                                $tmp[] = ($valueDest['TA'] ? $valueDest['TA'] : 0);
                                $tmp[] = ($valueDest['TN'] ? $valueDest['TN'] : 0);
                                $tmp[] = ($valueDest['TT'] ? $valueDest['TT'] : 0);
                                $tmp[] = ($valueDest['QA'] ? $valueDest['QA'] : 0);
                                $tmp[] = ($valueDest['QN'] ? $valueDest['QN'] : 0);
                                $tmp[] = ($valueDest['QT'] ? $valueDest['QT'] : 0);
                            }
                            $data[] = $tmp;
                        }
                    }
                }

                $pdf = new Snep_Pdf();
                $pdf->SetFont('Arial', '', 10);


                $dateNow = new Zend_Date();
                $pdf->table(array(15, 40, 20, 25, 20, 20, 25, 20), array('Order', 'Destination', 'Answered', 'Unanswered', 'Total', 'Answered', 'Unanswered', 'Total'), $data);
                $pdf->Output($this->view->translate('relatorio_ranking_pdf_') . $dateNow->toString($this->view->translate(" dd-MM-yyyy_hh'h'mm'm' ")) . '.pdf', "D");
            }
        }
    }

    /**
     * graphicAction - Graphic report
     */
    public function graphicAction() {
        if ($_SESSION['formDataRRC']) {
            $post = $post = $this->_request->getPost();
            if ($post) {
                $_SESSION['formDataRRC']["period"]["init_day"] = $post['ini'] . " 00:00";
                $_SESSION['formDataRRC']["period"]["till_day"] = $post['fim'] . " 23:59";
            }
            $ini = $_SESSION['formDataRRC']["period"]["init_day"];
            $fim = $_SESSION['formDataRRC']["period"]["till_day"];

            $this->view->form =
                    "<form action='' class='periodo' method='post'>" .
                    "<span>Período: </span>" .
                    "<div><input name='ini' type='text' class='maskDate' value='" . substr($ini, 0, 10) . "'/></div>" .
                    "<span> a </span>" .
                    "<div><input name='fim' type='text' class='maskDate' value='" . substr($fim, 0, 10) . "'/></div>" .
                    "<div><input type='submit' value='" . $this->view->translate('Change') . "'/></div><div class='sep'></div>" .
                    "<span>Exibir em: </span>" .
                    ($this->_request->getParam('bar') ?
                            "<a href='" . $this->getFrontController()->getBaseUrl() . "/ranking-report/graphic' class='type_line'>Linhas</a><div class='sep'></div>" .
                            "<span>Exportar: </span><a href='" . $this->getFrontController()->getBaseUrl() . "/ranking-report/graphic-pdf/bar/true' class='export_pdf'></a>" : "<a href='" . $this->getFrontController()->getBaseUrl() . "/ranking-report/graphic/bar/true' class='type_bar'>Colunas</a><div class='sep'></div>" .
                            "<span>Exportar: </span><a href='" . $this->getFrontController()->getBaseUrl() . "/ranking-report/graphic-pdf/' class='export_pdf'></a>") .
                    "</form>";

            $ini = strtotime(substr($ini, 3, 2) . "/" . substr($ini, 0, 2) . "/" . substr($ini, 6, 4));
            $fim = strtotime(substr($fim, 3, 2) . "/" . substr($fim, 0, 2) . "/" . substr($fim, 6, 4));

            $this->view->bar = $this->_request->getParam('bar');
            $this->view->graphic = $this->getFrontController()->getBaseUrl() . '/ranking-report/get-graphic/ini/' . $ini . '/fim/' . $fim . ($this->_request->getParam('bar') ? "/bar/true" : "");
            $this->view->title = $this->view->translate("Call Ranking");
        }
    }

    /**
     * getGraphicAction - Get graphic
     */
    public function getGraphicAction() {
        if ($_SESSION['formDataRRC']) {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            $grafic = $this->graphic();
            $grafic->Stroke();
        }
    }

    /**
     * graphic - Graphic report
     * @return type
     */
    public function graphic() {
        if ($_SESSION['formDataRRC']) {
            $formData = $_SESSION['formDataRRC'];
            $reportData = $this->getQuery($formData, false);
            if ($reportData) {
                if (!$this->_request->getParam('bar'))
                    $lines = true;
                else
                    $lines = false;
                $data = array();

                $i = 0;

                foreach ($reportData['rank'] as $source => $numCal) {

                    $label[] = $source;
                    $count[] = $reportData['countTotal'][$source];
                    $t = explode(':', $reportData['timeTotal'][$source]);
                    $time[] = $t[0] * 3600 + $t[1] * 60 + $t[2];
                }

                $max = max($time);
                if ($max)
                    $rate = max($count) / $max;
                else
                    $rate = 0;
                if ($reportData["type"] != 'num')
                    foreach ($count as $key => $value)
                        $count[$key] = $value / $rate;
                else
                    foreach ($time as $key => $value)
                        $time[$key] = $value * $rate;
                return Snep_Graphic::getGraphic(array('total', '', $label), array(array('time', 'Tempo', $time), array('count', 'Quantidade', $count)), "", $lines, $reportData["type"] != 'num', 810);
            }
            else {
                return Snep_Graphic::getGraphic(null, null, "", $lines, $reportData["type"] != 'num');
            }
        }
    }

    /**
     * graphicPdfAction - Export graphic for PDF
     */
    public function graphicPdfAction() {
        if ($_SESSION['formDataRRC']) {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            $grafic = $this->graphic();
            $file = "includes/pChart/tmp/graphic" . rand(0, 1000) . ".png";
            $grafic->Render($file);
            $pdf = new Snep_Pdf();
            $pdf->graphic($file);
            $dateNow = new Zend_Date();
            $pdf->Output($this->view->translate('ranking_report_graphic_pdf_') . $dateNow->toString($this->view->translate(" dd-MM-yyyy_hh'h'mm'm' ")) . '.pdf', "D");
            unlink($file);
        }
    }

    /**
     * formatNumberAsPhone - Format number of phone
     * @param <string> $phone
     * @return type
     */
    private function formatNumberAsPhone($phone) {

        if (strlen($phone) == 8)
            return preg_replace("/([0-9]{4})([0-9]{4})/", "$1-$2", $phone);
        elseif (strlen($phone) == 10)
            return preg_replace("/([0-9]{2})([0-9]{4})([0-9]{4})/", "($1) $2-$3", $phone);
        elseif (strlen($phone) == 11 && preg_match("/([0|8|5|3]{4})([0-9]{3})([0-9]{4})/", $phone))
            return preg_replace("/([0|8|5|3]{4})([0-9]{3})([0-9]{4})/", "$1 $2 $3", $phone);
        elseif (strlen($phone) == 11)
            return preg_replace("/([0-9]{3})([0-9]{4})([0-9]{4})/", "($1) $2-$3", $phone);
        elseif (strlen($phone) == 13)
            return preg_replace("/([0-9]{3})([0-9]{2})([0-9]{4})([0-9]{4})/", "$1 ($2) $3-$4", $phone);
        else
            return $phone;
    }

    /**
     * formatSecondsAsTime - Format time 
     * @param <string> $sec
     * @return type
     */
    private function formatSecondsAsTime($sec) {
        $minTime = intval($sec / 60);
        $secTime = sprintf("%02s", intval($sec % 60));
        $hourTime = sprintf("%02s", intval($minTime / 60));
        $restMinTime = sprintf("%02s", intval($minTime % 60));
        return $hourTime . ":" . $restMinTime . ":" . $secTime;
    }

    /**
     * subval_sort
     * @param <array> $a
     * @param type $subkey
     * @return type
     */
    function subval_sort($a, $subkey) {
        
        foreach ($a as $k => $v) {
            $b[$k] = strtolower($v[$subkey]);
        }
        asort($b);
        foreach ($b as $key => $val) {
            $c[] = $a[$key];
        }
        return $c;
    }
    
    /**
     * addDashboardAction - Add item in dashboard
     */
    public function addDashboardAction() {
        if ($_SESSION['formDataRRC'])
            Snep_Dashboard_Manager::add(array(
                'nome' => $this->_request->getParam('nome'),
                'descricao' => $this->_request->getParam('descricao'),
                'icone' => 'report_icon.png',
                'link' => 'ranking-report/view',
                'session' => $_SESSION['formDataRRC']
            ));
        $this->_helper->redirector('index', 'index');
    }

}

