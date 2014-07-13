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

require_once 'Snep/Inspector.php';

/**
 * Sound Files Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti
 */
class SoundFilesController extends Zend_Controller_Action {

    /**
     * indexAction - List all sound files
     */
    public function indexAction() {

        $this->view->url = $this->getFrontController()->getBaseUrl() . "/" . $this->getRequest()->getControllerName();
        $this->view->path_sound = $arquivo = Zend_Registry::get('config')->system->path->web . '/sounds/pt_BR/';

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from("sounds");

        $this->view->filter_value = Snep_Filter::setSelect($select, array('arquivo', 'descricao'), $this->_request);
        $this->view->order = Snep_Order::setSelect($select, array('arquivo', 'descricao', 'data'), $this->_request);
        $this->view->limit = Snep_Limit::get($this->_request);

        $objInspector = new Snep_Inspector('Permissions');
        $inspect = $objInspector->getInspects();
        $this->view->error = $inspect['Permissions'];

        $stmt = $db->query($select);
        $files = $stmt->fetchAll();

        $_files = array();
        foreach ($files as $id => $file) {
            $info = Snep_SoundFiles_Manager::verifySoundFiles($file['arquivo']);

            if (count($info) != 0 && $file['tipo'] != "MOH")
                $_files[] = array_merge($file, $info);
        }

        $page = $this->_request->getParam('page');
        $this->view->page = ( isset($page) && is_numeric($page) ? $page : 1 );




        $paginatorAdapter = new Zend_Paginator_Adapter_Array($_files);
        $paginator = new Zend_Paginator($paginatorAdapter);
        $paginator->setCurrentPageNumber($this->view->page);
        $paginator->setItemCountPerPage($this->view->limit);


        $this->view->files = $paginator;
        $this->view->pages = $paginator->getPages();
        $this->view->PAGE_URL = "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/";



        $filter = new Snep_Form_Filter();
        $filter->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/index');
        $filter->setValue($this->view->order);
        $filter->setFieldValue($this->view->filter_value);
        $filter->setResetUrl("{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/index/page/$page");

        $this->view->title = "Sound Files";
        $this->view->form_filter = $filter;
        $this->view->filter = array(array("url" => "{$this->getFrontController()->getBaseUrl()}/{$this->getRequest()->getControllerName()}/add/",
                "display" => $this->view->translate("Add Sound File"),
                "css" => "include"));
    }

