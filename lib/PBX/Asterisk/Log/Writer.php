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
require_once "Zend/Log/Writer/Abstract.php";

/**
 * Description of Writer
 *
 * @category  Snep
 * @package   PBX_Asterisk
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Henrique Grolli Bassotto
 */
class PBX_Asterisk_Log_Writer extends Zend_Log_Writer_Abstract {

    /**
     * @var Asterisk_AGI $asterisk interface de comunicação com o asterisk
     */
    protected $asterisk;

    /**
     * __construct - Construtor
     * @param Asterisk_AGI $asterisk interface de comunicação com o asterisk
     */
    public function __construct($asterisk) {
        $this->asterisk = $asterisk;

        $this->_formatter = new Zend_Log_Formatter_Simple();
    }

    /**
     * factory - Construct a Zend_Log driver
     * @param  array|Zen_Config $config
     * @return Zend_Log_FactoryInterface
     */
    public static function factory($config) {
        throw new Exception("NOT SUPPORTED");
    }

    /**
     * _write - Escreve uma mensagem no CLI do asterisk
     * @param  <array>  $event  log data event
     * @return void
     */
    protected function _write($event) {
        $line = $this->_formatter->format($event);

        $line = trim($line, "\n"); // Removendo quebras de linha a mais
        $line = str_replace('"', '\"', $line); // Escaping "

        $this->asterisk->verbose($line);
    }

}
