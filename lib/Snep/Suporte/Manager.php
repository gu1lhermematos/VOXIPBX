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
 * Class to manager Suporte
 *
 * @see Snep_Suporte_Manager
 *
 * @category  Snep
 * @package   Integrator
 * @copyright Copyright (c) 2012 OpenS Tecnologia
 * @author    Johnny Ewert <johnny@thesource.com.br>
 *
 */
class Snep_Suporte_Manager {

    /**
     * getDefault - Get all names of integrator
     * @return type
     */
    public static function getDefault() {

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from(array("integrador"), array("name as nome", "city", "state", "address", "cep", "phone_1", "phone_2", "cell_1", "email"));

        $stmts = $db->query($select);
        $result = $stmts->fetchAll();

        $integrador = $result['0'];

        return $integrador;
    }

    /**
     * getIntegrador
     * @return <string> $integrador
     */
    public static function getIntegrador() {

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from(array("integrador"), array("name as nome", "city", "state", "address", "cep", "phone_1", "phone_2", "cell_1", "email"));

        $stmts = $db->query($select);
        $result = $stmts->fetchAll();

        $cont_integrador = count($result);

        if ($cont_integrador < 2) {

            $integrador = self::getDefault();
        } else {
            $integrador = $result['1'];
        }
        return $integrador;
    }

}
