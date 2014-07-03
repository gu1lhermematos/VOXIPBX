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
 *
 * Gerador de Barra do Topo
 *
 * @see Snep_Bar
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2012 OpenS Tecnologia
 * @author    Iago Uilian Berndt <iagouilian@gmail.com.br>
 *
 */
class Snep_Bar {

    /**
     * get - @return html da barra
     *
     * @param <string> $title Titulo.
     * @param <string> $option_title Titulo do link.
     * @param <string> $option_link href do link.
     * @param <string> $class classe da barra.
     * @param <string> $html html a ser exibido no centro.
     */
    public static function get($title = "", $option_title = "", $option_link = "", $class = "add", $html = "") {
        $i18n = Zend_Registry::get("i18n");
        Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('layout')->getView()->headTitle()->append($i18n->translate(strval($title)));
        return "<div class='barTop'>" .
                "<div class='$class'>" .
                "<div class='title'>" .
                "<div class='icon'></div>" .
                "<span>" . $i18n->translate(strval($title)) . "</span>" .
                "</div>" .
                ($option_title ?
                        "<a href='$option_link'><div class='option'><div class='icon'></div><span>" . $i18n->translate(strval($option_title)) . "</span></div></a>" : '') .
                "<div class='html'>$html</div>" .
                "</div>" .
                "</div>";
    }

}

?>
