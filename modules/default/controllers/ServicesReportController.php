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
 * Services Report controller.
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2013 OpenS Tecnologia
 */
class ServicesReportController extends Zend_Controller_Action {
    
    /**
     * indexAction - init services report
     */
    public function indexAction() {

        $form = $this->getForm();

        if ($this->_request->getPost()) {
            $formIsValid = $form->isValid($_POST);
            $formData = $this->_request->getParams();

            if ($formIsValid) {

                $_SESSION['formDataSRC'] = $formData;
                $this->_helper->redirector('view');
            }
        }

        $form = str_replace("/-", "<span class='labelcheck'>", $form);
        $form = str_replace("-/", "</span>", $form);

        $this->view->form = $form;
    }
    
    /**
     * getForm - get form if services report
     */
    protected function getForm() {
        
        $form = new Snep_Form();

        // Set form action
        $form->setAction($this->getFrontController()->getBaseUrl() . '/services-report/index');

        $form_xml = new Zend_Config_Xml('./modules/default/forms/services_report.xml');
        $period = new Snep_Form_SubForm($this->view->translate("Period"), $form_xml->period);

        $locale = Snep_Locale::getInstance()->getLocale();

        if ($locale == 'en_US') {
            $now = $now->toString('YYYY-MM-dd HH:mm');
            $data = date('y/m/d');
            $st = $data . " 00:00";
            $ft = $data . " 23:59";
        } else {
            $data = date('d/m/Y');
            $st = $data . " 00:00";
            $ft = $data . " 23:59";
        }

        $initDay = $period->getElement('init_day');
        $initDay->setValue($st);
        //$initDay->addValidator($validatorDate);

        $tillDay = $period->getElement('till_day');
        $tillDay->setValue($ft);
        //$tillDay->addValidator($validatorDate);
        $form->addSubForm($period, "period");

        $exten = new Snep_Form_SubForm($this->view->translate("Extensions"), $form_xml->exten);
        $groupLib = new Snep_GruposRamais();
        $groupsTmp = $groupLib->getAll();

        $groupsData = array();
        foreach ($groupsTmp as $key => $group) {

            switch ($group['name']) {
                case 'administrator':
                    $groupsData[$this->view->translate('Administrators')] = $group['name'];
                    break;
                case 'users':
                    $groupsData[$this->view->translate('Users')] = $group['name'];
                    break;
                case 'all':
                    $groupsData[$this->view->translate('All')] = $group['name'];
                    break;
                default:
                    $groupsData[$group['name']] = $group['name'];
            }
        }

        $selectGroup = $exten->getElement('group_select');
        $selectGroup->addMultiOption(null, '----');

        foreach ($groupsData as $key => $value) {
            $selectGroup->addMultiOption($value, $key);
        }

        $selectGroup->setAttrib('onSelect', "enableField('exten-group_select', 'exten-exten_select');");

        $form->addSubForm($exten, "exten");

        $service = new Snep_Form_SubForm($this->view->translate("Services"), $form_xml->service);

        $form->addSubForm($service, "service");

        $form->getElement('submit')->setLabel($this->view->translate("Show Report"));
        $form->removeElement("cancel");
        return $form;
    }
    
