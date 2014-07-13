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
 * controller inpector
 */
class InspectorController extends Zend_Controller_Action {
    
    /**
     * indexAction - Diagnostic of system
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Configure"),
                    $this->view->translate("System Status")
        ));

        // Creates Snep_Inspector Object
        $objInspector = new Snep_Inspector();


        // Get array with status of inspected system requirements 
        $inspect = $objInspector->getInspects();

        $this->view->inspect = $inspect;

        //inicia script ler_queues
        if ($this->_request->getPost()) {

            $install = "ler_queues";
            $path = "/etc/init.d/";
            $func = $path . $install;

            exec("sudo " . $func . " start > /dev/null 2>&1 &");

            $output = shell_exec('ps aux | grep ler_que');
            $findme = 'ler_queues.php';
            $pos = strpos($output, $findme);

            if ($pos === false) {

                $output = shell_exec('ps aux | grep ler_que');
                $findme = 'ler_queues.php';
                $pos = strpos($output, $findme);

                if ($pos === false) {

                    $this->view->error = $this->view->translate("Error when running script ler_queues");
                    $this->_helper->viewRenderer('error');
                }
            }
        }
    }

}


