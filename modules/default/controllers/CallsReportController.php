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
include ("includes/functions.php");

class CallsReportController extends Zend_Controller_Action {
    
    /**
     * indexAction - Index Calls reports
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Reports"),
                    $this->view->translate("Calls")
        ));


        $form = $this->getForm();

        if ($this->_request->getPost()) {

            if (isset($_POST['calls']['costs_center'])) {
                $tmp = $_POST['calls']['costs_center'];
                $_POST['calls']['costs_center'] = "";
                $formIsValid = $form->isValid($_POST);
                $_POST['calls']['costs_center'] = $tmp;
            } else {
                $_POST['calls']['costs_center'] = "";
                $formIsValid = $form->isValid($_POST);
            }


            if ($formIsValid) {
                $this->createAction();
            }
        }

        $this->view->form = $form;
    }
    
    /**
     * getForm - get form a calls reports
     * @return <object> \Snep_Form
     */
    private function getForm() {

        $db = Zend_Registry::get('db');

        $form = new Snep_Form();
        $form->setAction($this->getFrontController()->getBaseUrl() . '/calls-report/');
        $form->setName('create');

        $form_xml = new Zend_Config_Xml('./modules/default/forms/calls_report.xml');

        // --- Subsection - Periods
        $period = new Snep_Form_SubForm($this->view->translate("Period"), $form_xml->period);

        $locale = Snep_Locale::getInstance()->getLocale();

        if ($locale == 'en_US') {
            $now = $now->toString('YYYY-MM-dd HH:mm');
            $data = date('y/m/d');
            $st = $data . " 00:00:00";
            $ft = $data . " 23:59:59";
        } else {
            $data = date('d/m/Y');
            $st = $data . " 00:00:00";
            $ft = $data . " 23:59:59";
        }
        // --- Subsection -- Destination
        $destination = new Snep_Form_SubForm($this->view->translate("Destination"), $form_xml->destination);
        $others = new Snep_Form_SubForm($this->view->translate("Others"), $form_xml->others);
        $source = new Snep_Form_SubForm($this->view->translate("Source"), $form_xml->source);

        if (isset($_GET['initDay']) != "") {

            $initDay = $period->getElement('initDay');
            $initDay->setValue($_GET['initDay']);
            $finalDay = $period->getElement('finalDay');
            $finalDay->setValue($_GET['finalDay']);

            $selectSrc = $source->getElement('selectSrc');
            $selectSrc->setValue("");
            $groupsrc = $source->getElement('groupSrc');
            $groupsrc->setValue(str_replace("'", "", $_GET['origem']));
            $cc = "T";
            $this->createAction($initDay, $finalDay, $cc);
        } else {

            $initDay = $period->getElement('initDay');
            $initDay->setValue($st);

            $finalDay = $period->getElement('finalDay');
            $finalDay->setValue($ft);

            $dstchannel = $destination->getElement('dstchannel');
        }

        $form->addSubForm($period, "period");

        // Populate groups
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

        // --- Subsection -- Source


        $sourceElement = $source->getElement('selectSrc');
        $sourceElement->addMultiOption(null, '');
        $form->addSubForm($source, "source");
        
        $destinationElement = $destination->getElement('selectDst');
        $destinationElement->addMultiOption(null, '');
        $form->addSubForm($destination, "destination");

        foreach ($groupsData as $key => $value) {
            $sourceElement->addMultiOption($value, $key);
            $destinationElement->addMultiOption($value, $key);
        }
        // --- Subsection - Call duration 
        $duration = new Snep_Form_SubForm($this->view->translate("Call duration"), $form_xml->duration); 
        $form->addSubForm($duration, "duration");
        
        
        // --- Subsection - Calls related options
        $calls = new Snep_Form_SubForm($this->view->translate("Additional filters"), $form_xml->calls);

        // List Cost Centers and populate select
        $select = $db->select()
                ->from(array('cc' => 'ccustos'))
                ->order('codigo');

        $costs = $db->query($select)->fetchAll();
        $costsElement = $calls->getElement('costs_center');

        foreach ($costs as $cost) {
            $costsElement->addMultiOption($cost['codigo'], $cost['tipo'] . ' : ' . $cost['codigo'] . ' - ' . $cost['nome']);
        }

        $calls->getElement('status')->setValue('ALL');
        $calls->getElement('type')->setValue('T');

        $form->addSubForm($calls, "calls");

        // --- Subsection - Other options
        $other = new Snep_Form_SubForm($this->view->translate("Other Options"), $form_xml->others);

        //$other->getElement('graph_type')->setValue('bars');
        $other->getElement('report_type')->setValue('analytical');

        $form->addSubForm($other, "others");

        $form->getElement('submit')->setLabel($this->view->translate("Show Report"));
        $form->removeElement('cancel');


        return $form;
    }
    
