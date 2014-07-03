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
 * Classe que abstrai as tarifas
 *
 * @see Snep_Tarifas
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti <rafael@opens.com.br>
 * 
 */
class Snep_Cnl {

    public function __construct() {
        
    }

    public function __destruct() {
        
    }

    public function __clone() {
        
    }

    /**
     * delPrefixo - Delete data in table ars_prefixo
     * @return type
     * @throws Exception
     */
    public static function delPrefixo() {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {

            $db->delete('ars_prefixo');
            $db->commit();
        } catch (Exception $ex) {

            $db->rollBack();
            throw $ex;
        }
        return;
    }

    /**
     * delPrefixoMovel - Delete data in table ars_mobile
     * @throws Exception
     */
    public static function delPrefixoMovel() {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {

            $db->delete('ars_mobile');
            $db->commit();
        } catch (Exception $ex) {

            $db->rollBack();
            throw $ex;
        }
        return;
    }
    
    /**
     * delCidade - Delete data in table ars_cidade
     * @return type
     * @throws Exception
     */
    public static function delCidade() {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {

            $db->delete('ars_cidade');
            $db->commit();
        } catch (Exception $ex) {

            $db->rollBack();
            throw $ex;
        }
        return;
    }

    /**
     * delDDD - Delete data in table ars_ddd
     * @return type
     * @throws Exception
     */
    public static function delDDD() {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {

            $db->delete('ars_ddd');
            $db->commit();
        } catch (Exception $ex) {

            $db->rollBack();
            throw $ex;
        }
        return;
    }

    /**
     * delOperadora - Delete data in table ars_operadora
     * @return type
     * @throws Exception
     */
    public static function delOperadora() {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {

            $db->delete('ars_operadora');
            $db->commit();
        } catch (Exception $ex) {

            $db->rollBack();
            throw $ex;
        }
        return;
    }
    
     /**
     * delOperadoraMovel - Delete data in table ars_mobile_carrier
     * @throws Exception
     */
    public static function delOperadoraMovel() {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {

            $db->delete('ars_mobile_carrier');
            $db->commit();
        } catch (Exception $ex) {

            $db->rollBack();
            throw $ex;
        }
        return;
    }

    /**
     * addoperadora - Add data in table ars_operadora
     * @param <int> $id
     * @param <string> $data
     * @throws Exception
     */
    public static function addOperadora($id, $data) {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {

            $operadora = array('id' => $id, 'name' => $data);
            $db->insert('ars_operadora', $operadora);
            $db->commit();
        } catch (Exception $ex) {

            $db->rollBack();
            throw $ex;
        }
    }

    /**
     * addDDD - Add data in table ars_ddd
     * @param <intd> $cod
     * @param <string> $estado
     * @param <string> $cidade
     * @throws Exception
     */
    public static function addDDD($cod, $estado, $cidade) {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {

            $ddd = array('cod' => $cod, 'estado' => $estado, 'cidade' => $cidade);
            $db->insert('ars_ddd', $ddd);

            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
        }
    }

    /**
     * addCidade - Add data in table ars_cidade
     * @param <string> $name
     * @return type
     * @throws Exception
     */
    public static function addCidade($name) {

        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {

            $cidade = array('name' => $name);
            $db->insert('ars_cidade', $cidade);
            $id = $db->lastInsertId();

            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
        }
        return $id;
    }

    /**
     * addoprefixo - Add data in table ars_prefixo
     * @param <int> $prefixo
     * @param <string> $cidade
     * @param <string> $operadora
     * @throws Exception
     */
    public static function addPrefixo($prefixo, $cidade, $operadora) {//600, '23', 4
        $db = Zend_Registry::get('db');
        $db->beginTransaction();

        try {

            $addPrefixo = array('prefixo' => $prefixo, 'cidade' => $cidade, 'operadora' => $operadora);
            $db->insert('ars_prefixo', $addPrefixo);

            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
        }
    }

    /**
     * addOperadoraMovel - Add data in table ars_mobile_carrier
     * @param <string> $operadora
     * @throws Exception
     */
    public static function addOperadoraMovel($operadora, $nome_operadora) {
        $db = Zend_Registry::get('db');
        $db->beginTransaction();
        try {
            $addOperadora = array('id' => $operadora, 'carrier' => $nome_operadora);
            $db->insert('ars_mobile_carrier', $addOperadora);
            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
        }
    }
    
    /**
     * addPrefixoMovel - Add data in table ars_mobile
     * @param <int> $ddd
     * @param <int> $prefixo
     * @param <string> $operadora
     * @throws Exception
     */
    public static function addPrefixoMovel($ddd, $prefixo, $operadora) {
        $db = Zend_Registry::get('db');
        $db->beginTransaction();
        try {
            $addPrefixo = array('area_code' => $ddd, 'prefix_code' => $prefixo, 'carrier' => $operadora);
            $db->insert('ars_mobile', $addPrefixo);
            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
        }
    }
    
    /**
     * getCnl - Get data from cnl
     * @return <array> $registros
     */
    public static function getCnl() {

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->from('cnl');

        $stmt = $db->query($select);
        $registros = $stmt->fetchAll();

        return $registros;
    }

    /**
     * getOperadora - Get operadora from cnl 
     * @return <array> $registros
     */
    public static function getOperadora() {

        $db = Zend_Registry::get('db');
        $select = $db->select()
                ->distinct('operadora')
                ->from('cnl', 'operadora');

        $stmt = $db->query($select);
        $registros = $stmt->fetchAll();

        return $registros;
    }

    /**
     * get
     * @param <string> $uf
     * @return <array> $registros
     */
    public function get($uf) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('ddd' => 'ars_ddd'), array('estado as uf'))
                ->join(array('cid' => 'ars_cidade'), 'cid.id = ddd.cidade', array('name as municipio'))
                ->where("ddd.estado = '$uf'")
                ->order("municipio");

        $stmt = $db->query($select);
        $registros = $stmt->fetchAll();

        return $registros;
    }

    /**
     * getPrefixo - Get prefixo from cnl 
     * @param <string> $cidade
     * @return <array>
     */
    public function getPrefixo($cidade) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('cid' => 'ars_cidade'), array('name as municipio'))
                ->join(array('pre' => 'ars_prefixo'), 'pre.cidade = cid.id', array('prefixo'))
                ->where("cid.name = '$cidade'");

        $stmt = $db->query($select);
        $registros = $stmt->fetchAll();

        return $registros;
    }

    /**
     * getCidade - Get Cidade from cnl 
     * @param <int> $prefixo
     * @return <array> $registros
     */
    public function getCidade($prefixo) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('cid' => 'ars_cidade'), array('name as municipio'))
                ->join(array('pre' => 'ars_prefixo'), 'pre.cidade = cid.id', array('prefixo'))
                ->join(array('ddd' => 'ars_ddd'), 'pre.cidade = ddd.cidade')
                ->where("pre.prefixo = '$prefixo'");

        $stmt = $db->query($select);
        $registros = $stmt->fetchAll();

        return $registros;
    }

}
