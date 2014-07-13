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
 * @see Snep_Vinculos
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2012 OpenS Tecnologia
 * @author    Iago Uilian Berndt <iagouilian@gmail.com.com.br>
 *
 */
class Snep_Buttons {    
	
    public $options;
    public function __construct() {}
    public function __destruct() {}
    public function __clone() {}

    public function addOption($model, $link, $isIdForSubmit = false, $name = false){
    	$this->options[] = Array('model'=>$model, 'link'=> $link, 'isIdForSubmit'=> $isIdForSubmit, 'name'=>$name);
    }
    public function getHtml(){
    	$i18n = Zend_Registry::get("i18n");
    	$html = "<div class='buttons'>";
    	$xml = simplexml_load_file('configs/buttonsOptions.xml');
    	$modelos = Array();
    	foreach($xml->children() as $child) foreach($child->children() as $child2) $modelos[$child->getName()][$child2->getName()] = $child2;
    	foreach($this->options as $option){
    		if($option['isIdForSubmit'])$html .= "<a href='#' onclick='$(\"#$option[link]\").submit();' class='option'>";
    		else $html .= "<a href='$option[link]' class='option'>";
    		$html .= "<img src='".str_replace("/index.php", "", Zend_Controller_Front::getInstance()->getBaseUrl())."/modules/default/img/".$modelos[$option['model']]['icon']."'/><span>".$i18n->translate(strval($option['name']?$option['name']:$modelos[$option['model']]['name']))."</span></a>";
    	}
    	return $html."</div>";
    }
    
}

?>