    /**
     * createAction - Monta dados do relatório
     * @param <string> $initDay
     * @param <string> $finalDay
     * @param <string> $cc
     * @return type
     */
    public function createAction($initDay, $finalDay, $cc) {
        
        $my_object = new Formata;

        if ($this->_request->getParam('dashboard')) {
            $id = $this->_request->getParam('dashboard');
            $dashboard = Snep_Dashboard_Manager::get();
            foreach ($dashboard as $dash) {
                if (is_array($dash) && $dash['id'] == $id) {
                    $formData = $dash['session'];
                }
            }
        }
        else
            $formData = $this->_request->getParams();
        $_SESSION['formDataCRC'] = $formData;

        $db = Zend_Registry::get('db');
        $config = Zend_Registry::get('config');

        $prefix_inout = $config->ambiente->prefix_inout;
        $dst_exceptions = $config->ambiente->dst_exceptions;
        if (isset($cc) != "") {
            $init_day = $initDay;
            $final_day = $finalDay;
        } else {
            $init_day = $formData['period']['initDay'];
            $final_day = $formData['period']['finalDay'];
        }
        $formated_init_dayy = new Zend_Date($init_day);
        $formated_init_day = $formated_init_dayy->toString('yyyy-MM-dd HH:mm:ss');

        $formated_final_day = new Zend_Date($final_day);
        $formated_final_day = $formated_final_day->toString('yyyy-MM-dd HH:mm:ss');

        $ordernar = $formData['period']['order'];

        $dstchannel = $formData["destination"]["dstchannel"];

        $groupsrc = $formData['source']['selectSrc'];
        if (isset($formData['source']['groupSrc'])) {
            $src = $formData['source']['groupSrc'];
        } else {
            $src = "";
        }
 
        $srctype = "src4";
       

        $groupdst = $formData['destination']['selectDst'];
        if (isset($formData['destination']['groupDst'])) {
            $dst = $formData['destination']['groupDst'];
        } else {
            $dst = "";
        }
        
        $dsttype = "dst4";

        if (isset($formData['calls']['costs_center'])) {
            $contas = $formData['calls']['costs_center'];
        }

        $duration1 = $formData['duration']['duration_init'];
        $duration2 = $formData['duration']['duration_end'];

        if (isset($formData['calls']['status'])) {
            $status = $formData['calls']['status'];
            $status_ans = $status_noa = $status_fai = $status_bus = $status_all = '';

            foreach ($status as $stat) {
                switch ($stat) {
                    case 'ANSWERED':
                        $status_ans = 'ANSWERED';
                        break;
                    case 'NOANSWER':
                        $status_noa = 'NO ANSWER';
                        break;
                    case 'FAILED':
                        $status_fai = 'FAILED';
                        break;
                    case 'BUSY':
                        $status_bus = 'BUSY';
                        break;
                }
            }
        }

        $call_type = $formData['calls']['type'];

        if (isset($cc) != "") {

            $view_files = 1;
            $view_tarif = 1;
        } else {

            $view_files = $formData['others']['show_records'];
            $view_tarif = $formData['others']['charging'];
        }

        $rel_type = $formData['others']['report_type'];

        $this->view->back = $this->view->translate("Back");

        // Default submit
        $acao = 'relatorio';

        if (key_exists('submit_csv', $formData))
            $acao = 'csv';
        else if (key_exists('submit_graph', $formData))
            $acao = 'grafico';

        /* Busca os ramais pertencentes ao grupo de ramal de origem selecionado */
        $ramaissrc = $ramaisdst = "";
        if ($groupsrc) {
            $origens = PBX_Usuarios::getByGroup($groupsrc);

            if (count($origens) == 0) {
                $this->view->error = $this->view->translate("There are no extensions in the selected group.");
                $this->_helper->viewRenderer('error');
            } else {
                $ramalsrc = "";

                foreach ($origens as $ramal) {
                    $num = $ramal->getNumero();
                    if (is_numeric($num)) {
                        $ramalsrc .= $num . ',';
                    }
                }
                if ($ramalsrc)
                    $ramaissrc = " AND src in (" . trim($ramalsrc, ',') . ") ";
            }
        }

        /* Busca os ramais pertencentes ao grupo de ramal de destino selecionado */
        if ($groupdst) {
            $destinos = PBX_Usuarios::getByGroup($groupdst);

            if (count($destinos) == 0) {
                $this->view->error = $this->view->translate("There are no extensions in the selected group.");
                $this->_helper->viewRenderer('error');
            } else {
                $ramaldst = "";
                foreach ($destinos as $ramal) {
                    $num = $ramal->getNumero();
                    if (is_numeric($num)) {
                        $ramaldst .= $num . ',';
                    }
                }
                if ($ramaldst)
                    $ramaisdst = " AND dst in (" . trim($ramaldst, ',') . ") ";
            }
        }

        /* Verificando existencia de vinculos no ramal */
        $name = $_SESSION['name_user'];
        $sql = "SELECT id_peer, id_vinculado FROM permissoes_vinculos WHERE id_peer ='$name'";
        $result = $db->query($sql)->fetchObject();

        $vinculo_table = "";
        $vinculo_where = "";
        if ($result) {
            $vinculo_table = " ,permissoes_vinculos ";
            $vinculo_where = " ( permissoes_vinculos.id_peer='{$result->id_peer}' AND (cdr.src = permissoes_vinculos.id_vinculado OR cdr.dst = permissoes_vinculos.id_vinculado) ) AND ";
        }

        /* Clausula do where: periodos inicial e final                                */
        $dia_inicial = $formated_init_day;
        $dia_final = $formated_final_day;

        $date_clause = " ( calldate >= '$dia_inicial'";
        $date_clause .=" AND calldate <= '$dia_final' )  ";

        $CONDICAO = $date_clause;

        $ORIGENS = '';

        // Clausula do where: Origens
        if ($src !== "") {
            if (strpos($src, ",")) {
                $SRC = '';
                $arrSrc = explode(",", $src);
                foreach ($arrSrc as $srcs) {
                    $SRC .= ' OR src LIKE \'' . $srcs . '\' ';
                }
                $SRC = " AND (" . substr($SRC, 3) . ")";
            } else {
                $CONDICAO = $this->do_field($CONDICAO, $src, substr($srctype, 3), 'src');
            }
        }

        // Clausula do where: Destinos
        if ($dst !== "") {
            if (strpos($dst, ",")) {
                $DST = '';
                $arrDst = explode(",", $dst);
                foreach ($arrDst as $dsts) {
                    $DST .= ' OR dst LIKE \'' . $dsts . '\' ';
                }
                $DST = " AND (" . substr($DST, 3) . ")";
            } else {
                $CONDICAO = $this->do_field($CONDICAO, $dst, substr($dsttype, 3), 'dst');
            }
        }

        if (isset($ORIGENS)) {
            $CONDICAO .= $ORIGENS;
        }
        if (isset($DST)) {
            $CONDICAO .= $DST;
        }
        if (isset($SRC)) {
            if (isset($DST)) {
                $CONDICAO .= " AND " . $SRC = substr($SRC, 4);
            } else {
                $CONDICAO .= $SRC;
            }
        }

        /* Clausula do where: Duracao da Chamada                                      */
        if ($duration1) {
            $CONDICAO .= " AND duration >= $duration1 ";
        } else {
            $CONDICAO .= " AND duration > 0 ";
        }
        if ($duration2) {
            $CONDICAO .= " AND duration <= $duration2 ";
        }


        /* Clausula do where:  Filtro de desccarte                                    */
        $TMP_COND = "";
        $dst_exceptions = explode(";", $dst_exceptions);
        foreach ($dst_exceptions as $valor) {
            $TMP_COND .= " dst != '$valor' ";
            $TMP_COND .= " AND ";
        }
        $CONDICAO .= " AND ( " . substr($TMP_COND, 0, strlen($TMP_COND) - 4) . " ) ";

        /* Clausula do where: // Centro de Custos Selecionado(s)                      */
        if (isset($contas) && count($contas) > 0 && $contas != "") {
            $TMP_COND = "";
            foreach ($contas as $valor) {
                $TMP_COND .= " accountcode like '" . $valor . "%'";
                $TMP_COND .= " OR ";
            }
            $contas = implode(",", $contas);
            if ($TMP_COND != "")
                $CONDICAO .= " AND ( " . substr($TMP_COND, 0, strlen($TMP_COND) - 3) . " ) ";
        }

        /* Clausula do where: Status/Tipo Ligacao                                     */
        if (isset($status_all))
            if (($status_all) || ($status_ans && $status_noa && $status_bus && $status_fai)) {
                $CONDICAO .= "";
            } else {
                if ($status_ans && $status_noa && $status_bus) {
                    $CONDICAO .= " AND ( disposition = '$status_ans' OR disposition = '$status_noa' ";
                    $CONDICAO .= " OR disposition = '$status_bus' ) ";
                } elseif ($status_ans && $status_noa && $status_fai) {
                    $CONDICAO .= " AND ( disposition = '$status_ans' OR disposition = '$status_noa' ";
                    $CONDICAO .= " OR disposition = '$status_fai' ) ";
                } elseif ($status_ans && $status_fai && $status_bus) {
                    $CONDICAO .= " AND ( disposition = '$status_ans' OR disposition = '$status_bus' ";
                } elseif ($status_noa && $status_bus && $status_fai) {
                    $CONDICAO .= " AND ( disposition = '$status_noa' OR disposition = '$status_bus' ";
                    $CONDICAO .= " OR disposition = '$status_fai' ) ";
                } elseif ($status_ans && $status_noa) {
                    $CONDICAO .= " AND ( disposition = '$status_ans' OR disposition = '$status_noa' ) ";
                } elseif ($status_ans && $status_bus) {
                    $CONDICAO .= " AND ( disposition = '$status_ans' OR disposition = '$status_bus' ) ";
                } elseif ($status_ans && $status_fai) {
                    $CONDICAO .= " AND ( disposition = '$status_ans' OR disposition = '$status_fai' ) ";
                } elseif ($status_noa && $status_bus) {
                    $CONDICAO .= " AND ( disposition = '$status_bus' OR disposition = '$status_noa' ) ";
                } elseif ($status_fai && $status_noa) {
                    $CONDICAO .= " AND ( disposition = '$status_fai' OR disposition = '$status_noa' ) ";
                } elseif ($status_bus && $status_fai) {
                    $CONDICAO .= " AND ( disposition = '$status_bus' OR disposition = '$status_fai' ) ";
                } elseif ($status_ans) {
                    $CONDICAO .= " AND ( disposition = '$status_ans' ) ";
                } elseif ($status_noa) {
                    $CONDICAO .= " AND ( disposition = '$status_noa' ) ";
                } elseif ($status_bus) {
                    $CONDICAO .= " AND ( disposition = '$status_bus' ) ";
                } elseif ($status_fai) {
                    $CONDICAO .= " AND ( disposition = '$status_fai' ) ";
                }
            }

        /* Clausula do where: Tipo de Chamada (Originada/Recebida/Outra))             */
        if ($call_type == "S") {                                                      // Chamadas Originadas
            $CONDICAO .= " AND (ccustos.tipo = 'S')";
        } elseif ($call_type == "E") {  // Chamadas Recebidas
            $CONDICAO .= " AND (ccustos.tipo = 'E')";
        } elseif ($call_type == "O") {  // Chamadas Outras
            $CONDICAO .= " AND (ccustos.tipo = 'O')";
        }

        /* Clausula do where: Prefixos de Login/Logout                                */
        if (strlen($prefix_inout) > 3) {
            $COND_PIO = "";
            $array_prefixo = explode(";", $prefix_inout);
            foreach ($array_prefixo as $valor) {
                $par = explode("/", $valor);
                $pio_in = $par[0];
                if (!empty($par[1])) {
                    $pio_out = $par[1];
                }
                $t_pio_in = strlen($pio_in);
                $t_pio_out = strlen($pio_out);
                $COND_PIO .= " substr(dst,1,$t_pio_in) != '$pio_in' ";
                if (!$pio_out == '') {
                    $COND_PIO .= " AND substr(dst,1,$t_pio_out) != '$pio_out' ";
                }
                $COND_PIO .= " AND ";
            }
            if ($COND_PIO != "")
                $CONDICAO .= " AND ( " . substr($COND_PIO, 0, strlen($COND_PIO) - 4) . " ) ";
        }
        $CONDICAO .= " AND ( locate('ZOMBIE',channel) = 0 ) ";

        /* Montagem do SELECT de Consulta */
        $SELECT = "ccustos.codigo,ccustos.tipo,ccustos.nome, date_format(calldate,\"%d/%m/%Y\") AS key_dia, date_format(calldate,\"%d/%m/%Y %H:%i:%s\") AS dia, src, dst, disposition, duration, billsec, accountcode, userfield, dcontext, amaflags, uniqueid, calldate, dstchannel ";
        $tot_tarifado = 0;

        /* Consulta de sql para verificar quantidade de registros selecionados e
          Montar lista de Totais por tipo de Status */
        try {
            unset($duration, $billsec);
            $sql_ctds = "SELECT " . $SELECT . " FROM cdr, ccustos $vinculo_table ";
            $sql_ctds .= " WHERE accountcode !='' AND (cdr.accountcode = ccustos.codigo) AND $vinculo_where " . $CONDICAO;
            $sql_ctds .= ( $ramaissrc === null ? '' : $ramaissrc) . ($ramaisdst === null ? '' : $ramaisdst);


            //Busca Por agente na fila para contagem
            $arrDst = " AND dstchannel like '%";
            if ($dstchannel != "") {
                if (strpos($dstchannel, ",")) {

                    $arrDst .= str_replace(",", "%' or dstchannel like '%", $dstchannel);
                    $arrDst.="%' And";
                } else {
                    $arrDst.= $dstchannel . "%' ";
                }

                $sql_ctds .=$arrDst;
            }
            $sql_ctds .= " GROUP BY userfield ORDER BY calldate, userfield";
            if ($acao == "grafico") {
                $tot_fai = $tot_bus = $tot_ans = $tot_noa = $tot_oth = array();
            } else {
                $tot_fai = $tot_bus = $tot_ans = $tot_noa = $tot_bil = $tot_dur = $tot_oth = 0;
            }

            $flag_ini = True; // Flag para controle do 1o. registro lido
            $userfield = "XXXXXXX";  // Flag para controle do Userfield
            unset($result);


            foreach ($db->query($sql_ctds) as $row) {

                /* Incializa array se tipo = grafico                                   */
                $key_dia = $row['key_dia'];

                if ($acao == "grafico") {
                    $tot_dias[$key_dia] = $key_dia;
                    $tot_ans[$key_dia] = (!array_key_exists($key_dia, $tot_ans)) ? 0 : $tot_ans[$key_dia];
                    $tot_noa[$key_dia] = (!array_key_exists($key_dia, $tot_noa)) ? 0 : $tot_noa[$key_dia];
                    $tot_bus[$key_dia] = (!array_key_exists($key_dia, $tot_bus)) ? 0 : $tot_bus[$key_dia];
                    $tot_fai[$key_dia] = (!array_key_exists($key_dia, $tot_fai)) ? 0 : $tot_fai[$key_dia];
                    $tot_oth[$key_dia] = (!array_key_exists($key_dia, $tot_oth)) ? 0 : $tot_oth[$key_dia];
                }

                /*  Faz verificacoes para contabilizar valores dentro do mesmo userfield
                  So vai contabilziar resultados por userfield */
                if ($userfield != $row['userfield']) {
                    if ($flag_ini) {
                        $result[$row['uniqueid']] = $row;
                        $userfield = $row['userfield'];
                        $flag_ini = False;
                        continue;
                    }
                } else {
                    $result[$row['uniqueid']] = $row;
                    continue;
                }
                if ($row['uniqueid'] == '') {
                    continue;
                }


                /* Varre o array da chamada com mesmo userfield                        */
                foreach ($result as $val) {
                    switch ($val['disposition']) {
                        case "ANSWERED":
                            if ($acao == 'grafico')
                                $tot_ans[$key_dia]++;
                            else
                                $tot_ans++;

                            $tot_bil += $val['billsec'];
                            $tot_dur += $val['duration'];
                            if ($view_tarif) {
                                $valor = money_format('%.2n', $my_object->fmt_tarifa(
                                                array("a" => $val['dst'],
                                            "b" => $val['billsec'],
                                            "c" => $val['accountcode'],
                                            "d" => $val['calldate']), "A")
                                );
                                $tot_tarifado += $valor;
                            }
                            break;
                        case "NO ANSWER":
                            if ($acao == 'grafico') {
                                $tot_noa[$key_dia]++;
                            } else {
                                $tot_noa++;
                            }
                            break;
                        case "BUSY" :
                            if ($acao == 'grafico') {
                                $tot_bus[$key_dia]++;
                            } else {
                                $tot_bus++;
                            }
                            break;
                        case "FAILED" :
                            if ($acao == 'grafico') {
                                $tot_fai[$key_dia]++;
                            } else {
                                $tot_fai++;
                            }
                            break;
                        default :
                            if ($acao == 'grafico') {
                                $tot_oth[$key_dia]++;
                            } else {
                                $tot_oth++;
                            }
                            break;
                    } // Fim do Switch
                }
                // Fim do Foreach do array "result"
                unset($result);
                $result[$row['uniqueid']] = $row;
                $userfield = $row['userfield'];
            }

            /* Switch a seguir é para pegar um possível último registro               */
            if (isset($result)) {
                foreach ($result as $val) {
                    switch ($val['disposition']) {
                        case "ANSWERED":
                            if ($acao == 'grafico') {
                                $tot_ans[$key_dia]++;
                            } else {
                                $tot_ans++;
                                $tot_bil += $val['billsec'];
                                $tot_dur += $val['duration'];
                                if ($view_tarif) {
                                    $valor = money_format('%.2n', $my_object->fmt_tarifa(
                                                    array("a" => $val['dst'],
                                                "b" => $val['billsec'],
                                                "c" => $val['accountcode'],
                                                "d" => $val['calldate']), "A")
                                    );
                                    $tot_tarifado += $valor;
                                }
                            }
                            break;
                        case "NO ANSWER":
                            if ($acao == 'grafico') {
                                $tot_noa[$key_dia]++;
                            } else {
                                $tot_noa++;
                            }
                            break;
                        case "BUSY" :
                            if ($acao == 'grafico') {
                                $tot_bus[$key_dia]++;
                            } else {
                                $tot_bus++;
                            }
                            break;
                        case "FAILED" :
                            if ($acao == 'grafico') {
                                $tot_fai[$key_dia]++;
                            } else {
                                $tot_fai++;
                            }
                            break;
                        default :
                            if ($acao == 'grafico') {
                                $tot_oth[$key_dia]++;
                            } else {
                                $tot_oth++;
                            }
                            break;
                    } // Fim do Switch
                }
            }
            // Fim do Foreach do array result para possivel ultimo registro
        } catch (Exception $e) {
            $this->view->error = $this->view->translate("Error");
            $this->_helper->viewRenderer('error');
        }

        if ($acao == "relatorio") {
            if (($tot_fai + $tot_bus + $tot_ans + $tot_noa) == 0) {
                $this->view->error = $this->view->translate("No entries found!.");
                $this->_helper->viewRenderer('error');
            }
            $tot_wait = $tot_dur - $tot_bil;
            $totais = array("answered" => $tot_ans,
                "notanswer" => $tot_noa,
                "busy" => $tot_bus,
                "fail" => $tot_fai,
                "billsec" => $tot_bil,
                "duration" => $tot_dur,
                "espera" => $tot_wait,
                "oth" => $tot_oth,
                "tot_tarifado" => $tot_tarifado);
            // "tot_tarifado"=>number_format($tot_tarifado,2,",","."));
        } else {

            if (count($tot_fai) == 0 && count($tot_bus) == 0 &&
                    count($tot_ans) == 0 && count($tot_noa) == 0 &&
                    count($tot_oth) == 0) {
                $this->view->error = $this->view->translate("No entries found!");
                $this->_helper->viewRenderer('error');
                return;
            }

            if ($acao != "grafico") {
                $totais = array("ans" => $tot_ans, "noa" => $tot_noa,
                    "bus" => $tot_bus, "fai" => $tot_fai,
                    "dias" => $tot_dias, "dur" => $tot_dur,
                    "bil" => $tot_bil);
            } else {
                $totais = array();
            }
        }

        /* Define um SQL de Exibicao no Template, agrupado e com ctdor de agrupamentos */
        $sql_chamadas = "SELECT count(userfield) as qtdade," . $SELECT . "FROM cdr, ccustos $vinculo_table ";
        $sql_chamadas .= " WHERE accountcode !='' AND (cdr.accountcode = ccustos.codigo) AND  ";
        $sql_chamadas .= " $vinculo_where " . $CONDICAO;
        $sql_chamadas .= ( $ramaissrc == null ? '' : $ramaissrc) . ($ramaisdst === null ? '' : $ramaisdst);

        switch ($ordernar) {
            case "data":
                $ordernar = " calldate ";
                break;
            case "src":
                $ordernar = " src, calldate ";
                break;
            case "dst":
                $ordernar = "  dst, calldate ";
                break;
            default :
                $ordernar = " calldate ";
                break;
        }

        $arrDst = " AND dstchannel like '%";
        if ($dstchannel != "") {
            if (strpos($dstchannel, ",")) {

                $arrDst .= str_replace(",", "%' or dstchannel like '%", $dstchannel);
                $arrDst.="%' And";
            } else {
                $arrDst.= $dstchannel . "%' ";
            }

            $sql_chamadas .=$arrDst;
        }
        $sql_chamadas .= " GROUP BY userfield ORDER BY $ordernar ";
        $defaultNS = new Zend_Session_Namespace('call_sql');
        $defaultNS->sql = $sql_chamadas;

        $defaultNS->totais = $totais;
        $defaultNS->view_tarif = $view_tarif;
        $defaultNS->view_files = $view_files;
        if (isset($status))
            $defaultNS->status = $status;
        if (isset($contas)) {
            $defaultNS->contas = $contas;
        }
        $defaultNS->report_type = $rel_type;

        $defaultNS->src = $src;
        $defaultNS->groupsrc = $groupsrc;

        $defaultNS->dst = $dst;
        $defaultNS->groupdst = $groupdst;

        $defaultNS->sub_title = $this->view->translate($formData['period']['initDay'] . " - " . $formData['period']['finalDay']);

        $row = $db->query($sql_chamadas)->fetchAll();

        for ($i = 0; $i <= count($row) - 1; $i++) {
            $row[$i]['id'] = $i + 1;
        }

        $defaultNS->row = $row;

        if (count($defaultNS->row) == 0) {
            $this->view->error = $this->view->translate("No entries found!");
            $this->_helper->viewRenderer('error');
            return;
        }

        $this->_helper->redirector('report');
    }
    