    /**
     *  addAction - Add Sound File
     */
    public function addAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Sound Files"),
                    $this->view->translate("Add")
        ));
        $path_sound = Zend_Registry::get('config')->system->path->asterisk->sounds;

        $db = Zend_Registry::get('db');

        $sql = "SELECT arquivo from sounds";
        $row = $db->query($sql)->fetchAll();

        // Lista de Arquivos que estao no Disco 
        $files = scandir($path_sound);

        foreach ($files as $i => $value) {
            if (substr($value, 0, 1) == '.') {
                unset($files[$i]);
            } else
            if (is_dir($files[$i])) {

                unset($files[$i]);
            }
        }

        // Retira da Lista os arquivos que ja estao no Cadastro do SNEP
        foreach ($row as $key => $value) {
            if ($i = array_search($value['arquivo'], $files)) {
                unset($files[$i]);
            }
        }


        $this->view->sounds_ast = $files;

        $form = new Snep_Form(new Zend_Config_Xml("modules/default/forms/sound_files.xml"));

        $file = new Zend_Form_Element_File('file');
        $file->setLabel($this->view->translate('Select the file'))
                ->addValidator(new Zend_Validate_File_Extension(array('wav', 'gsm')))
                ->removeDecorator('DtDdWrapper')
                ->removeDecorator('HtmlTag')
                ->removeDecorator('elementTd')
                ->removeDecorator('elementTr')
                ->removeDecorator('Label')
                ->addDecorator(array('elementTd' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input'))
                ->addDecorator('Label', array('tag' => 'div', 'class' => 'label'))
                ->addDecorator(array('elementTr' => 'HtmlTag'), array('tag' => 'div', 'class' => 'line'))
                ->setIgnore(true)
                ->setRequired(false);

        $form->getElement('type')
                ->setMultiOptions(array('AST' => $this->view->translate('Asterisk Default'),
                    'URA' => $this->view->translate('U.R.A')))
                ->setValue('AST');
        $form->addElement($file);

        $form->removeElement('filename');


        if ($this->_request->getPost()) {

            $form_isValid = $form->isValid($_POST);

            $dados = $this->_request->getParams();

            if ($form_isValid) {

                if ($dados['name'] == "" && $_FILES['file']['name'] != "") {

                    $invalid = array('â', 'ã', 'á', 'à', 'ẽ', 'é', 'è', 'ê', 'í', 'ì', 'ó', 'õ', 'ò', 'ú', 'ù', 'ç', " ", '@', '!');
                    $valid = array('a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'o', 'o', 'o', 'u', 'u', 'c', "_", '_', '_');

                    $originalName = str_replace($invalid, $valid, $_FILES['file']['name']);
                    $uploadName = $_FILES['file']['tmp_name'];
                    $arq_tmp = $path_sound . "/tmp/" . $originalName;
                    $arq_dst = $path_sound . "/" . $originalName;
                    //$arq_bkp = $path_sound . "/backup/" . $originalName;
                    //$arq_orig = $path_sound . "/pt_BR/" . $originalName;

                    $exist = Snep_SoundFiles_Manager::get($originalName);

                    if ($exist) {

                        $form->getElement('file')->addError($this->view->translate('File already exists'));
                        $form_isValid = false;
                    } else {

                        if (!move_uploaded_file($uploadName, $arq_tmp)) {
                            throw new ErrorException($this->view->translate("Unable to move file"));
                        }

                        if ($dados['gsm'] == 1) {

                            $fileNe = $path_sound . '/' . basename($arq_dst, '.wav') . '.gsm';
                            exec("sox $arq_tmp  -r 8000 -c 1 $fileNe resample -ql");
                            exec("rm $arq_tmp");
                            $originalName = basename($originalName, '.wav') . ".gsm";
                        } else {
                            exec("sox $arq_tmp -r 8000 -c 1 -e signed-integer -b 16 $arq_dst");
                            $comando = "mv $arq_tmp $arq_dst";
                            $result = exec("$comando 2>&1", $out, $err);
                            //exec("mv {$arq_dst} {$arq_tmp}");
                        }
                        if (isset($arq_dst) || isset($fileNe)) {
                            //   if (file_exists($arq_dst) || file_exists($fileNe)) {
                            Snep_SoundFiles_Manager::add(array('arquivo' => $originalName,
                                'descricao' => $dados['description'],
                                'tipo' => $dados['type']));

                            //log-user
                            $tabela = self::verificaLog();
                            if ($tabela == true) {

                                $id = $originalName;
                                $acao = "Adicionou Arquivo de som";
                                self::salvaLog($acao, $id);
                                $action = "ADD";
                                $add = self::getSounds($id);
                                self::insertLogSounds($action, $add);
                            }

                            $this->_redirect($this->getRequest()->getControllerName());
                        } else {

                            $this->view->error = array('error' => 1,
                                'message' => $this->view->translate('File already exists'));
                        }
                    }
                } else {

                    if ($dados['name'] == "") {

                        $form->getElement('file')->addError($this->view->translate('Select one file'));
                        $form_isValid = false;
                    } else {

                        if ($dados['gsm'] == 1) {
                            $fileNe = $path_sound . '/' . basename($dados['name'], '.wav') . '.gsm';
                            exec("sox $arq_tmp -r 8000 $fileNe resample -ql");
                            exec("rm $arq_tmp");
                            $originalName = basename($originalName, '.wav') . ".gsm";
                        } else {
                            exec("sox $arq_tmp -r 8000 -c 1 -e signed-integer -b 16 $arq_dst");
                            $comando = "mv $arq_tmp $arq_dst";
                            $result = exec("$comando 2>&1", $out, $err);
                        }


                        Snep_SoundFiles_Manager::add(array('arquivo' => $dados['name'],
                            'descricao' => $dados['description'],
                            'tipo' => $dados['type']));

                        //log-user
                        $tabela = self::verificaLog();
                        if ($tabela == true) {

                            $id = $_POST["name"];
                            $acao = "Adicionou Arquivo de som";
                            self::salvaLog($acao, $id);
                            $action = "ADD";
                            $add = self::getSounds($id);
                            self::insertLogSounds($action, $add);
                        }

                        $this->_redirect($this->getRequest()->getControllerName());
                    }
                }
            }
        }
        $this->view->form = $form;
    }

    /**
     * editAction - Edit Carrier
     */
    public function editAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Sound Files"),
                    $this->view->translate("Edit")
        ));

        $file = $this->_request->getParam("file");
        $data = Snep_SoundFiles_Manager::get($file);

        $this->view->file = $data;

        $form = new Snep_Form(new Zend_Config_Xml("modules/default/forms/sound_files.xml"));

        $file = new Zend_Form_Element_File('file');
        $file->setLabel($this->view->translate('Sound Files'))
                ->addValidator(new Zend_Validate_File_Extension(array('wav', 'gsm')))
                ->addValidator(new Zend_Validate_File_Extension(array('wav', 'gsm')))
                ->removeDecorator('DtDdWrapper')
                ->removeDecorator('HtmlTag')
                ->removeDecorator('elementTd')
                ->removeDecorator('elementTr')
                ->removeDecorator('Label')
                ->addDecorator(array('elementTd' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input'))
                ->addDecorator('Label', array('tag' => 'div', 'class' => 'label'))
                ->addDecorator(array('elementTr' => 'HtmlTag'), array('tag' => 'div', 'class' => 'line'))
                ->setIgnore(true);
        $form->addElement($file);

        $form->getElement('filename')->setValue($data['arquivo'])
                ->setAttrib('readonly', true);

        $form->getElement('description')->setLabel($this->view->translate('Description'))
                ->setValue($data['descricao'])
                ->setRequired(true);

        $form->getElement('type')
                ->setMultiOptions(array('AST' => $this->view->translate('Asterisk Default'),
                    'URA' => $this->view->translate('U.R.A')))
                ->setValue($data['tipo'])->setRequired(true);


        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();
            $form_isValid = $form->isValid($dados);

            $files = Snep_SoundFiles_Manager::get($originalName);

            if ($files) {
                $file->addError($this->view->translate("File already exists"));
                $form_isValid = false;
            }
            if ($form_isValid) {
                if ($_FILES['file']['name'] != "" && $_FILES['file']['size'] > 0) {

                    $path_sound = Zend_Registry::get('config')->system->path->asterisk->sounds;
                    $filepath = Snep_SoundFiles_Manager::verifySoundFiles($dados['filename'], true);
                    exec("mv {$filepath['fullpath']} $path_sound/backup/");
                    exec("sox {$_FILES['file']['tmp_name']} -r 8000 /var/lib/asterisk/sounds/pt_BR/{$dados['filename']} resample -ql");
                    //exec("mv  {$_FILES['file']['tmp_name']} {$filepath['fullpath']} ");
                }


                Snep_SoundFiles_Manager::edit($dados);

                $this->_redirect($this->getRequest()->getControllerName());
            }
        }
        $this->view->form = $form;
    }

    /**
     * removeAction - Remove a Carrier
     */
    public function removeAction() {

        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Sound Files"),
                    $this->view->translate("Delete")
        ));
        $id = $this->_request->getParam('id');

        //log-user
        $tabela = self::verificaLog();
        if ($tabela == true) {

            $acao = "Excluiu Arquivo de som";
            self::salvaLog($acao, $id);
            $action = "DEL";
            $add = self::getSounds($id);
            self::insertLogSounds($action, $add);
        }
        
        Snep_SoundFiles_Manager::remove($id);

        exec("rm /var/lib/asterisk/sounds/pt_BR/$id");
        exec("rm /var/lib/asterisk/sounds/pt_BR/backup/$id");

        $this->_redirect($this->getRequest()->getControllerName());
    }
    
    /**
     * verificaLog - Verify if exists module Loguser
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
     * salvaLog - Insert log in database
     * @param <string> $acao
     * @param <string> $sounds
     * @return <boolean>
     */
    function salvaLog($acao, $sounds) {
        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');
        $tipo = 8;

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $acao = mysql_escape_string($acao);

        $sql = "INSERT INTO `logs` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $sounds . "', '" . $tipo . "' , '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . NULL . "')";

        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getSounds - set array with data of sond files
     * @param <int> $id - Code sound file
     * @return <array> $archive - Data of sound file
     */
    function getSounds($id) {

        $archive = array();

        $db = Zend_Registry::get("db");
        $sql = "SELECT * from  sounds where arquivo='$id'";

        $stmt = $db->query($sql);
        $archive = $stmt->fetch();

        return $archive;
    }

    /**
     * insertLogFila - Insert on table logs_users the data of sound files
     * @param <string> $acao
     * @param <array> $add
     */
    function insertLogSounds($acao, $add) {

        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $sql = "INSERT INTO `logs_users` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $add["arquivo"] . "', '" . $add["descricao"] . "', '" . $add["tipo"] . "', '" . NULL . "', '" . "SOM" . "', '" . $acao . "')";
        $db->query($sql);
    }
    
    /**
     * restoreAction
     * @throws ErrorException
     */
    public function restoreAction() {

        $file = $this->_request->getParam('file');

        if ($file) {
            $result = Snep_SoundFiles_Manager::verifySoundFiles($file, true);

            if ($result['fullpath'] && $result['backuppath']) {
                try {
                    exec("mv {$result['backuppath']}  {$result['fullpath']} ");
                } catch (Exception $e) {
                    throw new ErrorException($this->view->translate("Unable to restore file"));
                }
            }
        }

        $this->_redirect($this->getRequest()->getControllerName());
    }

}