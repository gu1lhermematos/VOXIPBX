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
 * @see Snep_Form_Element_ExtensionGroup
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia 
 */
class Snep_Form_Element_ExtensionGroup extends Zend_Form_Element_Select {
    public function __construct($spec, $options = null) {
        $groups = new Snep_GruposRamais();
        $data = array();
        foreach ($groups->getAll() as $group) {
            $data[$group['name']] = $group['name'];
        }
        $this->addMultiOptions($data);
        parent::__construct($spec, $options);
    }
}
