<?php

/**
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as
 *  published by the Free Software Foundation, either version 3 of
 *  the License, or (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/lgpl.txt>.
 */

/**
 * Classe to manager a Carrier.
 *
 * @see Snep_SoundFiles_Manager
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti <rafael@opens.com.br>
 * 
 */
class Snep_SoundFiles_Manager {

    public function __construct() {
        
    }

    /**
     * getAll - Get all carrier
     */
    public function getAll() {

        $db = Zend_registry::get('db');

        $select = $db->select()
                ->from("operadoras");

        $stmt = $db->query($select);
        $carrier = $stmt->fetchAll();

        return $carrier;
    }
    
    /**
     * getAllSecao
     * @param <string> $file
     * @return <boolean>
     */
    public function getAllSecao($file) {

        $db = Zend_registry::get('db');

        $select = $db->select()
                ->from('sounds')
                ->where("sounds.secao = ?", $file);

        try {
            $stmt = $db->query($select);
            $carrier = $stmt->fetchAll();
        } catch (Exception $e) {
            return false;
        }

        return $carrier;
    }

    /**
     * get - Get a carrier by id
     * @param <int> $id
     * @return <Array>
     */
    public function get($file) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('sounds')
                ->where("sounds.arquivo = ?", $file);

        try {
            $stmt = $db->query($select);
            $sound = $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }

