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
 * @see Snep_Limit
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2012 OpenS Tecnologia
 * @author    Iago Uilian Berndt <iagouilian@gmail.com.com.br>
 *
 */
class Snep_Limit {

    /**
     * get
     * @param <object> $request
     * @return <string> $limit
     */
    public static function get($request) {
        $limit = "";
        if ($request->getPost('campo'))
            $limit = $request->getPost('campo');
        elseif ($request->getParam('limit'))
            $limit = $request->getParam('limit');
        if ($limit == 35)
            ;
        elseif ($limit == 50)
            ;
        elseif ($limit == 10)
            ;
        else
            $limit = Zend_Registry::get('config')->ambiente->linelimit;
        return $limit;
    }

}