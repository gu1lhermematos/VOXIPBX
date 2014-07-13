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
 * Classe que abstrai os Vínculos
 *
 * @see Snep_SubMenu
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2012 OpenS Tecnologia
 * @author    Iago Uilian Berndt <iagouilian@gmail.com.com.br>
 *
 */
class Snep_SubMenu {

    /**
     * get
     * @return <string> $html
     */
    public function get() {
        $i18n = Zend_Registry::get("i18n");
        $html = "";
        $dirimg = str_replace("/index.php", "", Zend_Controller_Front::getInstance()->getBaseUrl()) . "/modules/default/img/";
        $dirlink = Zend_Controller_Front::getInstance()->getBaseUrl() . "/";
        $xml = simplexml_load_file('configs/subMenu.xml');
        foreach ($xml->children() as $menu) {
            $html.="<li><a href='" . ($menu->link ? "$dirlink" . $menu->link : '#') . "'>" . $i18n->translate(strval($menu->title)) . "</a><ul>";
            $icon = $dirimg . $menu->default_icon;
            foreach ($menu->sub as $sub) {
                $html.="<li><a href='" . $dirlink . $sub->link . "' title='" . $i18n->translate(strval($menu->default_icon_title)) . "'>" . $i18n->translate(strval($sub->title)) . "<img src='$icon'/></a>";
                foreach ($sub->option as $option) {
                    $html .= "<a href='" . $dirlink . $option->link . "' title='" . $i18n->translate(strval($option->icon_title)) . "'><img src='" . $dirimg . $option->icon . "'/></a>";
                }
                $html.="<a href='" . $dirlink . $sub->link . "'><span>" . $i18n->translate(strval($sub->desc)) . "</span></a></li>";
            }
            $html.="</ul></li>";
        }
        return $html;
    }

    public function __destruct() {
        
    }

    public function __clone() {
        
    }

    /**
     * getModules
     * @return <string> $html
     */
    public static function getModules() {
        $modulos = Snep_Menu::getMasterInstance()->getChildren();
        $i18n = Zend_Registry::get("i18n");
        $html = "";
        foreach ($modulos as $module) {
            $explode = explode("_", $module->getId());
            if ($explode[0] != "default") {
                $html .=
                        '<li>' .
                        '<a href="#" title="' . $i18n->translate($module->getLabel()) . '">' . $i18n->translate($module->getLabel()) . '</a>' .
                        '<ul class="moduleRoutines">';

                foreach ($module->getChildren() as $item) {
                    $html .=
                            '<li><a href="' . $item->getUri() . '">' . $i18n->translate($item->getLabel()) . '</a></li>';
                }
                $html .=
                        '</ul>' .
                        '</li>';
            }
        }
        return $html;
    }

}

?>