    /**
     * getQuery - set query for select
     * @param <array> $data
     * @param <boolean> $ExportCsv
     * @return string
     * @throws Zend_Exception
     */
    protected function getQuery($data, $ExportCsv = false) {

        $fromDay = $data["period"]["init_day"];
        $tillDay = $data["period"]["till_day"];

        $fromDay = new Zend_Date($fromDay);
        $tillDay = new Zend_Date($tillDay);

        if(!isset($data["exten"]["exten_select"])) $data["exten"]["exten_select"] = 0;
        if(!isset($data["exten"]["group_select"])) $data["exten"]["group_select"] = 0;
        if(!isset($data["service"]["serv_select"])) $data["service"]["serv_select"] = 0;
        if(!isset($data["service"]["stat_select"])) $data["service"]["stat_select"] = 0;
        
        $extenList = $data["exten"]["exten_select"];
        $extenGroup = $data["exten"]["group_select"];
        $services = $data["service"]["serv_select"];
        $state = $data["service"]["stat_select"];


        $srv = '';
        if (count($services) > 0) {
            foreach ($services as $service) {
                $srv .= "'$service',";
            }
            $srv = " AND service IN (" . substr($srv, 0, -1) . ")";
        }

        $extenSrc = $extenDst = $cond = "";

        if ($extenGroup) {
          try{
            $origins = PBX_Usuarios::getByGroup($extenGroup);
            if (count($origins) == 0) {
                throw new Zend_Exception('Group not registered');
            } else {
                foreach ($origins as $ext) {
                    $extenSrc .= "'{$ext->getNumero()}'" . ',';
                }
                $extenSrc = " AND peer in (" . trim($extenSrc, ',') . ") ";
            }
          }catch (Exception $e) {
              $this->view->error = $this->view->translate("No records found.");
              $this->view->back = $this->view->translate("Back");
              $this->_helper->viewRenderer('error');
              return;
           }
        }else if ($extenList) {

            $extenList = explode(";", $extenList);
            $list = '';

            foreach ($extenList as $value) {
                $list .= " '". trim($value) . "'"  . ',';
            }
            $extenSrc = " AND services_log.peer IN (" . substr($list, 0, -1) . ") ";
        }

        $state_cnt = count($state);
        if ($state_cnt == 2) {
            $state = " ";
        } else {
            if ($state[0] == "D") {
                $state = " AND services_log.state = '0' ";
            }
            if ($state[0] == "A") {
                $state = " AND services_log.state = '1' ";
            }
        }

        $dateClause = " ( date >= '{$fromDay->toString('yyyy-MM-dd HH:mm')}'";
        $dateClause.=" AND date <= '{$tillDay->toString('yyyy-MM-dd HH:mm')}') "; //'
        $cond .= " $dateClause ";

        $sql = " SELECT *, date as dated, DATE_FORMAT(date,'%d/%m/%Y %T') as date FROM services_log WHERE ";
        $sql.= $cond . $state;
        $sql.= ( $extenSrc ? $extenSrc : '');
        $sql.= ( $srv ? $srv : '');

        $this->view->order = Snep_Order::get(array('dated', 'peer'), $this->_request);
        $sql.= ' ORDER BY ' . $this->view->order[0] . ' ' . $this->view->order[1];

        $db = Zend_Registry::get('db');
        $stmt = $db->query($sql);
        $dataTmp = $stmt->fetchAll();

        foreach ($dataTmp as $key => $value) {
            if (!$ExportCsv) {

                if ($value['state'] == 1) {
                    $dataTmp[$key]['state'] = $this->view->translate(' - Activated');
                } else {
                    $dataTmp[$key]['state'] = $this->view->translate(' - Deactivated');
                }
            } else {

                if ($value['state'] == 1) {
                    $dataTmp[$key]['state'] = $this->view->translate('Activated');
                } else {
                    $dataTmp[$key]['state'] = $this->view->translate('Deactivated');
                }

                $dataTmp[$key]['status'] = '"' . $value['status'] . '"';
            }
        }
        return $dataTmp;
    }
    
