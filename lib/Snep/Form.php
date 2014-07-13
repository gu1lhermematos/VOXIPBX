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
require_once 'Zend/Form.php';

/**
 * @see Snep_Form
 * 
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2012 OpenS Tecnologia
 */
class Snep_Form extends Zend_Form {

    public function __construct($options = null) {
        $this->addPrefixPath('Snep_Form', 'Snep/Form');
        parent::__construct($options);

        $this->setElementDecorators(array(
            'ViewHelper',
            'Description',
            'Errors',
            array(array('elementTd' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input')),
            array('Label', array('tag' => 'div', 'class' => 'label')),
            array(array('elementTr' => 'HtmlTag'), array('tag' => 'div', 'class' => 'line')),
        ));

        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'div')),
            array('Form', array('class' => 'snep_form'))
        ));
        $this->setAttrib('id', 'snep_form');

        $i18n = Zend_Registry::get("i18n");

        $submit = new Snep_Form_Element_Submit("submit", array("label" => $i18n->translate("Save")));
        $submit->setOrder(1000);
        $this->addElement($submit);
    }

    /**
     * setSelectBox - Inserts two selections and buttons to control the elements between them.
     *
     * @param <string> $name - Define elements id. Important to javascript interaction
     * @param <string> $label
     * @param <array> $start_itens
     * @param <array> $end_itens
     */
    public function setSelectBox($name, $label, $start_itens, $end_itens = false, $class = 'multiselect') {

        $i18n = Zend_Registry::get("i18n");

        $join = $start_itens;
        if (is_array($end_itens))
            foreach ($end_itens as $key => $value)
                $join[$key] = $value;

        $box = new Zend_Form_Element_Multiselect("box_add");
        $box->setLabel($i18n->translate($label))
                ->setMultiOptions($join)
                ->setAttrib('id', $name . '_box_add')
                ->setAttrib('class', $class)
                ->setRegisterInArrayValidator(false)
                ->addDecorator('HtmlTag', array('tag' => 'div'))
                ->addDecorator('Label', array('tag' => 'div', 'class' => 'label'));

        if (is_array($end_itens))
            $box->setValue(array_keys($end_itens));


        $this->addElements(array($box));
    }
    
    /**
     * setDefaultDecorators
     */
    public function setDefaultDecorators() {
        $this->setElementDecorators(array(
            'ViewHelper',
            'Description',
            'Errors',
            array(array('elementTd' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input')),
            array('Label', array('tag' => 'div', 'class' => 'label')),
            array(array('elementTr' => 'HtmlTag'), array('tag' => 'div', 'class' => 'line')),
        ));

        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'div')),
            array('Form', array('class' => 'snep_form'))
        ));
    }

}

