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
 * Expression Alias Controller. 
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Lucas Ivan Seidenfus
 */
class ExpressionAliasController extends Zend_Controller_Action {

    protected $form;
    
    /**
     * indexAction - List expression alias
     */
    public function indexAction() {


        $aliases = PBX_ExpressionAliases::getInstance();
        $this->view->aliases = $aliases->getAll();
        $this->view->title = "Expression Alias";
        $this->view->filter = array(array("url" => $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/add',
                "display" => $this->view->translate("Add Expression Alias"),
                "css" => "include"),
        );
    }
    
    /**
     * getForm - get Form expression alias
     * @return <object>
     */
    protected function getForm() {

        if ($this->form === Null) {
            $form_xml = new Zend_Config_Xml(Zend_Registry::get("config")->system->path->base . "/modules/default/forms/expression_alias.xml");
            $form = new Snep_Form($form_xml);


            $exprField = new Snep_Form_Element_Html("expression-alias/elements/expr.phtml", "expr", false);
            $exprField->setLabel($this->view->translate("Expressions"));
            $exprField->setOrder(1);
            $form->addElement($exprField);

            $this->form = $form;
        }

        return $this->form;
    }
    
    /**
     * addAction - Add expression alias
     * @throws PBX_Exception_BadArg
     */
    public function addAction() {

        $this->view->subTitle = $this->view->translate("Add Expression Alias");

        $form = $this->getForm();
        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            $expression = array(
                "name" => $_POST['name'],
                "expressions" => explode(",", $_POST['exprValue'])
            );

            $aliasesPersistency = PBX_ExpressionAliases::getInstance();

            //validacao
            $valida = Snep_ValidateExpression::execute($_POST['exprValue']);
            if ($valida['status'] != false) {
                echo "<script>alert('Sua álias possui caracter inválido. Não são permitidos acentos, valor vazio entre as chaves, espaços em branco e caracteres especiais, exceto( # % | . - _ )');</script>";
                header("refresh:0; ../expression-alias/add");
            } else
            if ($_POST["name"] == "" || $_POST["exprValue"] == "") {
                echo "<script>alert('Valor obrigatorio');</script>";
                header("refresh:0; ../expression-alias/add");
            }
            else
                try {

                    $aliasesPersistency->register($expression);
                    $exprList = $expression['expressions'];
                    $expr = "exprObj.addItem(" . count($exprList) . ");\n";

                    foreach ($exprList as $index => $value) {
                        $expr .= "exprObj.widgets[$index].value='{$value}';\n";
                    }

                    $this->view->dataExprAlias = $expr;
                    $form = $this->getForm();
                    $form->getElement('name')->setValue($_POST['name']);

                    $this->_helper->redirector('index');
                } catch (Exception $ex) {
                    throw new PBX_Exception_BadArg("Invalid Argument");
                }
        } else {
            $this->view->dataExprAlias = "exprObj.addItem();\n";
        }

        $this->renderScript('expression-alias/add_edit.phtml');
    }

    /**
     * editAction - Edit expression alias
     */
    public function editAction() {

        $this->view->subTitle = $this->view->translate("Edit Expression Alias");

        $id = (int) $this->getRequest()->getParam('id');

        $form = $this->getForm();
        $this->view->form = $form;
        $aliasesPersistency = PBX_ExpressionAliases::getInstance();

        if ($this->getRequest()->isPost()) {
            $expression = array(
                "id" => $id,
                "name" => $_POST['name'],
                "expressions" => explode(",", $_POST['exprValue'])
            );

            //validacao
            $valida = Snep_ValidateExpression::execute($_POST['exprValue']);
            if ($valida['status'] != false) {
                echo "<script>alert('Sua álias possui caracter inválido. Não são permitidos acentos, valor vazio entre as chaves, espaços em branco e caracteres especiais, exceto( # % | . - _ )');</script>";
                header("refresh:0; ../expression-alias/edit/id/$id");
            }
            else
                try {
                    $aliasesPersistency->update($expression);
                } catch (Exception $ex) {
                    display_error($ex->getMessage(), true);
                }
            $this->_forward('index', 'expression-alias');
        } else {

            $alias = $aliasesPersistency->get($id);
            $exprList = $alias['expressions'];
            $expr = "exprObj.addItem(" . count($exprList) . ");\n";

            foreach ($exprList as $index => $value) {
                $expr .= "exprObj.widgets[$index].value='{$value}';\n";
            }
            $this->view->dataExprAlias = $expr;
            $form = $this->getForm();
            $form->getElement('name')->setValue($alias['name']);

            $this->renderScript('expression-alias/add_edit.phtml');
        }
    }
    
    /**
     * deleteAction - delete expression alias
     */
    public function deleteAction() {

        if ($this->getRequest()->isGet()) {
            $id = (int) $this->getRequest()->getParam('id');
            $db = Zend_Registry::get('db');
            
            //verifica se grupo é usado em alguma regra
            $rules_query = "SELECT id, `desc` FROM regras_negocio WHERE origem LIKE 'AL:$id' OR destino LIKE 'AL:$id'";
            $regras = $db->query($rules_query)->fetchAll();

            if (count($regras) > 0) {

                $this->view->error = $this->view->translate("Cannot remove. The following routes are using this expression alias: ") . "<br />";
                foreach ($regras as $regra) {
                    $this->view->error .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
                }

                $this->_helper->viewRenderer('error');
            } else {

                $aliasesPersistency = PBX_ExpressionAliases::getInstance();
                $alias = $aliasesPersistency->get($id);
                if ($alias !== null) {
                    $aliasesPersistency->delete($id);
                }
                $this->_forward('index', 'expression-alias');
            }
        }
    }

}
