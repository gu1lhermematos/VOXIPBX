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

require_once 'Zend/Controller/Action.php';
require_once 'includes/functions.php';


/**
 * Services controller.
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2013 OpenS Tecnologia
 */
class SystemstatusController extends Zend_Controller_Action {
    
    /**
     * indexAction - List information of services
     */
    public function indexAction() {

        //Disables layout so jQuery .get() can call pure HTML at the index page.
        $this->_helper->layout()->disableLayout();

        $this->view->breadcrumb = $this->view->translate("Welcome to Snep version %s", SNEP_VERSION);

        // Direcionando para o "snep antigo"
        $config = Zend_Registry::get('config');
        $db = Zend_Registry::get('db');

        // GOD!
        $linfoData = new Zend_Http_Client('http://localhost/' . str_replace("/index.php", "", $this->getFrontController()->getBaseUrl()) . '/lib/linfo/index.php?out=xml');
        try {
            $linfoData->request();
            $sysInfo = $linfoData->getLastResponse()->getBody();
            $sysInfo = simplexml_load_string($sysInfo);
        } catch (HttpException $ex) {
            echo $ex;
        }

        if (trim($config->ambiente->db->host) == "") {
            $this->_redirect("/installer/");
        } else {

            $systemInfo = array();
            $uptimeRaw = explode(';', $sysInfo->core->uptime);
            $systemInfo['uptime'] = $uptimeRaw[0];


            require_once "includes/AsteriskInfo.php";

            try {
                $astinfo = new AsteriskInfo();
                $astVersionRaw = explode('@', $astinfo->status_asterisk("core show version", "", True));
                preg_match('/Asterisk (.*) built/', $astVersionRaw[0], $astVersion);

                $data = $astinfo->status_asterisk("database show", "", True);
                $lines = explode("\n", $data);
                $arr = array();

                $systemInfo['sip_peers'] = str_replace(array("onitored","line","sip"), array("onit.","",""), 
                        $astinfo->status_asterisk("sip show  peers", "sip peers"));    
                $systemInfo['sip_channels'] = $astinfo->status_asterisk("sip show channels", "SIP dialog");
                $systemInfo['iax2_peers'] = str_replace(array("onitored","line","iax2"), array("onit.","",""), 
                        $astinfo->status_asterisk("iax2 show peers", "iax2 peers"));
                $systemInfo['agents'] = $astinfo->status_asterisk("show agents", "agents configured");
            } catch (Exception $e) {
                
            }
            if (isset($astVersion[1])) {
                $systemInfo['asterisk'] = "Asterisk - " . $astVersion[1];
            } else {
                $systemInfo['asterisk'] = "Asterisk - ";
            }
            $systemInfo['mysql'] = trim(exec("mysql -V | awk -F, '{ print $1 }' | awk -F'mysql' '{ print $2 }'"));

            Zend_Date::setOptions(array('extend_month' => true));
            $dataAtual = new Zend_Date();
            $systemInfo['data'] = $dataAtual->toString('dd/MM/YYYY hh:mm:ss');

            $systemInfo['linux_ver'] = $sysInfo->core->os . ' / ' . $sysInfo->core->Distribution;

            $systemInfo['linux_kernel'] = $sysInfo->core->kernel;

            $cpuRaw = explode('-', $sysInfo->core->CPU);
            $systemInfo['hardware'] = $cpuRaw[1];

            $cpuNumber = count(explode('<br />', $sysInfo->core->CPU));

            $cpuUsageRaw = explode(' ', $sysInfo->core->load);
            $loadAvarege = ($cpuUsageRaw[0] + $cpuUsageRaw[1] + $cpuUsageRaw[2]) / 3;
            
            $config = Zend_Registry::get('config');
          
            if (isset($config->ambiente->path_voz)) {
                $path_voz = $config->ambiente->path_voz;
            } else {
                $path_voz = "";
            }
            unset($config);
            
            $systemInfo['num_arqvoz'] = exec("scripts/num_arquivos " . $path_voz);
           // $systemInfo['spc_arqvoz'] = exec("du -sch $path_voz | cut -f1");

            $systemInfo['usage'] = round(($loadAvarege * 100) / ($cpuNumber - 1));


            $systemInfo['memory'] = self::sys_meminfo();



            $systemInfo['memory']['swap'] = array(
                'total' => $this->byte_convert(floatval($sysInfo->memory->swap->core->free)),
                'free' => $this->byte_convert(floatval($sysInfo->memory->swap->core->total)),
                'used' => $this->byte_convert(floatval($sysInfo->memory->swap->core->used)),
                'percent' => floatval($sysInfo->memory->swap->core->total) > 0 ? round(floatval($sysInfo->memory->swap->core->used) / floatval($sysInfo->memory->swap->core->total) * 100) : 0
            );

            $deviceArray = $sysInfo->mounts->mount;
            foreach ($deviceArray as $mount) {
                $systemInfo['space'][] = array(
                    'mount_point' => $mount["mountpoint"],
                    'size' => $this->byte_convert(floatval($mount["size"])),
                    'free' => $this->byte_convert(floatval($mount["free"])),
                    'percent' => floatval($mount["size"]) > 0 ? round((floatval($mount["used"]) / floatval($mount["size"])) * 100) : 0
                );
            }

            $netArray = $sysInfo->net->interface;
            $count = 0;

            foreach ($netArray as $board) {
                if ($count < 6) {
                    $systemInfo['net'][] = array(
                        'device' => $board["device"],
                        'up' => $board["state"]);
                    $count++;
                }
            }

//            $sqlN = "select count(*) from";
//            $select = $db->query($sqlN . ' peers');
//            $result = $select->fetch();
//
//            $systemInfo['num_peers'] = $result['count(*)'];
//
//            $select = $db->query($sqlN . ' trunks');
//            $result = $select->fetch();
//
//            $systemInfo['num_trunks'] = $result['count(*)'];
//
//            $select = $db->query($sqlN . ' regras_negocio');
//            $result = $select->fetch();
//
//            $systemInfo['num_routes'] = $result['count(*)'];

            $systemInfo['modules'] = array();
            $modules = Snep_Modules::getInstance()->getRegisteredModules();
            foreach ($modules as $module) {
                $systemInfo['modules'][] = array(
                    "name" => $module->getName(),
                    "version" => $module->getVersion(),
                    "description" => $module->getDescription()
                );
            }

            //verifica se ler_queues está rodando
            $cc = self::ler_queues();
            
            $systemInfo['cc'] = $cc["exists"];
            if($systemInfo['cc'] == true){
                $systemInfo['cc'] = $cc['ler_queues'];
            }
            
            $this->view->indexData = $systemInfo;

            // Creates Snep_Inspector Object
            $objInspector = new Snep_Inspector();

            // Get array with status of inspected system requirements
            $inspect = $objInspector->getInspects();

            // Verify errors
            $this->view->error = false;
            foreach ($inspect as $log => $message) {
                if ($message['error'] == 1) {
                    $this->view->error = true;
                }
            }

            // Inspector url
            $this->view->inspector = $this->getFrontController()->getBaseUrl() . '/inspector/';

            //Sends inspects to be viewed on the services screen (footer)
            $this->view->inspect = $inspect;
        }
    }
    
