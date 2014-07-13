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
 * @see Snep_Form_Element_Codec
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia 
 */
class Snep_Form_Element_Codec extends Zend_Form_Element_Select {

    public function __construct($spec, $options = null) {
        $this->addMultiOptions(array(
            "ulaw" => "ulaw",
            "alaw" => "alaw",
            "ilbc" => "ilbc",
            "g729" => "g729",
            "gsm" => "gsm",
            "h264" => "h264",
            "h263" => "h263",
            "h263p" => "h263p"
        ));
        parent::__construct($spec, $options);
    }
}