    /**
     * viewAction - View information of services report
     */
    public function viewAction() {


        if ($this->_request->getPost() && !$_POST['campo']) {
            $formData = $this->_request->getParams();
            $reportData = $this->getQuery($formData);
            $_SESSION['formDataSRC'] = $formData;
        } else {
            if ($this->_request->getParam('dashboard')) {
                $id = $this->_request->getParam('dashboard');
                $dashboard = Snep_Dashboard_Manager::get();
                foreach ($dashboard as $dash) {
                    if (is_array($dash) && $dash['id'] == $id) {
                        $_SESSION['formDataSRC'] = $dash['session'];
                    }
                }
            }
            $formData = $_SESSION['formDataSRC'];
            $page = $this->_request->getParam('page');
            $reportData = $this->getQuery($formData);
        }
 
        if ($reportData) {

            $this->view->limit = Snep_Limit::get($this->_request);

            $paginatorAdapter = new Zend_Paginator_Adapter_Array($reportData);
            $paginator = new Zend_Paginator($paginatorAdapter);

            if (!isset($page)) {
                $paginator->setCurrentPageNumber($this->view->page);
            } else {
                $paginator->setCurrentPageNumber($page);
            }
            $paginator->setItemCountPerPage($this->view->limit);

            $this->view->report = $paginator;
            $this->view->title = $this->view->translate("Serviços do Periodo: {$formData["period"]["init_day"]} - {$formData["period"]["till_day"]} ");
            $this->view->pages = $paginator->getPages();
            $this->view->PAGE_URL = $this->getFrontController()->getBaseUrl() . "/" . $this->getRequest()->getControllerName() . "/view/";
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
     * csvAction - Export for CSV
     */
    public function csvAction() {
        if ($_SESSION['formDataSRC']) {
            $formData = $_SESSION['formDataSRC'];
            $reportData = $this->getQuery($formData, true);

            if ($reportData) {
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender();

                $csv = new Snep_Csv();
                $csvData = $csv->generate($reportData, true);

                $dateNow = new Zend_Date();
                $fileName = $this->view->translate('services_report_csv_') . $dateNow->toString($this->view->translate(" dd-MM-yyyy_hh'h'mm'm' ")) . '.csv';

                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');

                echo $csvData;
            }
        }
    }
    
    /**
     * pdfAction - Export for PDF
     */
    public function pdfAction() {
        if ($_SESSION['formDataSRC']) {
            $formData = $_SESSION['formDataSRC'];
            $reportData = $this->getQuery($formData, true);

            if ($reportData) {
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender();

                $pdf = new Snep_Pdf();
                $pdf->SetFont('Arial', '', 10);
                $date = array();
                foreach ($reportData as $row) {
                    $date[] = array($row['peer'], $row['date'], $row['service'] . $row['state'], $row['status']);
                }
                $dateNow = new Zend_Date();
                $pdf->table(array(20, 35, 55, 80), array('Extension', 'Date and Time', 'Service - Activated/Deactivated', 'Service Status'), $date);
                $pdf->Output($this->view->translate('services_report_pdf_') . $dateNow->toString($this->view->translate(" dd-MM-yyyy_hh'h'mm'm' ")) . '.pdf', "D");
            }
        }
    }
    
    /**
     * graphicAction - Graphic of services report
     */
    public function graphicAction() {
        if ($_SESSION['formDataSRC']) {
            $post = $post = $this->_request->getPost();
            if ($post) {
                $_SESSION['formDataSRC']["period"]["init_day"] = $post['ini'] . " 00:00";
                $_SESSION['formDataSRC']["period"]["till_day"] = $post['fim'] . " 23:59";
            }
            $ini = $_SESSION['formDataSRC']["period"]["init_day"];
            $fim = $_SESSION['formDataSRC']["period"]["till_day"];

            $this->view->form =
                    "<form action='' class='periodo' method='post'>" .
                    "<span>Período: </span>" .
                    "<div><input name='ini' type='text' class='maskDate' value='" . substr($ini, 0, 10) . "'/></div>" .
                    "<span> a </span>" .
                    "<div><input name='fim' type='text' class='maskDate' value='" . substr($fim, 0, 10) . "'/></div>" .
                    "<div><input type='submit' value='" . $this->view->translate('Change') . "'/></div><div class='sep'></div>" .
                    "<span>Exibir em: </span>" .
                    ($this->_request->getParam('bar') ?
                            "<a href='" . $this->getFrontController()->getBaseUrl() . "/services-report/graphic' class='type_line'>Linhas</a><div class='sep'></div>" .
                            "<span>Exportar: </span><a href='" . $this->getFrontController()->getBaseUrl() . "/services-report/graphic-pdf/bar/true' class='export_pdf'></a>" : "<a href='" . $this->getFrontController()->getBaseUrl() . "/services-report/graphic/bar/true' class='type_bar'>Colunas</a><div class='sep'></div>" .
                            "<span>Exportar: </span><a href='" . $this->getFrontController()->getBaseUrl() . "/services-report/graphic-pdf/' class='export_pdf'></a>") .
                    "</form>";

            $ini = strtotime(substr($ini, 3, 2) . "/" . substr($ini, 0, 2) . "/" . substr($ini, 6, 4));
            $fim = strtotime(substr($fim, 3, 2) . "/" . substr($fim, 0, 2) . "/" . substr($fim, 6, 4));



            $this->view->bar = $this->_request->getParam('bar');
            $this->view->graphic = $this->getFrontController()->getBaseUrl() . '/services-report/get-graphic/ini/' . $ini . '/fim/' . $fim . ($this->_request->getParam('bar') ? "/bar/true" : "");
            $this->view->title = $this->view->translate("Services Use");
        }
    }
    
    /**
     * graphicPdfAction - Export graphic for PDF
     */
    public function graphicPdfAction() {
        if ($_SESSION['formDataSRC']) {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            $grafic = $this->graphic();
            $file = "includes/pChart/tmp/graphic" . rand(0, 1000) . ".png";
            $grafic->Render($file);
            $pdf = new Snep_Pdf();
            $pdf->graphic($file);
            $dateNow = new Zend_Date();
            $pdf->Output($this->view->translate('services_report_graphic_pdf_') . $dateNow->toString($this->view->translate(" dd-MM-yyyy_hh'h'mm'm' ")) . '.pdf', "D");
            unlink($file);
        }
    }
    
    /**
     * getGraphicAction - Get graphic
     */
    public function getGraphicAction() {
        if ($_SESSION['formDataSRC']) {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            $grafic = $this->graphic();
            $grafic->Stroke();
        }
    }
    
    /**
     * graphic
     * @return <object>
     */
    public function graphic() {
        if ($_SESSION['formDataSRC']) {
            $formData = $_SESSION['formDataSRC'];
            $reportData = $this->getQuery($formData, true);
            if ($reportData) {
                if (!$this->_request->getParam('bar'))
                    $lines = true;
                else 
                    $lines = false;
                $data = array();

                $series = array();
                foreach ($reportData as $row) {
                    $date = strtotime(substr($row['date'], 3, 2) . "/" . substr($row['date'], 0, 2) . "/" . substr($row['date'], 6, 4));
                    if (!$this->_request->getParam('ini') && !$this->_request->getParam('fim') || $date >= $this->_request->getParam('ini') && $date <= $this->_request->getParam('fim')) {
                        if(!isset($data[$date][$row['service'] . " - " . $row['state']])) $data[$date][$row['service'] . " - " . $row['state']] = 0;
                        $data[$date][$row['service'] . " - " . $row['state']]++;
                        $series[$row['service'] . " - " . $row['state']] = array();
                    }
                }

                $label = array();
                $data_ = array();
                foreach ($data as $date => $row) {
                    $label[] = $date;
                    foreach ($series as $key => $value) {
                        if ($row[$key])
                            $data_[$key][] = $row[$key];
                        else
                            $data_[$key][] = 0;
                    }
                }
                $s = array();
                foreach ($series as $key => $value) {
                    $s[] = array($key, $key, $data_[$key]);
                }
            }
            return Snep_Graphic::getGraphic(array('date', '', $label), $s, "", $lines);
        }
    }
    
    /**
     * addDashboardAction - Add item in the dashboard
     */
    public function addDashboardAction() {
        if ($_SESSION['formDataSRC'])
            Snep_Dashboard_Manager::add(array(
                'nome' => $this->_request->getParam('nome'),
                'descricao' => $this->_request->getParam('descricao'),
                'icone' => 'report_icon.png',
                'link' => 'services-report/view',
                'session' => $_SESSION['formDataSRC']
            ));
        $this->_helper->redirector('index', 'index');
    }

}