    /**
     * byte_convert
     * @param <int> $size
     * @param <int> $precision
     * @return string
     */
    function byte_convert($size, $precision = 2) {


        // Sanity check
        if (!is_numeric($size))
            return '?';

        // Get the notation
        $notation = 1024;

        // Fixes large disk size overflow issue
        // Found at http://www.php.net/manual/en/function.disk-free-space.php#81207
        $types = array('B', 'KB', 'MB', 'GB', 'TB');
        $types_i = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
        for ($i = 0; $size >= $notation && $i < (count($types) - 1 ); $size /= $notation, $i++)
            ;
        return(round($size, $precision) . ' ' . ($notation == 1000 ? $types[$i] : $types_i[$i]));
    }
    
    /**
     * ler_queues - Verify if daemon it is ative
     * @return <boolean>
     */
    function ler_queues() {
        $output = shell_exec('ps aux | grep ler_que');

        $findme = 'ler_queues.php';
        $pos = strpos($output, $findme);

        if (class_exists("Cc_AgentsInfo") || class_exists("Cc_Statistical")) {
            if ($pos === false) {
                $cc['ler_queues'] = "Desativado";
                $cc['exists'] = true;
                return $cc;
            } else {
                $cc['ler_queues'] = "Ativado";
                $cc['exists'] = true;
                return $cc;
            }
        } else {
            $cc['exists'] = false;
            return $cc;
        }
    }
    
