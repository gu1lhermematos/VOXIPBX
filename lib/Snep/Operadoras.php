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
 * Classe que abstrai Operadoras
 *
 * @see Snep_Operadoras
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti <rafael@opens.com.br>
 * 
 */
class Snep_Operadoras {

    private $codigo;
    private $nome;
    private $tpm;
    private $tdm;
    private $tbf;
    private $tbc;
    private $vpf;
    private $vpc;

    public function __construct() {
        
    }

    public function __destruct() {
        
    }

    public function __clone() {
        
    }

    /**
     * __get - Acesso diretos aos atributos
     * @param <string> $atributo
     * @return type
     */
    public function __get($atributo) {
        return $this->{$atributo};
    }

    /**
     * __set - Acesso direto aos atributos
     * @param <string> $atributo
     * @param <string> $valor
     */
    public function __set($atributo, $valor) {
        $this->{$atributo} = $valor;
    }

    /**
     * get - Retorna uma determinada Operadora
     * @param <string> $codigo
     * @return <array> $operadora
     */
    public function get($codigo) {
        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('operadoras')
                ->where("codigo = '$codigo'");

        $stmt = $db->query($select);
        $operadora = $stmt->fetchAll();

        return $operadora;
    }

    /**
     * getAll - Retorna todas as operadoras
     * @return <array> $operadoras
     */
    public function getAll() {
        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('operadoras')
                ->order('nome');

        $stmt = $db->query($select);
        $operadoras = $stmt->fetchAll();

        return $operadoras;
    }

    /**
     * getFiltrado - Retorna todas operadoras possibilitando filtro no select
     * @param <string> $filtro
     * @param <string> $valor
     * @return <array> $operadoras
     */
    public function getFiltrado($filtro, $valor) {
        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('operadoras')
                ->order('codigo');

        if (!is_null($filtro)) {
            $select->where("" . $filtro . " like '%" . $valor . "%'");
        }

        $stmt = $db->query($select);
        $operadoras = $stmt->fetchAll();

        return $operadoras;
    }

    /**
     * getCcustoOperadora - Retorna os centros de custo de determinada operadora
     * @param <string> $id
     * @return <array> $op_ccustos
     */
    public function getCcustoOperadora($id) {
        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('o' => 'oper_ccustos'))
                ->join(array('c' => 'ccustos'), 'c.codigo = o.ccustos', array('codigo', 'nome', 'tipo'))
                ->where("o.operadora = '$id'");

        $stmt = $db->query($select);
        $op_ccustos = $stmt->fetchAll();

        return $op_ccustos;
    }

    /**
     * getOperadoraCcusto
     * @param <string> $ccusto
     * @return <array> $ccusto_op
     */
    public function getOperadoraCcusto($ccusto) {
        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from(array('o' => 'operadoras'))
                ->from(array('oc' => 'oper_ccustos'))
                ->join(array('c' => 'ccustos'), "o.codigo=oc.operadora AND oc.ccustos=c.codigo AND c.codigo='$ccusto'");

        $stmt = $db->query($select);
        $ccusto_op = $stmt->fetch();

        return $ccusto_op;
    }

    /**
     * register - Registra determinada operadora no banco
     * @param <object> $operadora
     * @return <string>
     */
    public static function register($operadora) {
        $db = Zend_Registry::get('db');

        $insert_data = array("codigo" => $operadora->codigo,
            "nome" => $operadora->nome,
            "tpm" => $operadora->tpm,
            "tdm" => $operadora->tdm,
            "tbf" => $operadora->tbf,
            "tbc" => $operadora->tbc,
            "vpf" => $operadora->vpf,
            "vpc" => $operadora->vpc
        );

        $db->insert('operadoras', $insert_data);

        return $db->lastInsertId();
    }

    /**
     * setCcustoOperadora - Registra determinados Centros de Custo para determinada Operadora
     * @param <string> $operadora
     * @param <array> $ccustos
     */
    public function setCcustoOperadora($operadora, $ccustos) {
        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        $db->delete('oper_ccustos', "operadora = '$operadora'");

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }

        foreach ($ccustos as $ccusto) {
            $insert_data = array("operadora" => $operadora,
                "ccustos" => $ccusto
            );
            $db->insert('oper_ccustos', $insert_data);
        }
    }

    /**
     * update - Atualiza informações de determinada Operadora
     * @param <string> $operadora
     */
    public static function update($operadora) {

        $oper = self::get($operadora->codigo);

        if ($oper) {

            $update_data = array("codigo" => $operadora->codigo,
                "nome" => $operadora->nome,
                "tpm" => $operadora->tpm,
                "tdm" => $operadora->tdm,
                "tbf" => $operadora->tbf,
                "tbc" => $operadora->tbc,
                "vpf" => $operadora->vpf,
                "vpc" => $operadora->vpc
            );

            $db = Zend_Registry::get('db');
            $db->update("operadoras", $update_data, "codigo = '$operadora->codigo'");
        }
    }

    /**
     * remove - Remove determinada operadora
     * @param <string> $operadora
     */
    public static function remove($operadora) {
        $db = Zend_Registry::get('db');

        $oper = self::get($operadora);

        if ($oper) {

            $db->delete('oper_ccustos', "operadora = '$operadora'");
            $db->delete('operadoras', "codigo = '$operadora'");

            $db->beginTransaction();

            try {
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
            }
        }
    }

}
