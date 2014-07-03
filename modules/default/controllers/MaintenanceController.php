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
 * controller maintenance
 */
class MaintenanceController extends Zend_Controller_Action {
    
    /**
     * indexAction - filter maintenance
     */
    public function indexAction() {

        $this->view->errors = self::inspector();
        $this->view->breadcrumb = $this->view->translate("Settings") . ' » ' . $this->view->translate("Maintenance of System");
        $this->view->dates = array('init' => date('d/m/Y', strtotime("-1 days")), 'end' => date('d/m/Y') );

        $dir = Zend_Registry::get('config')->ambiente->path_voz . "/backup/" ;

        $files = array();
        $ctd = 0;

        if ( is_dir( $dir ) ) {
           if ($dh = opendir( $dir )) {
              while (($file = readdir($dh)) !== false) {
                 if ($file != "." || $file !="..") {

                    if(strpos($file, 'tgz') || strpos($file, 'pdf') || strpos($file, 'bz2') || strpos($file, 'tar')) {
                        $files[$ctd]['name'] = $file ;
                        $files[$ctd]['dir']  = $dir ;
                        $files[$ctd]['date'] = date ("d F Y H:i:s", filectime( $dir.$file ) ) ;
                        $files[$ctd]['size'] = number_format( sprintf( "%u", filesize( $dir.$file ) ) / 1024, "0", ",", ".")  . " Kb";
                        $ctd ++ ;
                    }
                 }
              }
              closedir($dh);
           }
        }

        $this->view->files = $files;
    }
    
    /**
     * compactAction - Compact archives sounds
     */
    public function compactAction() {

        if($this->_request->getParams()) {

                $path = Zend_Registry::get('config')->ambiente->path_voz;
                $backup = Zend_Registry::get('config')->ambiente->path_voz_bkp;
                
                // Recolhe datas do form
                $data_ini = new Zend_Date($_POST['init-data-compact']);
                $data_fim = new Zend_Date($_POST['end-data-compact']);
                

                $fileName = "Chamadas_" . $data_ini->toString('dd-MM-YYYY') . "_ate_" . $data_fim->toString('dd-MM-YYYY');

                $data_fim = $data_fim->toString('YYYY-MM-dd') . " 23:59:59";
                $data_ini = $data_ini->toString('YYYY-MM-dd') . " 00:00:00";

                // Recupera objeto do banco
                $db = Zend_Registry::get('db');

                $select = $db->select()
                    ->from('cdr', array(' count(calldate) as total'))
                    ->where("calldate >= '$data_ini' AND calldate <= '$data_fim' ");

                $stmt = $db->query($select);
                $total = $stmt->fetch();

                $tot = 0;

                if($total['total'] > 18 ) {
                    $media = $total['total'] / 9;
                }else{
                    $media = $total['total'];
                }

                $compress_command = "tar -jcvf $backup$fileName.tar.bz2 ";
                $remove_command = "rm -f ";
                $output = "";

                $i = 0;
                while($i <= $total['total']) {

                    $select = $db->select()
                                 ->from('cdr', array('calldate','userfield'))
                                 ->where("calldate >= '$data_ini' AND calldate <= '$data_fim' ")
                                 ->order('userfield')
                                 ->limit( $media, $i );

                    $stmt = $db->query($select);
                    $result = $stmt->fetchAll();

                    foreach($result as $call) {

                        $x = Snep_Manutencao::arquivoExiste( $call['calldate'], $call['userfield'], true );
                        if($x) {
                            $output .= " $x ";
                        }          
                    }
                    unset($result);

                   $i = $i+$media;
                }

                exec(" $compress_command $output ");
                
                if( $_POST['type'] ==  'type-compact-remove' ) {
                    exec(" $remove_command $output ");
                }                
                $this->_redirect('./default/maintenance/');
        }        

    }

    /**
     * emoveFileAction - Remove archives selected
     */
    public function removeFileAction() {

        if($this->_request->getParam('file')) {

            $file = $this->_request->getParam('file');
            $path = Zend_Registry::get('config')->ambiente->path_voz_bkp .'/';

            if(file_exists($path . $file)) {
                if(is_writable( $path . $file) ) {
                    exec("rm -f $path$file");
                }
            }

        }
        $this->_redirect('./default/maintenance/');        
    }
    
    /**
     * removeRegisterAction - Remove recording files
     */
    public function removeRegisterAction() {

        if($this->_request->getParams()) {

                $path = Zend_Registry::get('config')->ambiente->path_voz;

                // Recolhe datas do form
                $data_ini = new Zend_Date($_POST['init-data-remove']);
                $data_ini = $data_ini->toString('YYYY-MM-dd') . " 00:00:00";

                $data_fim = new Zend_Date($_POST['end-data-remove']);
                $data_fim = $data_fim->toString('YYYY-MM-dd') . " 23:59:59";

                // Recupera objeto do banco
                $db = Zend_Registry::get('db');

                $select = $db->select()
                    ->from('cdr', array(' count(calldate) as total'))
                    ->where("calldate >= '$data_ini' AND calldate <= '$data_fim' ");                    

                $stmt = $db->query($select);
                $total = $stmt->fetch();

                $tot = 0;
                $media = $total['total'] / 9;
                $i = 0;

                while($i <= $total['total']) {

                    $select = $db->select()
                                 ->from('cdr', array('calldate','userfield'))
                                 ->where("calldate >= '$data_ini' AND calldate <= '$data_fim' ")
                                 ->order('userfield')
                                 ->limit( $media, $i );

                    $stmt = $db->query($select);
                    $result = $stmt->fetchAll();

                    foreach($result as $call) {
                        $x = Snep_Manutencao::arquivoExiste( $call['calldate'], $call['userfield'], $path, true );
                        if($x) {
                            exec(" rm -f $x ");
                        }
                    }

                    unset($result);

                   $i = $i+$media;
                }

                $db->beginTransaction();
                
                try {
                    $db->delete('cdr', "calldate >= '$data_ini' AND calldate <= '$data_fim' ");
                    $db->commit();
                } catch (Exception $e) {
                    $db->rollBack();
                }
        }
        $this->_redirect('./default/maintenance/');      

    }
    
    /**
     * inspector - inspector error
     * @return <int>
     */
    protected function inspector() {

        $result['error'] = 0;
        $result['message'] = '';

        $paths = array(Zend_Registry::get('config')->ambiente->path_voz     => array('exists' => 1, 'writable' => 1, 'readable' => 1),
                       Zend_Registry::get('config')->ambiente->path_voz_bkp => array('exists' => 1, 'writable' => 1, 'readable' => 1) );

        foreach($paths as $path => $permission) {

            if($permission['exists']) {

                if( ! file_exists( $path ) ) {
                    $result['message'] .= " \n";
                    $result['message'] .= $path ." ". Zend_Registry::get("Zend_Translate")->translate(" does not exist. ") ."\n";
                    $result['error'] = 1;

                }else{
                    if($permission['writable']) {
                        if( ! is_writable( $path ) ) {
                            $result['message'] .= $path ." ". Zend_Registry::get("Zend_Translate")->translate(" Not have have write permission. ") ."\n";
                            $result['error'] = 1;
                        }
                    }
                    if($permission['readable']) {
                        if( ! is_readable( $path ) ) {
                            $result['message'] .= " \n";
                            $result['message'] .= $path ." ". Zend_Registry::get("Zend_Translate")->translate(" Not have have read permission. ") ."\n";
                            $result['error'] = 1;
                        }
                    }
                }
            }
        }

        return $result;

    }

}