    /**
     * do_field - 
     * @param <string> $sql
     * @param <string> $fld
     * @param <string> $fldtype
     * @param <string> $nmfld
     * @param <string> $tpcomp
     * @return <string>
     */
    public function do_field($sql, $fld, $fldtype, $nmfld = "", $tpcomp = "AND") {
        if (isset($fld) && ($fld != '')) {
            $sql = "$sql $tpcomp";

            if ($nmfld == "") {
                $sql = "$sql $fld";
            } else {
                $sql = "$sql $nmfld";
            }

            if (isset($fldtype)) {
                switch ($fldtype) {
                    case 1:
                        $sql = "$sql='" . $fld . "'";
                        break;
                    case 2:
                        $sql = "$sql LIKE '" . $fld . "%'";
                        break;
                    case 3:
                        $sql = "$sql LIKE '%" . $fld . "'";
                        break;
                    case 4:
                        $sql = "$sql LIKE '%" . $fld . "%'";
                        break;
                }
            } else {
                $sql = "$sql LIKE '%" . $fld . "%'";
            }
        }
        return $sql;
    }
    
    /**
     * reportAction - Monta dados do relatório
     * @return type
     */
    public function reportAction() {
        $db = Zend_Registry::get('db');
        $format = new Formata;

        // View labels
        $this->view->seq = $this->view->translate("SEQ");
        $this->view->calldate = $this->view->translate("Call's date");
        $this->view->origin = $this->view->translate("Source");
        $this->view->destination = $this->view->translate("Destination");
        $this->view->operator = $this->view->translate("Operator");
        $this->view->callstatus = $this->view->translate("Status");
        $this->view->duration = $this->view->translate("Duration");
        $this->view->conversation = $this->view->translate("Conversation");
        $this->view->cost_center = $this->view->translate("Cost Center");
        $this->view->city = $this->view->translate("City");
        $this->view->state = $this->view->translate("State");

        $this->view->filter = $this->view->translate("Filter");
        $this->view->calls = $this->view->translate("Calls");
        $this->view->totals_sub = $this->view->translate("Totals");
        $this->view->times = $this->view->translate("Times");
        $this->view->tot_tariffed = $this->view->translate("Total tariffed");

        $this->view->answered = $this->view->translate("Answered");
        $this->view->nanswered = $this->view->translate("Not Answered");
        $this->view->busy = $this->view->translate("Busy");
        $this->view->failure = $this->view->translate("Failed");
        $this->view->other = $this->view->translate("Other");

        $this->view->tarrifation = $this->view->translate("Charging");
        $this->view->wait = $this->view->translate("Waiting");
        $this->view->sub_total = $this->view->translate("Subtotal");
        $this->view->gravation = $this->view->translate("Records");

        $this->view->back = $this->view->translate("Back");

        $defaultNS = new Zend_Session_Namespace('call_sql');

        $this->view->title = $this->view->translate("Calls") . " - " . $defaultNS->sub_title;


        $this->view->totals = $defaultNS->totais;

        
        $this->view->tariffed = $defaultNS->view_tarif;

        $this->view->files = $defaultNS->view_files;

        $this->view->status = $defaultNS->status;
        $this->view->compress_files = $this->view->translate("Compress selected files");

        $this->view->duration_call = $format->fmt_segundos(
                array("a" => $defaultNS->totais['duration'], "b" => 'hms')
        );
        $this->view->bill_sec = $format->fmt_segundos(
                array("a" => $defaultNS->totais['billsec'], "b" => 'hms')
        );
        $this->view->wait_call = $format->fmt_segundos(
                array("a" => $defaultNS->totais['espera'], "b" => 'hms')
        );

        $row = $defaultNS->row;

        if ($defaultNS->report_type == 'synth') {

            // Cost center treatment
            $cc = $defaultNS->contas;
            $sql_CC = null;
            $sql = explode(",", $cc);
            foreach ($sql as $val) {
                if ($sql_CC != null)
                    $sql_CC .= ",";
                $sql_CC .= "'" . $val . "'";
            }

            if ($cc != '') {
                $sqlcc = "select nome from ccustos where codigo IN (" . $sql_CC . ")";
                $ccs = $db->query($sqlcc)->fetchAll(PDO::FETCH_ASSOC);
                $ccusto_sintetic = '';
                foreach ($ccs as $id => $value) {
                    $ccusto_sintetic .= $ccs[$id]['nome'] . ", ";
                }
            } else {
                $ccusto_sintetic = $this->view->translate("Any");
            }

            $this->view->cost_center_res = $ccusto_sintetic;

            // Groups treatment 
            $sint_destino = $defaultNS->dst;
            $sint_groupdst = $defaultNS->groupdst;

            if ($sint_destino != '' && $sint_groupdst == '') {
                $sint_dest = $sint_destino;
            }

            if ($sint_groupdst != '' && $sint_destino == '') {
                $sqldst = "select name from peers where peers.group = '$sint_groupdst' ";
                $sint_dst = $db->query($sqldst)->fetchAll(PDO::FETCH_ASSOC);
                $sint_dest = '';
                foreach ($sint_dst as $id => $value) {
                    $sint_dest .= $sint_dst[$id]['name'] . ", ";
                }
            }

            if ($sint_dest == '')
                $sint_dest = "Todos";

            if (!empty($sint_dest)) {
                $this->view->sinteticdst = $sint_dest;
            }

            $sint_origem = $defaultNS->src;
            $sint_groupsrc = $defaultNS->groupsrc;

            if ($sint_origem != '' && $sint_groupsrc == '') {
                $src_sintetic = trim($sint_origem);
            }
            if ($sint_groupsrc != '' && $sint_origem == '') {
                $sqlsrc = "select name from peers where peers.group = '$sint_groupsrc' ";
                $sint_src = $db->query($sqlsrc)->fetchAll(PDO::FETCH_ASSOC);
                $src_sintetic = '';
                foreach ($sint_src as $id => $value) {
                    $src_sintetic .= $sint_src[$id]['name'] . ", ";
                }
            }

            if ($src_sintetic == '')
                $src_sintetic = "Todos";

            if (!empty($src_sintetic)) {
                $this->view->sinteticsrc = $src_sintetic;
            }

            $this->renderScript('calls-report/synthetic-report.phtml');
        } else {

            // Analytical Report

            $this->view->limit = Snep_Limit::get($this->_request);

            $paginatorAdapter = new Zend_Paginator_Adapter_Array($row);
            $paginator = new Zend_Paginator($paginatorAdapter);

            $paginator->setCurrentPageNumber($this->_request->page);
            $paginator->setItemCountPerPage($this->view->limit);

            $items = $paginator->getCurrentItems();

            $this->view->pages = $paginator->getPages();

            $filter = new Snep_Form_Filter(true);
            $filter->setValue($this->view->limit, $this->view->PAGE_URL);
            $this->view->form_filter = $filter;

            $this->view->PAGE_URL = $this->getFrontController()->getBaseUrl() . "/calls-report/report/";

            $listItems = $this->format($items);

            $this->view->call_list = $listItems;
            $this->view->compact_success = $this->view->translate("The files were compressed successfully! Wait for the download start.");
            $this->renderScript('calls-report/analytical-report.phtml');
        }
        return;
    }
    
