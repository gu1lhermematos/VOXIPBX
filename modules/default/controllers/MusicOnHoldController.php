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
 * Music on Hold Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti
 */
class MusicOnHoldController extends Zend_Controller_Action {

    /**
     * indexAction - List all Music on Hold sounds
     */
    public function indexAction() {

        $baseURL = $this->getFrontController()->getBaseUrl();
        $nameControll = $this->getRequest()->getControllerName();

        $objInspector = new Snep_Inspector('Sounds');
        $this->view->error = array_pop($objInspector->getInspects());
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Configure"),
                    $this->view->translate("Music on Hold Sessions")
                ));

        $this->view->url = $baseURL . "/" .
                $nameControll;

        $this->view->modes = array('files' => $this->view->translate('Directory'),
            'mp3' => $this->view->translate('MP3'),
            'quietmp3' => $this->view->translate('Normal'),
            'mp3nb' => $this->view->translate('Without buffer'),
            'quietmp3nb' => $this->view->translate('Without buffer quiet'),
            'custom' => $this->view->translate('Custom application'));

        Snep_SoundFiles_Manager::syncFiles();

        $this->view->sections = Snep_SoundFiles_Manager::getClasses();
        $this->view->title = "Music on Hold Sessions";
        $this->view->filter = array(array("url" => "{$baseURL}/{$nameControll}/add/",
                "display" => $this->view->translate("Add Session"),
                "css" => "include"));
    }

    /**
     * addAction - Add section of music on hold
     */
    public function addAction() {

        $nameControll = $this->getRequest()->getControllerName();

        $form = new Snep_Form(new Zend_Config_Xml("modules/default/forms/music_on_hold.xml"));
        $form->getElement('base')->setAttrib('readonly', true);

        if ($this->_request->getPost()) {

            $post = $_POST;
            $form_isValid = $form->isValid($post);

            $classes = Snep_SoundFiles_Manager::getClasses();

            if ($post['base'] != '/var/lib/asterisk/moh/') {
                $form->getElement('name')->addError(
                        $this->view->translate('Invalid Path'));

                $form_isValid = false;
            }
            if (file_exists($post['directory'])) {
                $form->getElement('directory')->addError(
                        $this->view->translate('Directory already exists'));

                $form_isValid = false;
            }

            foreach ($classes as $name => $item) {

                if ($item['name'] == $post['name']) {
                    $form->getElement('name')->addError(
                            $this->view->translate('Music on hold class already exists'));

                    $form_isValid = false;
                }
                $fullPath = $post['base'] . $post['directory'];
                if ($item['directory'] == $fullPath) {
                    $form->getElement('directory')->addError(
                            $this->view->translate('Directory already exists'));

                    $form_isValid = false;
                }
            }

            if ($form_isValid) {
                $post['directory'] = $post['base'] . $post['directory'];



                Snep_SoundFiles_Manager::addClass($post);

                $this->_redirect($nameControll);
            }
        }
        $this->view->form = $form;
    }

    /**
     * editAction - Edit section of music on hold
     */
    public function editAction() {


        $nameControll = $this->getRequest()->getControllerName();
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Music on Hold Sessions"),
                    $this->view->translate("Edit")
                ));

        $file = $this->_request->getParam("file");
        $data = Snep_SoundFiles_Manager::getClasse($file);

        $form = new Snep_Form(new Zend_Config_Xml("modules/default/forms/music_on_hold.xml"));
        $form->getElement('name')->setValue($data['name']);
        $form->getElement('mode')->setValue($data['mode']);

        $directory = explode("/", $data['directory']);
        $directoryName = array_pop($directory);

        $form->getElement('base')->setAttrib('readonly', true)->setValue(implode("/", $directory) . '/');
        $form->getElement('directory')->setValue($directoryName)->setRequired(true);
        $form->getElement('directory')->setAttrib('readonly', true);
        $form->getElement('name')->setAttrib('readonly', true);

        $originalName = new Zend_Form_Element_Hidden('originalName');
        $originalName->setValue($data['name']);

        $form->addElement($originalName);

        /* $form->setElementDecorators(array(
          'ViewHelper',
          'Description',
          'Errors',
          array(array('elementTd' => 'HtmlTag'), array('tag' => 'td')),
          array('Label', array('tag' => 'th')),
          array(array('elementTr' => 'HtmlTag'), array('tag' => 'tr', 'class'=>"snep_form_element"))
          ));
         */

        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();
            $form_isValid = $form->isValid($dados);

            if ($form_isValid) {

                $class = array('name' => $dados['name'],
                    'mode' => $dados['mode'],
                    'directory' => $dados['base'] . $dados['directory']);

                $originalName = $dados['originalName'];
                Snep_SoundFiles_Manager::editClass($originalName, $class);
                $this->_redirect($nameControll);
            }
        }

        $this->view->file = $data;
        $this->view->form = $form;
    }

    /**
     * removeAction - Remove section of music on hold
     */
    public function removeAction() {

        $nameControll = $this->getRequest()->getControllerName();

        $file = $this->_request->getParam('file');

        $this->view->class = Snep_SoundFiles_Manager::getClasse($file);
        $secao = Snep_SoundFiles_Manager::getAllSecao($file);

        if (count($secao) >= 1) {
            $this->view->message = $this->view->translate("You are removing a music on hold class, it has some audio files attached to it.");
        } else {
            $this->view->message = $this->view->translate("You want to remove this section?");
        }

        $form = new Snep_Form();

        $name = new Zend_Form_Element_Hidden('name');
        $name->setValue($file);
        $form->addElement($name);

        if (count($secao) >= 1) {
            $check = new Zend_Form_Element_Checkbox('remove');
            $check->setLabel($this->view->translate("Delete Sound Files?"));
            $check->setDecorators(array(
                        'ViewHelper',
                        'Description',
                        'Errors',
                        array(array('elementTd' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input')),
                        array('Label', array('tag' => 'div', 'class' => 'label')),
                        array(array('elementTr' => 'HtmlTag'), array('tag' => 'div', 'class' => 'line')),
                    ))
                    ->setAttrib('class', 'newcheck tolabel')->setValue(true);

            $form->addElement($check);
        }
        if ($this->_request->getPost()) {

            $dados = $this->_request->getParams();

            $form_isValid = $form->isValid($dados);

            if ($form_isValid) {
                if ($dados['remove']) {
                    $class = Snep_SoundFiles_Manager::getClasse($dados['name']);
                    Snep_SoundFiles_Manager::removeClass($class);
                    Snep_SoundFiles_Manager::removeClassSounds($class['name']);
                } elseif (count($secao) == 0) {
                    $class = Snep_SoundFiles_Manager::getClasse($dados['name']);
                    Snep_SoundFiles_Manager::removeClass($class);
                }
                $this->_redirect($nameControll);
            }
        }

        $this->view->form = $form;
    }
    
    /**
     * fileAction - sound file
     */
    public function fileAction() {

        $this->view->path_sound = $arquivo = Zend_Registry::get('config')->system->path->web . '/sounds/moh/';
        $baseURL = $this->getFrontController()->getBaseUrl();
        $nameControll = $this->getRequest()->getControllerName();
        $file = $this->_request->getParam('class');

        $this->view->url = $baseURL . "/" . $nameControll;


        $class = Snep_SoundFiles_Manager::getClasse($file);
        
        $this->view->files = Snep_SoundFiles_Manager::getClassFiles($class);

        $arrayInf = array('data' => null,
            'descricao' => $this->view->translate('Not Found'),
            'secao' => $class['name']);
        $errors = "";
        if (isset($this->view->files)) {
            foreach ($this->view->files as $file => $list) {
                if (!isset($list['arquivo'])) {
                    $arrayInf['arquivo'] = $file;
                    $this->view->files[$file] = $arrayInf;
                    (!isset($errors) ? $errors = "" : false);
                    $errors .= $this->view->translate("File {$file} not found") . "<br/>";
                }
            }
        }

        ( isset($errors) && $errors != "" ?
                        $this->view->error = array(
                    'error' => true,
                    'message' => $errors) : false);

        $this->view->filter = array(array("url" => "{$baseURL}/{$nameControll}/",
                "display" => $this->view->translate("Back"),
                "css" => "back"),
            array("url" => "{$baseURL}/{$nameControll}/addfile/class/{$class['name']}",
                "display" => $this->view->translate("Add File"),
                "css" => "include"),
        );
        $this->view->title = "Music on Hold Sessions";
    }
    
    /**
     * addFileAction - Add sound file in section
     */
    public function addfileAction() {

        $nameControll = $this->getRequest()->getControllerName();

        $className = $this->_request->getParam('class');

        $class = Snep_SoundFiles_Manager::getClasse($className);
        
        $form = new Snep_Form(new Zend_Config_Xml("modules/default/forms/sound_files.xml"));

        $file = new Zend_Form_Element_File('file');
        $file->setLabel($this->view->translate('Sound File'))
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
                ->setRequired(true);

        $form->addElement($file);

        $section = new Zend_Form_Element_Hidden('section');
        $section->setValue($class['name']);
        $form->addElement($section);

        $form->removeElement('type');

        if ($this->_request->getPost()) {

            $class = Snep_SoundFiles_Manager::getClasse($_POST['section']);
            
            $dados = $this->_request->getParams();
            $form_isValid = $form->isValid($dados);

            $invalid = array('â', 'ã', 'á', 'à', 'ẽ', 'é', 'è', 'ê', 'í', 'ì', 'ó', 'õ', 'ò', 'ú', 'ù', 'ç', " ", '@', '!');
            $valid = array('a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'o', 'o', 'o', 'u', 'u', 'c', "_", '_', '_');

            $originalName = str_replace($invalid, $valid, $_FILES['file']['name']);
            $files = Snep_SoundFiles_Manager::get($originalName);

            if ($class["name"] == $files["secao"]) {
                $file->addError($this->view->translate("File already exists"));
                $form_isValid = false;
            }
                        
            $valida = Snep_SoundFiles_Manager::getIndex($originalName,$class["name"]);
            if($valida != false){
               $file->addError($this->view->translate("File already exists"));
                $form_isValid = false; 
            }
          

            if ($form_isValid) {

                $uploadName = $_FILES['file']['tmp_name'];
                $arq_tmp = $class['directory'] . "/tmp/" . $originalName;
                $arq_dst = $class['directory'] . "/" . $originalName;
                $dir = $class['directory'] . "/tmp";
                $dest_dir = $class['directory'] . "/";

                exec("mv $uploadName $arq_tmp");

                if ($dados['gsm'] == 1) {
                    $dados['name'] = $_FILES['file']['name'];
                    $fileNe = $dir . '/' . basename($dados['name'], '.wav') . '.gsm';

                    exec("sox $arq_tmp -r 8000 -c 1 $fileNe resample -ql");
                    $originalName = basename($originalName, '.wav') . ".gsm";

                    $comando = "mv $fileNe $dest_dir";
                    $result = exec("$comando 2>&1", $out, $err);

                    exec("rm $arq_tmp");
                } else {
                    exec("sox $arq_tmp -r 8000 -c 1 -e signed-integer -b 16 $arq_dst");
                    $comando = "mv $arq_tmp $arq_dst";
                    $result = exec("$comando 2>&1", $out, $err);
                    exec("rm $arq_tmp");
                }

                if (isset($arq_dst) || isset($fileNe)) {

                    Snep_SoundFiles_Manager::
                    addClassFile(array('arquivo' => $originalName,
                        'descricao' => $dados['description'],
                        'data' => new Zend_Db_Expr('NOW()'),
                        'tipo' => 'MOH',
                        'secao' => $className));
                }

                $this->_redirect($nameControll . "/file/class/$className/");
            }
        }

        $this->view->form = $form;
        $this->view->class = $this->_request->getParam('class');
    }
    
    /**
     * edutfileACtion - Edit sound file in section
     */
    public function editfileAction() {

        $fileName = $this->_request->getParam('file');
        $class = $this->_request->getParam('class');



        $className = Snep_SoundFiles_Manager::getClasse($class);


        $files = Snep_SoundFiles_Manager::getClassFiles($className);


        $_files = array('arquivo' => '', 'descricao' => '',
            'tipo' => '', 'secao' => $class, 'full' => '');

        foreach ($files as $name => $file) {

            if ($fileName === $name) {
                if (isset($file['arquivo'])) {
                    $_files = $file;
                }
            }
        }

        $form = new Snep_Form(new Zend_Config_Xml("modules/default/forms/sound_files.xml"));
        $form->setAction($this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . "/editfile/file/$fileName/class/$class");

        $file = new Zend_Form_Element_File('file');
        $file->setLabel($this->view->translate('Sound File'))
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

        $form->getElement('description')->setValue($_files['descricao']);
        $form->getElement('filename')->setValue($_files['arquivo']);

        $section = new Zend_Form_Element_Hidden('section');
        $section->setValue($_files['secao']);
        $form->addElement($section);

        $originalName = new Zend_Form_Element_Hidden('originalName');
        $originalName->setValue($fileName);
        $form->addElement($originalName);

        $originalPath = new Zend_Form_Element_Hidden('originalPath');
        $originalPath->setValue($_files['full']);
        $form->addElement($originalPath);


        $form->removeElement('type');

        if ($this->_request->getPost()) {

            $class = Snep_SoundFiles_Manager::getClasse($_POST['section']);

            $dados = $this->_request->getParams();
            $form_isValid = $form->isValid($dados);

            $invalid = array('â', 'ã', 'á', 'à', 'ẽ', 'é', 'è', 'ê', 'í', 'ì', 'ó', 'õ', 'ò', 'ú', 'ù', 'ç', " ", '@', '!');
            $valid = array('a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'o', 'o', 'o', 'u', 'u', 'c', "_", '_', '_');

            if ($_FILES['file']['size'] > 0) {

                $oldName = $dados['originalName'];

                $originalName = str_replace($invalid, $valid, $_FILES['file']['name']);
                $files = Snep_SoundFiles_Manager::get($originalName);

                if ($form_isValid) {

                    $uploadName = $_FILES['file']['tmp_name'];
                    $arq_tmp = $class['directory'] . "/tmp/" . $oldName;
                    $arq_dst = $class['directory'] . "/" . $oldName;
                    $arq_bkp = $class['directory'] . "/backup/" . $oldName;
                    $arq_orig = $class['directory'] . "/" . $oldName;

                    exec("mv $uploadName $arq_tmp");
                    exec("mv $arq_orig $arq_bkp");

                    $fileNe = basename($arq_dst, '.wav');


                    if ($dados['gsm'] == 1) {
                        exec("sox $arq_tmp -r 8000 {$fileNe}.gsm");
                        $exists = file_exists($fileNe . "gsm");
                    } else {

                        exec("sox $arq_tmp -r 8000 -c 1 -e signed-integer -b 16 $arq_dst");
                        $exists = file_exists($arq_dst);
                    }
                }

                if ($exists) {

                    exec("rm -f {$arq_tmp}");
                    Snep_SoundFiles_Manager::remove($oldName);
                    Snep_SoundFiles_Manager::addClassFile(array(
                        'arquivo' => $oldName,
                        'descricao' => $dados['description'],
                        'data' => new Zend_Db_Expr('NOW()'),
                        'tipo' => 'MOH',
                        'secao' => $class['name'])
                    );
                }
            } else {
                $originalName = $dados['originalName'];
                Snep_SoundFiles_Manager::
                editClassFile(array('arquivo' => $originalName,
                    'descricao' => $dados['description'],
                    'data' => new Zend_Db_Expr('NOW()'),
                    'tipo' => 'MOH',
                    'secao' => $class['name']));
            }

            $this->_redirect($this->getRequest()->getControllerName() . "/file/class/{$className['name']}/");
        }

        $this->view->form = $form;
        $this->view->class = $this->_request->getParam('class');
    }
    
    /**
     * restoreAction 
     */
    public function restoreAction() {


        $nameControll = $this->getRequest()->getControllerName();
        $file = $this->_request->getParam('file');
        $class = $this->_request->getParam('class');

        $className = Snep_SoundFiles_Manager::getClasse($class);

        $local = $className['directory'] . '/';
        $arq_bkp = $local . "backup/";
        $arq_tmp = $local . "tmp/";
        exec("mv $local$file $arq_tmp");
        exec("mv $arq_bkp$file $local");
        exec("rm  $arq_tmp$file");


        $this->_redirect($nameControll . "/file/class/$class");
    }
    
    /**
     * removefileAction - remove sound file of section
     */
    public function removefileAction() {


        $nameControll = $this->getRequest()->getControllerName();
        $file = $this->_request->getParam('file');
        $class = $this->_request->getParam('class');

        $className = Snep_SoundFiles_Manager::getClasse($class);
        $files = Snep_SoundFiles_Manager::getClassFiles($className);

        foreach ($files as $name => $path) {
            if ($file == $name) {

                exec("rm {$path['full']} ");

                if (!file_exists($path['full'])) {
                    Snep_SoundFiles_Manager::remove($name, $path['secao']);
                }
            }
        }

        $this->_redirect($nameControll . "/file/class/$class");
    }

}
