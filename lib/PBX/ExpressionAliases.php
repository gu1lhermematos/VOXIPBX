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
 * Faz o controle em banco dos Alias para expressões regulares.
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author Henrique Grolli Bassotto
 */
class PBX_ExpressionAliases {

    private static $instance;

    protected function __construct() {
        
    }

    protected function __clone() {
        
    }

    /**
     * Retorna instancia dessa classe
     * @return PBX_ExpressionAliases
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * getAll - List expression Aliases
     * @return <array> $aliases
     */
    public function getAll() {
        $db = Zend_Registry::get('db');
        $select = "SELECT aliasid, name FROM expr_alias";

        $stmt = $db->query($select);
        $raw_aliases = $stmt->fetchAll();

        $aliases = array();
        foreach ($raw_aliases as $alias) {
            $aliases[$alias['aliasid']] = array(
                "id" => $alias['aliasid'],
                "name" => $alias['name'],
                "expressions" => array()
            );
        }

        $db = Zend_Registry::get('db');
        $select = "SELECT aliasid, expression FROM expr_alias_expression";

        $stmt = $db->query($select);
        $raw_expressions = $stmt->fetchAll();

        foreach ($raw_expressions as $expr) {
            $aliases[$expr["aliasid"]]["expressions"][] = $expr['expression'];
        }

        return $aliases;
    }

    /**
     * get
     * @param <int> $id
     * @return <array>
     * @throws PBX_Exception_BadArg
     */
    public function get($id) {
        if (!is_integer($id)) {
            throw new PBX_Exception_BadArg("Id must be numerical");
        }

        $db = Zend_Registry::get('db');
        $select = "SELECT name FROM expr_alias WHERE aliasid='$id'";

        $stmt = $db->query($select);
        $raw_alias = $stmt->fetchObject();
        $alias = array(
            "id" => $id,
            "name" => $raw_alias->name,
            "expressions" => array()
        );

        $db = Zend_Registry::get('db');
        $select = "SELECT expression FROM expr_alias_expression WHERE aliasid='$id'";

        $stmt = $db->query($select);
        $raw_expression = $stmt->fetchAll();

        foreach ($raw_expression as $expr) {
            $alias["expressions"][] = $expr['expression'];
        }

        return $alias;
    }

    /**
     * register
     * @param <array> $expression
     * @throws Exception
     */
    public function register($expression) {
        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        $db->insert("expr_alias", array("name" => $expression['name']));
        $id = $db->lastInsertId();

        foreach ($expression['expressions'] as $expr) {
            $data = array("aliasid" => $id, "expression" => $expr);
            $db->insert("expr_alias_expression", $data);
        }

        //log-user
        $tabela = self::verificaLog();
        if ($tabela == true) {

            $acao = "Adicionou expressao regular";
            self::salvaLog($acao, $id);
            $action = "ADD";

            $add["id"] = $id;
            $add["name"] = $expression["name"];
            $add["exp"] = "";
            foreach ($expression['expressions'] as $expr) {

                $add["exp"] .= $expr . "  ";
            }
            self::insertLogExpression($action, $add);
        }

        try {
            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
        }
    }

    /**
     * update - update expression alias
     * @param <array> $expression
     * @throws Exception
     */
    public function update($expression) {
        $id = $expression['id'];

        //log-user
        $tabela = self::verificaLog();
        if ($tabela == true) {

            $action = "OLD";
            $add = self::getExpression($id);
            self::insertLogExpression($action, $add);
        }

        $db = Zend_Registry::get('db');
        $db->beginTransaction();
        $db->update("expr_alias", array("name" => $expression['name']), "aliasid='$id'");
        $db->delete("expr_alias_expression", "aliasid='$id'");

        foreach ($expression['expressions'] as $expr) {
            $data = array("aliasid" => $id, "expression" => $expr);
            $db->insert("expr_alias_expression", $data);
        }

        if ($tabela == true) {

            $acao = "Editou expressao regular";
            self::salvaLog($acao, $id);
            $action = "NEW";
            $add = self::getExpression($id);
            self::insertLogExpression($action, $add);
        }

        try {
            $db->commit();
        } catch (Exception $ex) {
            $db->rollBack();
            throw $ex;
        }
    }

    /**
     * delete - Remove expression alias
     * @param <int> $id
     */
    public function delete($id) {
        $db = Zend_Registry::get('db');

        //log-user
        $tabela = self::verificaLog();
        if ($tabela == true) {

            $acao = "Excluiu expressao regular";
            self::salvaLog($acao, $id);
            $action = "DEL";
            $add = self::getExpression($id);
            self::insertLogExpression($action, $add);
        }

        $db->delete("expr_alias", "aliasid='$id'");
    }

    /**
     * verificaLog - Verify if exists module Loguser
     * @return <boolean> $tabela
     */
    function verificaLog() {
        if (class_exists("Loguser_Manager")) {
            $tabela = true;
        } else {
            $tabela = false;
        }
        return $tabela;
    }

    /**
     * salvalog - Insert data on database
     * @param <String> $ação 
     * @param <String> $sounds 
     * @return <boolean> 
     */
    function salvaLog($acao, $sounds) {
        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');
        $tipo = 10;

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $acao = mysql_escape_string($acao);

        $sql = "INSERT INTO `logs` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $acao . "', '" . $sounds . "', '" . $tipo . "' , '" . NULL . "', '" . NULL . "', '" . NULL . "', '" . NULL . "')";

        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getExpression - Array with data of expression alias
     * @param <int> $id 
     * @return <array> $archive 
     */
    function getExpression($id) {

        $archive = array();

        $db = Zend_Registry::get("db");
        $sql = "SELECT aliasid as id,name from  expr_alias where aliasid='$id'";
        $stmt = $db->query($sql);
        $archive = $stmt->fetch();

        $sql = "SELECT expression from  expr_alias_expression where aliasid='$id'";
        $stmt = $db->query($sql);
        $expressions = $stmt->fetchall();
        $archive["exp"] = "";

        foreach ($expressions as $expr) {
            $archive["exp"] .= $expr["expression"] . " ";
        }

        return $archive;
    }

    /**
     * insertLogexpression - Insert data of expression on table logs_users 
     * @param <array> $add
     * @param <string> $acao
     */
    function insertLogExpression($acao, $add) {

        $db = Zend_Registry::get("db");
        $ip = $_SERVER['REMOTE_ADDR'];
        $hora = date('Y-m-d H:i:s');

        $auth = Zend_Auth::getInstance();
        $username = $auth->getIdentity();

        $sql = "INSERT INTO `logs_users` VALUES (NULL, '" . $hora . "', '" . $ip . "', '" . $username . "', '" . $add["id"] . "', '" . $add["name"] . "', '" . $add["exp"] . "', '" . NULL . "', '" . "EXP" . "', '" . $acao . "')";
        $db->query($sql);
    }

}