    /**
     * format - formata dados
     * @param <object> $items
     * @param <boolean> $formatseconds
     * @return array
     */
    private function format($items, $formatseconds = true) {
                
        $listItems = array();
        $format = new Formata;
        foreach ($items as $item) {

            // Status
            switch ($item['disposition']) {
                case 'ANSWERED':
                    $item['disposition'] = $this->view->translate('Answered');
                    break;
                case 'NO ANSWER':
                    $item['disposition'] = $this->view->translate('Not Answered');
                    break;
                case 'FAILED':
                    $item['disposition'] = $this->view->translate('Failed');
                    break;
                case 'BUSY':
                    $item['disposition'] = $this->view->translate('Busy');
                    break;
                case 'OTHER':
                    $item['disposition'] = $this->view->translate('Others');
                    break;
            }

            // Search for a city or format the telephone type
            if (strlen($item['dst']) > 7 ) {
                $item['city'] = $this->telType($item['dst']);
            } elseif (is_numeric($item['src']) &&  strlen($item['src']) > 7 ) {
                $item['city'] = $this->telType($item['src']);
            } else {
                $item['city'] = $this->telType($item['dst']);
            }

            $item['nome'] = $item['tipo'] . " : " . $item['codigo'] . " - " . $item['nome'];


            if (isset($this->view->tariffed)) {
                if ($item['tipo'] === "S") {
                    $item['rate'] = $format->fmt_tarifa(array("a" => $item['dst'],
                        "b" => $item['billsec'],
                        "c" => $item['accountcode'],
                        "d" => $item['calldate'],
                        "e" => $item['tipo']));
                } elseif ($item['tipo'] === "E") {
                    $item['rate'] = $format->fmt_tarifa(array("a" => $item['src'],
                        "b" => $item['billsec'],
                        "c" => $item['accountcode'],
                        "d" => $item['calldate'],
                        "e" => $item['tipo']));
                } else {
                    $item['rate'] = 0;
                }
            }

            
            $item['src'] = $format->fmt_telefone(array("a" => $item['src']));
            $item['dst'] = $format->fmt_telefone(array("a" => $item['dst']));

            if ($formatseconds) {
                $item['billsec'] = $format->fmt_segundos(array("a" => $item['billsec'], "b" => 'hms'));
                $item['duration'] = $format->fmt_segundos(array("a" => $item['duration'], "b" => 'hms'));
            }

            if ($this->view->files != '0') {

                $filePath = Snep_Manutencao::arquivoExiste($item['calldate'], $item['userfield']);
                $item['file_name'] = $item['userfield'] . ".wav";

                if ($filePath) {
                    $item['file_path'] = $filePath;
                    $item['file_name'] = $filePath;
                } else {
                    $item['file_path'] = 'N.D.';
                }
            }
            else
                $item['file_path'] = 'N.D.';



            //verifica se existe o módulo cc e tabela cc_configuration no banco
            $configuration = self::verificaCC();

            if ($configuration != false) {

                $listaAgentes = self::ListaAgentes();
                
                //verifica se origem,destino e operador são agentes para renomear
                $srcAgente = $item["src"];
                $dstAgente = $item["dst"];
                $dstchannelAgente = explode("@", $item['dstchannel']);
                $dstchannelAgente = explode("/", $dstchannelAgente[0]);
                $dstchannelAgente = $dstchannelAgente[1];
                                
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

                    if ($agentes["code"] == $dstchannelAgente) {
                        $item["operador"] = 1;
                        $validaOperador = 1;
                        if ($configuration == "ambos") {
                            $dstchannelAgente = $agentes["code"] . " - (" . rtrim($agentes["name"]) . ")";
                        } else if ($configuration == "name") {
                            $dstchannelAgente = rtrim($agentes["name"]);
                        } else {
                            $dstchannelAgente = $agentes["code"];
                        }
                    }
                }

                $item["src"] = $srcAgente;
                $item["dst"] = $dstAgente;
                
                if (isset($validaOperador)) {
                    $item["dstchannel"] = $dstchannelAgente;
                }
            }

            array_push($listItems, $item);
        }

