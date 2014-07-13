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
 * Persistência de extensões (ramais) no Snep
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2010 OpenS Tecnologia
 * @author    Henrique Grolli Bassotto
 */
class Snep_Extensions {

    private $commitPending = false;
    private $commitList = array();

    /**
     * get - Retorna um Ramal
     * @param <int> $extensionId
     * @return Snep_Exten usuario
     */
    public function get($extensionId) {
        $db = Zend_Registry::get('db');

        $select = $db->select()->from('peers')->where("name = '$extensionId' AND peer_type='R'");
        $stmt = $db->query($select);
        $usuario = $stmt->fetchObject();
        if (!$usuario) {
            throw new PBX_Exception_NotFound("Usuario $extensionId nao encontrado");
        }

        $exten = $this->processExten($usuario);
        return $exten;
    }

    /**
     * orocessExten - Processa dados crus da tabela no banco para instanciação de objetos de
     * ramais.
     *
     * @param <Object> $data Resultado de um select com todas as colunas no banco
     * de dados dos ramais.
     * @return Snep_Exten ramal criado a partir dos dados.
     */
    private function processExten($data) {
        $tech = substr($data->canal, 0, strpos($data->canal, '/'));

        if ($tech == "SIP" || $tech == "IAX2") {
            $config = array(
                "username" => $data->name,
                "secret" => $data->secret,
                "allow" => $data->allow,
                "type" => $data->type,
                "qualify" => $data->qualify,
                "dtmfmode" => $data->dtmfmode,
                "directmedia" => $data->directmedia,
                "nat" => $data->nat,
                "call-limit" => $data->{'call-limit'}
            );

            if ($tech == "SIP") {
                $interface = new PBX_Asterisk_Interface_SIP($config);
            } else {
                $interface = new PBX_Asterisk_Interface_IAX2($config);
            }
        } else if ($tech == "VIRTUAL") {
            $exten_id = substr($data->canal, strpos($data->canal, '/') + 1);
            $trunk = PBX_Trunks::get(substr($data->canal, strpos($data->canal, '/') + 1));
            $interface = new PBX_Asterisk_Interface_VIRTUAL(array("channel" => $trunk->getInterface()->getCanal() . "/" . $exten_id));
        } else if ($tech == "MANUAL") {
            $interface = new PBX_Asterisk_Interface_VIRTUAL(array("channel" => substr($data->canal, strpos($data->canal, '/') + 1)));
        } else if ($tech == "KHOMP") {
            $khomp_id = substr($data->canal, strpos($data->canal, '/') + 1);
            $khomp_board = substr($khomp_id, 1, strpos($khomp_id, 'c') - 1);
            $khomp_channel = substr($khomp_id, strpos($khomp_id, 'c') + 1);
            $interface = new PBX_Asterisk_Interface_KHOMP(array("board" => $khomp_board, "channel" => $khomp_channel));
        } else {
            if ($data->name == "admin")
                $interface = new PBX_Asterisk_Interface_VIRTUAL(array("channel" => "admin"));
            else
                throw new Exception("Tecnologia $tech desconhecida ou invalida.");
        }

        $exten = new Snep_Exten($data->name, $data->password, $data->callerid, $interface);
        $exten->setChannel($data->canal);
        $exten->setGroup($data->group);

        if ($data->authenticate) {
            $exten->lock();
        }

        if ($data->dnd) {
            $exten->DNDEnable();
        }

        if ($data->sigame != "") {
            $exten->setFollowMe($data->sigame);
        }

        if (is_numeric($data->pickupgroup)) {
            $exten->setPickupGroup($data->pickupgroup);
        }

        if ($data->usa_vc == 'yes') {
            $exten->setMailBox($data->mailbox);
            $exten->setEmail($data->email);
        }

        if ($data->time_total != NULL) {
            $exten->setTimeTotal($data->time_total);
            $exten->setCtrlType($data->time_chargeby);
        }

        return $exten;
    }

    /**
     * getAll - Retorna todos os ramais do banco.
     * @return Snep_Exten[] array
     */
    public function getAll() {
        $db = Zend_Registry::get('db');
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $select = $db->select('name')->from('peers')->where("peer_type='R' AND name != 'admin'");

        $stmt = $db->query($select);
        $raw_data = $stmt->fetchAll();

        $extensions = array();
        foreach ($raw_data as $row) {
            $extensions[] = $this->processExten($row);
        }

        return $extensions;
    }

    /**
     * register - Registra um ramal no banco de dados.
     * @param Snep_Exten $exten Ramal a ser persistido no banco.
     */
    public function register(Snep_Exten $exten) {
        if ($this->commitPending === false) {
            $this->queueRegister($exten);
            $this->commit();
        } else {
            throw new Exception("Transação pendente, 'commite' antes de adicionar um ramal diretamente.");
        }
    }

    /**
     * queueRegister - Adiciona um ramal na fila para ser adicionado em lote no banco de dados.
     * @param Snep_Exten $exten
     */
    public function queueRegister(Snep_Exten $exten) {
        if (array_key_exists($exten->getNumero(), $this->commitList)) {
            throw new Exception("Ramal $exten já está na fila para inserção no banco.");
        }

        $this->commitPending = true;
        $this->commitList[$exten->getNumero()] = $exten;
    }