    /**
     * sys_meminfo - information of system
     * @return type
     */
    function sys_meminfo() {
        $results['ram'] = array('total' => 0, 'free' => 0, 'used' => 0, 'percent' => 0);
        $results['swap'] = array('total' => 0, 'free' => 0, 'used' => 0, 'percent' => 0);
        $results['devswap'] = array();

        $bufr = rfts('/proc/meminfo');

        if ($bufr != "ERROR") {
            $bufe = explode("\n", $bufr);
            foreach ($bufe as $buf) {
                if (preg_match('/^MemTotal:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
                    $results['ram']['total'] = $ar_buf[1];
                } else if (preg_match('/^MemFree:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
                    $results['ram']['free'] = $ar_buf[1];
                } else if (preg_match('/^Cached:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
                    $results['ram']['cached'] = $ar_buf[1];
                } else if (preg_match('/^Buffers:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
                    $results['ram']['buffers'] = $ar_buf[1];
                }
            }
            $results['ram']['used'] = $results['ram']['total'] - $results['ram']['free'];
            $results['ram']['percent'] = round(($results['ram']['used'] * 100) / $results['ram']['total']);
            // values for splitting memory usage
            if (isset($results['ram']['cached']) && isset($results['ram']['buffers'])) {
                $results['ram']['app'] = $results['ram']['used'] - $results['ram']['cached'] - $results['ram']['buffers'];
                $results['ram']['app_percent'] = round(($results['ram']['app'] * 100) / $results['ram']['total']);
                $results['ram']['buffers_percent'] = round(($results['ram']['buffers'] * 100) / $results['ram']['total']);
                $results['ram']['cached_percent'] = round(($results['ram']['cached'] * 100) / $results['ram']['total']);
            }

            $bufr = rfts('/proc/swaps');
            if ($bufr != "ERROR") {
                $swaps = explode("\n", $bufr);
                for ($i = 1; $i < (sizeof($swaps)); $i++) {
                    if (trim($swaps[$i]) != "") {
                        $ar_buf = preg_split('/\s+/', $swaps[$i], 6);
                        $results['devswap'][$i - 1] = array();
                        $results['devswap'][$i - 1]['dev'] = $ar_buf[0];
                        $results['devswap'][$i - 1]['total'] = $ar_buf[2];
                        $results['devswap'][$i - 1]['used'] = $ar_buf[3];
                        $results['devswap'][$i - 1]['free'] = ($results['devswap'][$i - 1]['total'] - $results['devswap'][$i - 1]['used']);
                        $results['devswap'][$i - 1]['percent'] = round(($ar_buf[3] * 100) / $ar_buf[2]);
                        $results['swap']['total'] += $ar_buf[2];
                        $results['swap']['used'] += $ar_buf[3];
                        $results['swap']['free'] = $results['swap']['total'] - $results['swap']['used'];
                        $results['swap']['percent'] = round(($results['swap']['used'] * 100) / $results['swap']['total']);
                    }
                }
            }
        }
        return $results;
    }

}