        return $listItems;
    }

    /**
     * verificaCC - verifica se existe módulo cc e tabela cc_configuration
     * @global type $db
     * @return boolean
     */
    function verificaCC() {

        global $db;

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
     * ListaAgentes - Verifica se a origem ou destino é agente
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
     * telType - Busca localidade pelo telefone
     * @param <string> $telefone
     * @return <string> $cidade
     */
    private function telType($phone) {
        $my_object = new Formata;
        try {
            $cidade = $my_object->fmt_cidade(array("a"=>$phone),"");
        } catch (Exception $e) {
            $cidade = $e;
        }
        return $cidade;
    }
    
    /**
     * compactAction - Compact arquivos de gravação
     */
    public function compactAction() {

        $config = Zend_Registry::get('config');
        $this->_helper->layout->disableLayout();

        $path = $config->ambiente->path_voz;

        $zip = new ZipArchive();
        $files = $this->_request->getParam('files');
        $fileName = date("d-m-Y-h-i") . ".zip";

        $caminho = explode("snep", $path);
        $caminho = $caminho[0];

        $zip->open($path . $fileName, ZipArchive::CREATE);

        $arrFiles = explode(',', $files);

        foreach ($arrFiles as $file) {

            $file = $caminho . $file;

            $zip->addFile($file, $file);
        }
        $zip->close();
        $this->view->path = '/snep/arquivos/' . $fileName;
    }
    
    /**
     * csvAction - Export CSV
     */
    public function csvAction() {
        $defaultNS = new Zend_Session_Namespace('call_sql');

        if ($_SESSION['formDataCRC'] && $defaultNS->row) {

            $reportData = $this->format($defaultNS->row);

            if ($reportData) {
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender();

                foreach ($reportData as $value) {
                    $item[] = $value['qtdade'];
                    $item[] = $value['dia'];
                    $item[] = $value['src'];
                    $item[] = $value['dst'];
                    if (preg_match("/Local/i", $value['dstchannel'])) {
                        $item[] = strTok(str_replace('Local/', '', $value['dstchannel']), "@");
                    } else if ($value['operador'] == 1) {
                        $item[] = $value['dstchannel'];
                    } else {
                        $item[] = "";
                    }
                    $item[] = $value['disposition'];
                    $item[] = $value['billsec'];
                    $item[] = $value['nome'];
                    $item[] = $value['city'];
                    $data[] = $item;
                    unset($item);
                }

                $csv = new Snep_Csv();
                $header = array("SEQ", "Data das ligações", "Origem", "Destino", "Operador", "Status", "Conversação", "Centro de Custos", "Cidade");
                $csvData = $csv->generate($data, $header);

                $dateNow = new Zend_Date();
                $fileName = $this->view->translate('calls_report_csv_') . $dateNow->toString($this->view->translate(" dd-MM-yyyy_hh'h'mm'm' ")) . '.csv';

                header('Content-type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');

                echo $csvData;
            }
        }
    }
    
    /**
     * pdfAction - Exporta para PDF
     */
    public function pdfAction() {

        $defaultNS = new Zend_Session_Namespace('call_sql');

        if ($_SESSION['formDataCRC'] && $defaultNS->row) {

            $reportData = $this->format($defaultNS->row);

            if ($reportData) {
                $this->_helper->layout->disableLayout();
                $this->_helper->viewRenderer->setNoRender();

                $label = array(
                    $this->view->translate("SEQ"),
                    $this->view->translate("Call's date"),
                    $this->view->translate("Source"),
                    $this->view->translate("Destination"),
                    $this->view->translate("Operator"),
                    $this->view->translate("Status"),
                    $this->view->translate("Duration"),
                    $this->view->translate("Conversation"),
                    $this->view->translate("Cost Center"),
                    $this->view->translate("Local"));

                if ($defaultNS->view_tarif) {
                    $w = array(10, 35, 35, 40, 20, 25, 25, 50, 20, 20);
                    $label[] = $this->view->translate("Charging");
                } else {
                    $w = array(10, 35, 35, 40, 20, 25, 25, 25, 50, 20);
                }

                $pdf = new Snep_Pdf();
                $pdf->SetFont('Arial', '', 10);
                foreach ($reportData as $row) {
                    if (preg_match("/Local/i", $row['dstchannel'])) {
                        $row['dstchannel'] = strTok(str_replace('Local/', '', $row['dstchannel']), "@");
                    } else if ($row['operador'] == 1) {
                        $row['dstchannel'] = $row['dstchannel'];
                        
                    } else {
                        $row['dstchannel'] = "";
                    }
                    $ndate = array(
                        $row['id'],
                        $row['dia'],
                        $row['src'],
                        $row['dst'],
                        $row['dstchannel'],
                        $row['disposition'],
                        $row['duration'],
                        $row['billsec'],
                        $row['nome'],
                        $row['city']);

                    if ($defaultNS->view_tarif) {
                        if (isset($row['rate'])) {
                            $ndate[] = $row['rate'];
                        } else {
                            $row['rate'] = "-";
                            $ndate[] = $row['rate'];
                        }
                    }
                    $date[] = $ndate;
                }

                $dateNow = new Zend_Date();
                $pdf->table($w, $label, $date, 'L', 28);

                $pdf->Output($this->view->translate('calls_report_pdf_') . $dateNow->toString($this->view->translate(" dd-MM-yyyy_hh'h'mm'm' ")) . '.pdf', "D");
            }
        }
    }
    
    /**
     * graphicAction - Monta gráfico do relatório
     */
    public function graphicAction() {
        $defaultNS = new Zend_Session_Namespace('call_sql');

        if ($_SESSION['formDataCRC'] && $defaultNS->row) {

            $this->view->tarif = (bool) $_SESSION['formDataCRC']['others']['charging'];

            $post = $this->_request->getPost();

            if ($post) {
                $_SESSION['formDataCRC']["period"]["initDay"] = $post['ini'] . " 00:00";
                $_SESSION['formDataCRC']["period"]["finalDay"] = $post['fim'] . " 23:59";
            }

            $ini = $_SESSION['formDataCRC']["period"]["initDay"];
            $fim = $_SESSION['formDataCRC']["period"]["finalDay"];

            $this->view->form =
                    "<form action='' class='periodo' method='post'>" .
                    "<span>Período: </span>" .
                    "<div><input name='ini' type='text' class='maskDate' value='" . substr($ini, 0, 10) . "'/></div>" .
                    "<span> a </span>" .
                    "<div><input name='fim' type='text' class='maskDate' value='" . substr($fim, 0, 10) . "'/></div>" .
                    "<div><input type='submit' value='" . $this->view->translate('Change') . "'/></div><div class='sep'></div>" .
                    "<span>Exibir em: </span>" .
                    ($this->_request->getParam('bar') ?
                            "<a href='" . $this->getFrontController()->getBaseUrl() . "/calls-report/graphic' class='type_line'>Linhas</a><div class='sep'></div>" .
                            "<span>Exportar: </span><a href='" . $this->getFrontController()->getBaseUrl() . "/calls-report/graphic-pdf/bar/true' class='export_pdf'></a>" : "<a href='" . $this->getFrontController()->getBaseUrl() . "/calls-report/graphic/bar/true' class='type_bar'>Colunas</a><div class='sep'></div>" .
                            "<span>Exportar: </span><a href='" . $this->getFrontController()->getBaseUrl() . "/calls-report/graphic-pdf/' class='export_pdf'></a>") .
                    "</form>";

            $this->view->bar = $this->_request->getParam('bar');
            $this->view->graphic = $this->getFrontController()->getBaseUrl() . '/calls-report/get-graphic' . ($this->_request->getParam('bar') ? "/bar/true" : "");
            $this->view->title = $this->view->translate("Calls");
            $this->graphicData(strtotime(substr($ini, 3, 2) . "/" . substr($ini, 0, 2) . "/" . substr($ini, 6, 4)), strtotime(substr($fim, 3, 2) . "/" . substr($fim, 0, 2) . "/" . substr($fim, 6, 4)));
        }
    }
    
    /**
     * graphicPdfAction - Monta PDF do gráfico
     */
    public function graphicPdfAction() {
        $defaultNS = new Zend_Session_Namespace('call_sql');

        if ($_SESSION['formDataCRC'] && $defaultNS->row) {
            //$this->graphicData();
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender();

            $grafic = $this->graphic('status');
            $file[0] = "includes/pChart/tmp/graphic" . rand(0, 1000) . time() . ".png";
            $grafic->Render($file[0]);

            $grafic = $this->graphic('center');
            $file[01] = "includes/pChart/tmp/graphic" . rand(0, 1000) . time() . ".png";
            $grafic->Render($file[1]);

            $grafic = $this->graphic('seconds');
            $file[2] = "includes/pChart/tmp/graphic" . rand(0, 1000) . time() . ".png";
            $grafic->Render($file[2]);

            if ($_SESSION['formDataCRC']['others']['charging']) {
                $grafic = $this->graphic('status');
                $file[3] = "includes/pChart/tmp/graphic" . rand(0, 1000) . time() . ".png";
                $grafic->Render($file[3]);
            }

            $pdf = new Snep_Pdf();
            $pdf->graphic($file);
            $dateNow = new Zend_Date();
            $pdf->Output($this->view->translate('calls_report_graphic_pdf_') . $dateNow->toString($this->view->translate(" dd-MM-yyyy_hh'h'mm'm' ")) . '.pdf', "D");

            unlink($file[0]);
            unlink($file[1]);
            unlink($file[2]);
            if ($_SESSION['formDataCRC']['others']['charging'])
                unlink($file[3]);
        }
    }
    
    /**
     * getGraphicAction - Monta gráfico dos dados
     */
    public function getGraphicAction() {
        $defaultNS = new Zend_Session_Namespace('call_sql');
        if ($_SESSION['formDataCRC'] && $defaultNS->row) {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            $grafic = $this->graphic();
            $grafic->Stroke();
        }
    }
    
    /**
     * graphicData - Monta dados para o gráfico
     * @param <string> $ini
     * @param <string> $fim
     */
    public function graphicData($ini = NULL, $fim = NULL) {
        $defaultNS = new Zend_Session_Namespace('call_sql');

        if ($_SESSION['formDataCRC'] && $defaultNS->row) {

            $reportData = $this->format($defaultNS->row, false);

            if ($reportData) {
                if (!$this->_request->getParam('bar'))
                    $lines = true;

                $data_status = array();
                $series_status = array();
                $data_center = array();
                $series_center = array();
                $data_seconds = array();
                $data_tarif = array();
                $data = array();

                foreach ($reportData as $row) {
                    $date = strtotime(substr($row['dia'], 3, 2) . "/" . substr($row['dia'], 0, 2) . "/" . substr($row['dia'], 6, 4));
                    if (!$ini && !$fim || $date >= $ini && $date <= $fim) {

                        if (!isset($data_status[$date]))
                            $data_status[$date] = array();
                        if (!isset($data_status[$date][$row['disposition']]))
                            $data_status[$date][$row['disposition']] = 0;

                        if (!isset($data_center[$date]))
                            $data_center[$date] = array();
                        if (!isset($data_center[$date][$row['nome']]))
                            $data_center[$date][$row['nome']] = 0;

                        if (!isset($data_seconds[$date]))
                            $data_seconds[$date] = array();
                        if (!isset($data_seconds[$date]['duration']))
                            $data_seconds[$date]['duration'] = 0;
                        if (!isset($data_seconds[$date]['billsec']))
                            $data_seconds[$date]['billsec'] = 0;

                        if (!isset($data_tarif[$date]))
                            $data_tarif[$date] = array();
                        if (!isset($data_tarif[$date]['tarif']))
                            $data_tarif[$date]['tarif'] = 0;

                        $data[$date] = true;
                        //Zend_Debug::Dump();exit
                        $data_status[$date][$row['disposition']]++;

                        $series_status[$row['disposition']] = true;

                        $data_center[$date][$row['nome']]++;
                        $series_center[$row['nome']] = true;

                        $data_seconds[$date]['duration']+= $row['duration'];
                        $data_seconds[$date]['billsec']+= $row['billsec'];

                        if (isset($row['rate']))
                            $data_tarif[$date]['tarif']+= $row['rate'];
                    }
                }


                $label = array();
                $data_status_final = array();
                $data_center_final = array();
                $data_seconds_final = array();
                $data_tarif_final = array();

                if ($_SESSION['formDataCRC']['period']['order'] != "date")
                    ksort($data, SORT_NUMERIC);

                foreach ($data as $date => $row) {
                    $label[] = $date;
                    foreach ($series_status as $key => $value) {
                        if (isset($data_status[$date][$key]) && $data_status[$date][$key])
                            $data_status_final[$key][] = $data_status[$date][$key];
                        else
                            $data_status_final[$key][] = 0;
                    }
                    foreach ($series_center as $key => $value) {
                        if (isset($data_center[$date][$key]))
                            $data_center_final[$key][] = $data_center[$date][$key];
                        else
                            $data_center_final[$key][] = 0;
                    }

                    if ($data_seconds[$date]['duration'])
                        $data_seconds_final['duration'][] = $data_seconds[$date]['duration'];
                    else
                        $data_seconds_final['duration'][] = 0;
                    if ($data_seconds[$date]['billsec'])
                        $data_seconds_final['billsec'][] = $data_seconds[$date]['billsec'];
                    else
                        $data_seconds_final['billsec'][] = 0;

                    if ($data_tarif[$date]['tarif'])
                        $data_tarif_final['tarif'][] = $data_tarif[$date]['tarif'];
                    else
                        $data_tarif_final['tarif'][] = 0;
                }
                $_SESSION['CRC_GRAPHIC_LABEL'] = $label;

                $_SESSION['CRC_GRAPHIC_STATUS'] = array();
                foreach ($series_status as $key => $value) {
                    $_SESSION['CRC_GRAPHIC_STATUS'][] = array($key, $key, $data_status_final[$key]);
                }

                $_SESSION['CRC_GRAPHIC_CENTER'] = array();
                foreach ($series_center as $key => $value) {
                    $_SESSION['CRC_GRAPHIC_CENTER'][] = array($key, $key, $data_center_final[$key]);
                }

                $_SESSION['CRC_GRAPHIC_SECONDS'] = array();
                $_SESSION['CRC_GRAPHIC_SECONDS'][] = array('duration', $this->view->translate("Duration"), $data_seconds_final['duration']);
                $_SESSION['CRC_GRAPHIC_SECONDS'][] = array('billsec', $this->view->translate("Conversation"), $data_seconds_final['billsec']);

                $_SESSION['CRC_GRAPHIC_TARIF'] = array();
                $_SESSION['CRC_GRAPHIC_TARIF'][] = array('tarif', $this->view->translate("Total tariffed"), $data_tarif_final['tarif']);
            }
        }
    }
    
    /**
     * graphic - Monta gráfico dos dados
     * @param <strong> $type
     * @return type
     */
    public function graphic($type = null) {
        if (!$type)
            $type = $this->_request->getParam('type');

        if ($type == 'status' && $_SESSION['CRC_GRAPHIC_STATUS']) {
            return Snep_Graphic::getGraphic(array('date', '', $_SESSION['CRC_GRAPHIC_LABEL']), $_SESSION['CRC_GRAPHIC_STATUS'], $this->view->translate("Status"), !$this->_request->getParam('bar'));
        }
        if ($type == 'center' && $_SESSION['CRC_GRAPHIC_CENTER']) {
            return Snep_Graphic::getGraphic(array('date', '', $_SESSION['CRC_GRAPHIC_LABEL']), $_SESSION['CRC_GRAPHIC_CENTER'], $this->view->translate("Cost Center"), !$this->_request->getParam('bar'));
        }
        if ($type == 'seconds' && $_SESSION['CRC_GRAPHIC_SECONDS']) {
            return Snep_Graphic::getGraphic(array('date', '', $_SESSION['CRC_GRAPHIC_LABEL']), $_SESSION['CRC_GRAPHIC_SECONDS'], $this->view->translate("Time"), !$this->_request->getParam('bar'), true);
        }
        if ($type == 'tarif' && $_SESSION['CRC_GRAPHIC_TARIF']) {
            return Snep_Graphic::getGraphic(array('date', '', $_SESSION['CRC_GRAPHIC_LABEL']), $_SESSION['CRC_GRAPHIC_TARIF'], $this->view->translate("Total tariffed"), !$this->_request->getParam('bar'));
        }
        else
            return $test = Snep_Graphic::getGraphic(NULL, NULL, "", !$this->_request->getParam('bar'));
    }

    public function errorAction() {
        
    }
    
    /**
     * addDashboardAction - Adiciona página ao dashboard
     */
    public function addDashboardAction() {
        if ($_SESSION['formDataCRC'])
            Snep_Dashboard_Manager::add(array(
                'nome' => $this->_request->getParam('nome'),
                'descricao' => $this->_request->getParam('descricao'),
                'icone' => 'report_icon.png',
                'link' => 'calls-report/create',
                'session' => $_SESSION['formDataCRC']
            ));
        $this->_helper->redirector('index', 'index');
    }

}

