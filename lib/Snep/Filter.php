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
 * @see Snep_Filter
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2012 OpenS Tecnologia
 * @author    Iago Uilian Berndt <iagouilian@gmail.com.com.br>
 *
 */
class Snep_Filter {
	
	public static function setSelect($select, $opcoes, $request) {
		if ($request->getPost('filtro')) $filtro = $request->getPost('filtro');
		elseif($request->getParam('filter')) $filtro = $request->getParam('filter');
		else return;
		
        $query = mysql_escape_string( $filtro );
		foreach ($opcoes as $key=>$value)$opcoes[$key] = " `$value` like '%$query%' ";
        $select->where(implode($opcoes, " OR "));
        return $filtro;
	}
}