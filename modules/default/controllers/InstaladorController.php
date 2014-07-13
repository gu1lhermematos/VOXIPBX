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
 * controller instalador
 */
class InstaladorController extends Zend_Controller_Action {
    
    /**
     * indexAction - Install modules
     */
    public function indexAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Status"),
                    $this->view->translate("Installer")
        ));

        // Create object Snep_Form
        $form = new Snep_Form();

        // Set form action
        $form->setAction($this->getFrontController()->getBaseUrl() . '/instalador/index');

        if ($this->_request->getPost()) {

            $install = "get_instalador.sh";

            // Pega array do setup.conf do Zend_Registry.
            $config = Zend_Registry::get('config');
            // Pega registro path.instalador do setup.conf
            $path = $config->system->path->scripts . "/";

            $func = $path . $install;

            $pathinstall = $config->system->path->installer . "/";
            $archive = "application/Bootstrap.php";
            $pathinstall .= $archive;

            exec($func, $retorno);
            if ($retorno != "") {
                if ($retorno[0] == "dow") {

                    $this->view->error = $this->view->translate("Error when downloading file");
                    $this->_helper->viewRenderer('error');
                } else
                if (!file_exists($pathinstall)) {
                    $this->view->error = $this->view->translate("Unable to create directory");
                    $this->_helper->viewRenderer('error');
                } else {
                    $this->view->error = $this->view->translate("Module successfully installed");
                    $this->_helper->viewRenderer('error');
                }
            }
        }

        $this->view->form = $form;
    }

}

?>
