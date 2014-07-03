<?php

/**
 *  This file is part of SNEP.
 *  Para território Brasileiro leia LICENCA_BR.txt
 *  All other countries read the following disclaimer
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
 * Classe to manager a Permission.
 *
 * @see Snep_Permission_Manager
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Iago Uilian Berndt <iagouilian@gmail.com>
 *
 */
class Snep_Permission_Manager {

    public function __construct() {
        
    }

    /**
     * getAllGroup - Method to get all permission allow of the group
     * @param <string> $group
     * @return <array>
     */
    public static function getAllGroup($group) {

        $db = Zend_registry::get('db');

        $select = $db->select()
                ->from("group_resource", array("id_resource"))
                ->where('id_group = ?', $group)
                ->where('allow = ?', 1);

        $stmt = $db->query($select);
        $fetch = $stmt->fetchAll();

        $result = array();
        foreach ($fetch as $value)
            $result[] = $value['id_resource'];
        return $result;
    }

    /**
     * update - Method to add update all resources.
     * @param <array> $resources
     * @param <string> $group
     */
    public static function update($resources, $group) {

        $db = Zend_Registry::get('db');
        $db->delete("group_resource", "id_group = '$group'");
        foreach ($resources as $key => $value) {
            $db->insert('group_resource', array(
                "id_resource" => $key,
                "id_group" => $group,
                "allow" => $value
                    )
            );
        }
    }

    /**
     * get - Method to get permission
     * @param <string> $group
     * @param <string> $resource
     * @return <array>
     */
    public static function get($group, $resource) {

        $db = Zend_registry::get('db');

        $select = $db->select()
                ->from("group_resource", array("id_resource", "allow"))
                ->where('id_group = ?', $group)
                ->where('id_resource = ?', $resource);

        $stmt = $db->query($select);
        return $stmt->fetch();
    }

    /**
     * getAll - Retorna todos os resources dos modulos.
     * @return <array>
     */
    public static function getAll() {

        return Snep_Modules::$resources;

        /*
          $resources = array();
          $dir = getcwd() . "/modules";
          $point = opendir($dir);

          while ($file = readdir($point)) {

          if (is_file("modules/" . $file . "/protected.xml")) {

          $xml = simplexml_load_file("modules/" . $file . "/protected.xml");
          $xmlAttr = $xml->attributes();
          $module = (string)$xmlAttr['id'];
          $resources[$module]['label'] = (string)$xmlAttr['label'];
          foreach ($xml->children() as $controller) {
          $controllerAttr = $controller->attributes();
          $resources[$module]['controllers'][(string)$controllerAttr['id']]['label'] = (string)$controllerAttr['label'];

          foreach ($controller->children() as $action) {
          $resources[$module]['controllers'][(string)$controllerAttr['id']]['actions'][(string)$action['id']] = (string)$action['label'];

          }
          }
          }


          }
          return $resources; */
    }

    /**
     * checkExistenceCurrentResource - Verifica se o resource atual existe.
     * @return <booleano>
     */
    public static function checkExistenceCurrentResource() {

        $resources = Snep_Modules::$resources;
        $request = Zend_Controller_Front::getInstance()->getRequest();

        if (!isset($resources[$request->getModuleName()]))
            return false;
        if (!isset($resources[$request->getModuleName()][$request->getControllerName()]))
            return false;
        if (!isset($resources[$request->getModuleName()][$request->getControllerName()][$request->getActionName()]))
            return false;
        return true;

        /*
          if (is_file("modules/" . $request->getModuleName() . "/protected.xml")) {

          $xml = simplexml_load_file("modules/" . $request->getModuleName() . "/protected.xml");

          foreach ($xml->children() as $controller) {
          $controllerAttr = $controller->attributes();
          if($controllerAttr['id'] == (string)$request->getControllerName()){

          foreach ($controller->children() as $action) {
          if($action['id'] == (string)$request->getActionName()) return true;
          }
          }
          }

          }
          return false; */
    }

}