    /**
     * commit - Processa a fila de ramais para registro.
     */
    public function commit() {
        $data = array();
        foreach ($this->commitList as $exten) {
            $data[] = $this->getExtenDataAsArray($exten);
        }

        $db = Zend_Registry::get('db');

        $db->insert("peers", $data);

        $this->commitPending = false;
    }

    /**
     * ExtenDataAsArray - Processa os dados de um objeto em um array 
     * associativo que pode ser usado para manipulação do banco de dados.
     * @param Snep_Exten $exten
     * @return <array> string
     */
    public function ExtenDataAsArray(Snep_Exten $exten) {
        $extenData = array(
            "context" => "default",
            "peer_type" => "R",
            "name" => $exten->getNumero(),
            "fromuser" => $exten->getNumero(),
            "username" => $exten->getNumero(),
            "callerid" => $exten->getCallerid(),
            "password" => $exten->getPassword(),
            "pickupgroup" => $exten->getPickupGroup(),
            "canal" => $exten->getChannel(),
            "group" => $exten->getGroup(),
            "email" => $exten->getEmail(),
            "usa_vc" => $exten->hasVoiceMail(),
            "mailbox" => $exten->getMailBox(),
            "authenticate" => $exten->isLocked(),
            "time_total" => $exten->getTimeTotal(),
            "time_chargeby" => $exten->getCtrlType()
        );

        /**
         * Adicionando informações específica de interface.
         */
        if ($exten->getInterface() instanceof PBX_Asterisk_Interface_SIP ||
                $exten->getInterface() instanceof PBX_Asterisk_Interface_IAX2) {
            $interface = $exten->getInterface();
            $extenData['allow'] = $interface->allow;
            $extenData['type'] = $interface->type;
            $extenData['secret'] = $interface->secret;
            $extenData['qualify'] = $interface->qualify;
            $extenData['dtmfmode'] = $interface->dtmfmode;
            $extenData['directmedia'] = $interface->directmedia ;
            $extenData['call-limit'] = $interface->{'call-limit'};
            $extenData['nat'] = $interface->nat;
        }

        return $extenData;
    }

    /**
     * getExtenDataAsArray - Processa os dados de um objeto em um array 
     * associativo que pode ser usado para manipulação do banco de dados.
     * @param Snep_Exten $exten
     * @return <array> string
     */
    private function getExtenDataAsArray(Snep_Exten $exten) {
        $extenData = array(
            "context" => "default",
            "peer_type" => "R",
            "name" => $exten->getNumero(),
            "fromuser" => $exten->getNumero(),
            "username" => $exten->getNumero(),
            "callerid" => $exten->getCallerid(),
            "password" => $exten->getPassword(),
            "pickupgroup" => $exten->getPickupGroup(),
            "canal" => $exten->getInterface()->getCanal(),
            "group" => $exten->getGroup(),
            "email" => $exten->getEmail(),
            "usa_vc" => $exten->hasVoiceMail(),
            "mailbox" => $exten->getMailBox(),
            "authenticate" => $exten->isLocked(),
            "time_total" => $exten->getTimeTotal(),
            "time_chargeby" => $exten->getCtrlType()
        );

        /**
         * Adicionando informações específica de interface.
         */
        if ($exten->getInterface() instanceof PBX_Asterisk_Interface_SIP ||
                $exten->getInterface() instanceof PBX_Asterisk_Interface_IAX2) {
            $interface = $exten->getInterface();
            $extenData['allow'] = $interface->allow;
            $extenData['type'] = $interface->type;
            $extenData['secret'] = $interface->secret;
            $extenData['qualify'] = $interface->qualify;
            $extenData['dtmfmode'] = $interface->dtmfmode;
            $extenData['call-limit'] = $interface->{'call-limit'};
            $extenData['nat'] = $interface->nat;
        }

        return $extenData;
    }

    /**
     * update - Atualiza informações de um ramal registrado no banco de dados.
     * @param Snep_Exten $exten
     */
    public function update(Snep_Exten $exten) {
        $db = Zend_Registry::get('db');

        $db->update("peers", $this->getExtenDataAsArray($exten), "name='{$exten->getNumero()}'");
    }

    /**
     * delete - Remove um ou mais ramais do banco de dados.
     *
     * Para remover mais de um ramal do banco basta passar um array com todos
     * os ramais a serem removidos. Caso a operação falhe em qualquer ponto
     * nenhum ramal será removido.
     *
     * @param <string|array> $exten
     */
    public function delete($exten) {
        if (is_array($exten)) {
            $db = Zend_Registry::get("db");
            $db->beginTransaction();

            foreach ($exten as $extension) {
                $db->delete("peers", "name='$exten'");
            }

            try {
                $db->commit();
            } catch (Exception $ex) {
                $db->rollBack();
                throw $ex;
            }
        } else {
            $db->delete("peers", "name='$exten'");
        }
    }

}