        return $sound;
    }
    
    /**
     * getIndex - Get a carrier by id
     * @param <string> $file
     * @param <string> $sessao
     * @return <array>
     */
    public function getIndex($file,$sessao) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('sounds')
                ->where("sounds.arquivo = ?", $file)
                ->where("sounds.secao = ?", $sessao);

        try {
            $stmt = $db->query($select);
            $sound = $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }

        return $sound;
    }

    /**
     * add - Add a sound file
     * @param <array> $file
     */
    public function add($file) {

        $db = Zend_Registry::get('db');

        $insert_data = array('arquivo' => $file['arquivo'],
            'descricao' => $file['descricao'],
            'data' => new Zend_Db_Expr('NOW()'),
            'tipo' => $file['tipo']);

        $db->insert('sounds', $insert_data);

        return $db->lastInsertId();
    }

    /**
     * remove - Remove a Sound File register
     * @param <string> $file
     * @param <boolean> $class
     */
    public function remove($file, $class = false) {

        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        if ($class) {
            $db->delete('sounds', "arquivo = '$file' and secao = '$class'");
        } else {
            $db->delete('sounds', "arquivo = '$file'");
        }

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }
    
    /**
     * addClass
     * @param <string> $class
     * @return <boolean>
     */
    public function addClass($class) {

        $classes = self::getClasses();
        $classes[$class['name']] = $class;


        $header = ";----------------------------------------------------------------\n ";
        $header .= "; Arquivo: snep-musiconhold.conf - Cadastro Musicas de Espera    \n";
        $header .="; Sintaxe: [secao]                                               \n";
        $header .=";          mode=modo de leitura do(s) arquivo(s)                 \n";
        $header .=";          directory=onde estao os arquivos                      \n";
        $header .= ";          [application=aplicativo pra tocar o som] - opcional   \n";
        $header .= "; Include: em /etc/asterisk/musiconhold.conf                     \n";
        $header .= ";          include /etc/asterisk/snep/snep-musiconhold.conf      \n";
        $header .= "; Atualizado em: 26/05/2008 11:22:18                             \n";
        $header .= "; Copyright(c) 2008 Opens Tecnologia                             \n";
        $header .= ";----------------------------------------------------------------\n";
        $header .= "; Os registros a Seguir sao gerados pelo Software SNEP.          \n";
        $header .= "; Este Arquivo NAO DEVE ser editado Manualmente sob riscos de    \n";
        $header .= "; causar mau funcionamento do Asterisk                           \n";
        $header .= ";----------------------------------------------------------------\n\n";

        $body = '';
        foreach ($classes as $classe) {
            $body .= "[" . $classe['name'] . "] \n";
            $body .= "mode=" . $classe['mode'] . "\n";
            $body .= "directory=" . $classe['directory'] . "\n\n";
        }

        if (!file_exists($class['directory'])) {

            exec("mkdir {$class['directory']}");
            exec("mkdir {$class['directory']}/tmp");
            exec("mkdir {$class['directory']}/backup");

            file_put_contents('/etc/asterisk/snep/snep-musiconhold.conf', $header . $body);

            return true;
        }
        return false;
    }
    
    /**
     * syncFiles
     * @return <boolean>
     */
    public function syncFiles() {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('sounds')
                ->where('sounds.tipo = ?', 'MOH');

        try {
            $stmt = $db->query($select);
            $sounds = $stmt->fetchAll();
        } catch (Exception $e) {
            return false;
        }

        $_sound = array();
        foreach ($sounds as $sound) {
            $_sound[$sound['arquivo']] = $sound['arquivo'];
        }


        $allClasses = Snep_SoundFiles_Manager::getClasses();
        $classesFolder = array();

        foreach ($allClasses as $id => $xclass) {


            $classesFolder[$id]['name'] = $xclass['name'];
            $classesFolder[$id]['directory'] = $xclass['directory'];

            if (file_exists($xclass['directory'])) {

                $allFiles = array();
                $files = array();
                foreach (scandir($xclass['directory']) as $thisClass => $file) {

                    if (!preg_match("/^\.+.*/", $file)) {

                        if (!preg_match('/^tmp+.*/', $file)) {

                            if (!preg_match('/^backup+.*/', $file)) {

                                if (!in_array($file, array_keys($allClasses))) {

                                    if (!in_array($file, $_sound)) {

                                        $newfile = array('arquivo' => $file,
                                            'descricao' => $file,
                                            'data' => new Zend_Db_Expr('NOW()'),
                                            'tipo' => 'MOH',
                                            'secao' => $id);


                                        Snep_SoundFiles_Manager::addClassFile($newfile);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }
    
    /**
     * getClasses
     * @return <array> $_section
     */
    public function getClasses() {

        $sections = new Zend_Config_Ini('/etc/asterisk/snep/snep-musiconhold.conf');
        $_section = array();
        foreach ($sections->toArray() as $class => $info) {
            $_section[$class]['name'] = $class;
            $_section[$class]['mode'] = $info['mode'];
            $_section[$class]['directory'] = $info['directory'];
        }

        return $_section;
    }
    
    /**
     * getClasse
     * @param <string> $name
     * @return <array> $_section
     */
    public function getClasse($name) {

        $sections = new Zend_Config_Ini('/etc/asterisk/snep/snep-musiconhold.conf');
        $_section = array();
        foreach ($sections->toArray() as $class => $info) {

            if ($class == $name) {
                $_section['name'] = $class;
                $_section['mode'] = $info['mode'];
                $_section['directory'] = $info['directory'];
            }
        }

        return $_section;
    }

    /**
     * addClassFile - Add a sound file
     * @param <array> $file
     */
    public function addClassFile($file) {

        $db = Zend_Registry::get('db');

        $insert_data = array('arquivo' => $file['arquivo'],
            'descricao' => $file['descricao'],
            'data' => new Zend_Db_Expr('NOW()'),
            'tipo' => 'MOH',
            'secao' => $file['secao']);

        try {

            $db->insert('sounds', $insert_data);
        } catch (Exception $e) {

            return false;
        }


        return $db->lastInsertId();
    }

    /**
     * editClassFile
     * @param <array> $file
     * @return <boolean>
     */
    public function editClassFile($file) {

        $db = Zend_Registry::get('db');

        $insert_data = array('arquivo' => $file['arquivo'],
            'descricao' => $file['descricao'],
            'data' => new Zend_Db_Expr('NOW()'),
            'tipo' => 'MOH',
            'secao' => $file['secao']);

        try {
            $db->update('sounds', $insert_data, "arquivo='{$file['arquivo']}' and secao='{$file['secao']}'");
        } catch (Exception $e) {

            return false;
        }

        return $db->lastInsertId();
    }
    
    /**
     * getClassFiles
     * @param <string> $class
     * @return <array> $resultado
     */
    public function getClassFiles($class) {

        $allClasses = Snep_SoundFiles_Manager::getClasses();
        
        $classesFolder = array();



        foreach ($allClasses as $id => $xclass) {
            $classesFolder[$id] = $id;
        }
        
        if (file_exists($class['directory'])) {
            $allFiles = array();
            $files = array();
            foreach (scandir($class['directory']) as $file) {

                if (!preg_match("/^\.+.*/", $file) && !in_array($file, $classesFolder)) {

                    if (preg_match("/^backup+.*/", $file)) {

                        foreach (scandir($class['directory'] . '/' . $file) as $backup) {
                            
                            if (!preg_match("/^\.+.*/", $backup)) {
                                
                                //        $files[] = $class['directory'] .'/backup/'. $backup;
                            }
                        }
                    } elseif (preg_match("/^tmp+.*/", $file)) {

                        foreach (scandir($class['directory'] . '/' . $file) as $tmp) {
                            if (!preg_match("/^\.+.*/", $tmp)) {
                                //       $files[] = $class['directory'] .'/tmp/'. $tmp;
                            }
                        }
                    } else {
                        $files[$file] = $class['directory'] . '/' . $file;
                        //$allFiles[$file] = $file;
                    }
                }
            }

            $sessao = $class['name'];
            
            $resultado = array();
            foreach ($files as $name => $file) {
                $ultimas = substr($file, -4);
                if ($ultimas == ".gsm" || $ultimas == ".wav") {
                    
                    $resultado[$name] = Snep_SoundFiles_Manager::getIndex($name,$sessao);
                    $resultado[$name]['full'] = $file;
                    $pasta = basename($class['directory']);
                    $resultado[$name]['pasta'] = $pasta;
                }
            }


            return $resultado;
        }
    }

    /**
     * removeClassSounds - Remove a Sound File register
     * @param <string> $class
     */
    public function removeClassSounds($class) {

        $db = Zend_Registry::get('db');

        $db->beginTransaction();

        $db->delete('sounds', "secao = '$class'");

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }
    
    /**
     * editClass
     * @param <string> $originalName
     * @param <array> $newClass
     * @return <boolean>
     */
    public function editClass($originalName, $newClass) {

        $classes = self::getClasses();

        $directory = '';
        foreach ($classes as $class => $item) {

            if ($originalName == $item['name']) {
                $classes[$class]['name'] = $newClass['name'];
                $classes[$class]['mode'] = $newClass['mode'];
                $directory = $classes[$class]['directory'];
                $classes[$class]['directory'] = $newClass['directory'];
            }
        }



        $header = ";----------------------------------------------------------------\n ";
        $header .= "; Arquivo: snep-musiconhold.conf - Cadastro Musicas de Espera    \n";
        $header .="; Sintaxe: [secao]                                               \n";
        $header .=";          mode=modo de leitura do(s) arquivo(s)                 \n";
        $header .=";          directory=onde estao os arquivos                      \n";
        $header .= ";          [application=aplicativo pra tocar o som] - opcional   \n";
        $header .= "; Include: em /etc/asterisk/musiconhold.conf                     \n";
        $header .= ";          include /etc/asterisk/snep/snep-musiconhold.conf      \n";
        $header .= "; Atualizado em: 26/05/2008 11:22:18                             \n";
        $header .= "; Copyright(c) 2008 Opens Tecnologia                             \n";
        $header .= ";----------------------------------------------------------------\n";
        $header .= "; Os registros a Seguir sao gerados pelo Software SNEP.          \n";
        $header .= "; Este Arquivo NAO DEVE ser editado Manualmente sob riscos de    \n";
        $header .= "; causar mau funcionamento do Asterisk                           \n";
        $header .= ";----------------------------------------------------------------\n\n";

        $body = '';
        foreach ($classes as $classe) {
            $body .= "[" . $classe['name'] . "] \n";
            $body .= "mode=" . $classe['mode'] . "\n";
            $body .= "directory=" . $classe['directory'] . "\n\n";
        }

        if (!file_exists($newClass['directory'])) {

            exec("mkdir {$newClass['directory']}");
            exec("mkdir {$newClass['directory']}/tmp");
            exec("mkdir {$newClass['directory']}/backup; ");

            exec("cp  {$directory}/* {$newClass['directory']}/");
            exec("cp  {$directory}/tmp/* {$newClass['directory']}/tmp/");
            exec("cp  {$directory}/backup/* {$newClass['directory']}/backup/");

            exec("rm -rf {$directory}");

            file_put_contents('/etc/asterisk/snep/snep-musiconhold.conf', $header . $body);

            return true;
        }
        return false;
    }
    
    /**
     * removeClass
     * @param <array> $classRemove
     */
    public function removeClass($classRemove) {


        $classes = self::getClasses();

        $directory = '';
        foreach ($classes as $class => $item) {

            if ($classRemove['name'] == $item['name']) {

                if (file_exists($classRemove['directory'])) {
                    exec("rm -rf {$classRemove['directory']}");
                }
                unset($classes[$class]);
            }
        }

        $header = ";----------------------------------------------------------------\n ";
        $header .= "; Arquivo: snep-musiconhold.conf - Cadastro Musicas de Espera    \n";
        $header .="; Sintaxe: [secao]                                               \n";
        $header .=";          mode=modo de leitura do(s) arquivo(s)                 \n";
        $header .=";          directory=onde estao os arquivos                      \n";
        $header .= ";          [application=aplicativo pra tocar o som] - opcional   \n";
        $header .= "; Include: em /etc/asterisk/musiconhold.conf                     \n";
        $header .= ";          include /etc/asterisk/snep/snep-musiconhold.conf      \n";
        $header .= "; Atualizado em: 26/05/2008 11:22:18                             \n";
        $header .= "; Copyright(c) 2008 Opens Tecnologia                             \n";
        $header .= ";----------------------------------------------------------------\n";
        $header .= "; Os registros a Seguir sao gerados pelo Software SNEP.          \n";
        $header .= "; Este Arquivo NAO DEVE ser editado Manualmente sob riscos de    \n";
        $header .= "; causar mau funcionamento do Asterisk                           \n";
        $header .= ";----------------------------------------------------------------\n\n";

        $body = '';
        foreach ($classes as $classe) {
            $body .= "[" . $classe['name'] . "] \n";
            $body .= "mode=" . $classe['mode'] . "\n";
            $body .= "directory=" . $classe['directory'] . "\n\n";
        }

        file_put_contents('/etc/asterisk/snep/snep-musiconhold.conf', $header . $body);
    }

    /**
     * edit - Update a carrier data
     * @param <Array> $file
     */
    public function edit($file) {

        $db = Zend_Registry::get('db');

        $update_data = array('descricao' => $file['description'],
            'tipo' => 'AST');

        $db->update("sounds", $update_data, "arquivo = '{$file['filename']}'");
    }

    /**
     * setCostCenter - Set CostCenter to Carrier
     * @param <int> $idCarrier
     * @param <int> $costCenter
     */
    public function setCostCenter($idCarrier, $costCenter) {

        $db = Zend_Registry::get('db');

        $db->insert('oper_ccustos', array('operadora' => $idCarrier,
            'ccustos' => $costCenter));
    }
    
    /**
     * verifySoundfiles
     * @param <string> $name
     * @param <boolean> $full
     * @return <string>
     */
    public function verifySoundFiles($name, $full = false) {

        $sound_path = Zend_Registry::get('config')->system->path->asterisk->sounds;
        $web_path = Zend_Registry::get('config')->system->path->web;
        $sound_path1 = "/var/lib/asterisk/sounds/";
        $result = array();
        if (file_exists($sound_path)) {

            if (file_exists($sound_path . '/' . $name)) {
                if ($full) {
                    $result['fullpath'] = $sound_path . '/' . $name;
                } else {
                    $result['fullpath'] = $web_path . '/sounds/pt_BR/' . $name;
                }
            }
            if (file_exists($sound_path . '/backup/' . $name)) {
                if ($full) {
                    $result['backuppath'] = $sound_path . '/backup/' . $name;
                } else {
                    $result['backuppath'] = $web_path . '/sounds/pt_BR/backup/' . $name;
                }
            }
            if (file_exists($sound_path1 . '/' . $name)) {
                if ($full) {
                    $result['fullpath'] = $sound_path1 . '/' . $name;
                } else {
                    $result['fullpath'] = $web_path . '/sounds/' . $name;
                }
            }
        }

        return $result;
    }

}
