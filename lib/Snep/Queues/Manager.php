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
 * Classe to manager a Queues.
 *
 * @see Snep_Queues_Manager
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti <rafael@opens.com.br>
 * 
 */
class Snep_Queues_Manager {

    public function __construct() {
        
    }

    /**
     * get - Get a queue by id
     * @param <string> $name
     * @return <array> $queue
     */
    public function get($name) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('queues')
                ->where("queues.name = ?", $name);

        $stmt = $db->query($select);
        $queue = $stmt->fetch();

        return $queue;
    }

    /**
     * getAll
     * @return <array> $queue
     */
    public function getAll() {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('queues', array('name'));

        $stmt = $db->query($select);
        $queue = $stmt->fetchAll();

        return $queue;
    }

    /**
     * getPeers - Get peers
     * @param <string> $id
     * @return <array> $queue
     */
    public function getPeers($id) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('peers')
                ->where("peers.name = ?", $id);

        $stmt = $db->query($select);
        $queue = $stmt->fetch();

        return $queue;
    }

    /**
     * setQueuePeers - Set Peers in the Queue
     * @param <string> $insert_data
     */
    public function SetQueuePeers($insert_data) {

        $db = Zend_Registry::get('db');

        /*  $insert_data_queue = array('fila' => $insert_data['fila'],
          'ramal'    => $insert_data['ramal'] );
         */
        $db->insert('queue_peers', $insert_data);
    }

    /**
     * getQueueOnlyPeers
     * @param <string> $id
     * @return <array> $queuesPeer
     */
    public function getQueueOnlyPeers($id) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('queue_peers', array('fila'))
                ->where('queue_peers.ramal = ?', $id);

        $stmt = $db->query($select);
        $queuesPeer = $stmt->fetchAll();

        return $queuesPeer;
    }

    /**
     * RemoveAllQueuePeers
     * @param <string> $id
     * @return \Exception|boolean
     */
    public function RemoveAllQueuPeers($id) {

        $db = Zend_Registry::get('db');

        $db->beginTransaction();

        try {

            $db->delete("queue_peers", "ramal='{$id}'");
            $db->commit();

            return true;
        } catch (Exception $e) {

            $db->rollBack();
            return $e;
        }
    }

    /**
     * add - Add a Queue.
     * @param <array> $queue
     */
    public function add($queue) {

        $db = Zend_Registry::get('db');

        $insert_data = array('name' => $queue['name'],
            'musiconhold' => $queue['musiconhold'],
            'announce' => $queue['announce'],
            'context' => $queue['context'],
            'timeout' => $queue['timeout'],
            'queue_youarenext' => $queue['queue_youarenext'],
            'queue_thereare' => $queue['queue_thereare'],
            'queue_callswaiting' => $queue['queue_callswaiting'],
            'queue_thankyou' => $queue['queue_thankyou'],
            'announce_frequency' => $queue['announce_frequency'],
            'retry' => $queue['retry'],
            'wrapuptime' => $queue['wrapuptime'],
            'maxlen' => $queue['maxlen'],
            'servicelevel' => $queue['servicelevel'],
            'strategy' => $queue['strategy'],
            'joinempty' => $queue['joinempty'],
            'leavewhenempty' => $queue['leavewhenempty'],
            'reportholdtime' => $queue['reportholdtime'],
            'memberdelay' => $queue['memberdelay'],
            'weight' => $queue['weight']
        );

        $db->insert('queues', $insert_data);
    }

    /**
     * addQueuPeers
     * @param <string> $queue
     */
    public function addQueuPeers($queue) {

        $db = Zend_Registry::get('db');
        $insert_data = array('fila' => $queue,
            'ramal' => 1
        );

        $db->insert('queue_peers', $insert_data);
    }

    /**
     * edit - Edit a Queue
     * @param <array> $queue
     */
    public function edit($queue) {

        $db = Zend_Registry::get('db');

        $update_data = array('musiconhold' => $queue['musiconhold'],
            'announce' => $queue['announce'],
            'context' => $queue['context'],
            'timeout' => $queue['timeout'],
            'queue_youarenext' => $queue['queue_youarenext'],
            'queue_thereare' => $queue['queue_thereare'],
            'queue_callswaiting' => $queue['queue_callswaiting'],
            'queue_thankyou' => $queue['queue_thankyou'],
            'announce_frequency' => $queue['announce_frequency'],
            'retry' => $queue['retry'],
            'wrapuptime' => $queue['wrapuptime'],
            'maxlen' => $queue['maxlen'],
            'servicelevel' => $queue['servicelevel'],
            'strategy' => $queue['strategy'],
            'joinempty' => $queue['joinempty'],
            'leavewhenempty' => $queue['leavewhenempty'],
            'reportholdtime' => $queue['reportholdtime'],
            'memberdelay' => $queue['memberdelay'],
            'weight' => $queue['weight']
        );

        $db->update('queues', $update_data, "name = '{$queue['name']}'");
    }

    /**
     * remove - Remove a Queue
     * @param <string> $name
     */
    public function remove($name) {

        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        $db->delete('queues', "name = '$name'");

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }

    /**
     * removeQueues - Remove a queues_agent
     * @param <string> $name
     */
    public function removeQueues($name) {

        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        $db->delete('queues_agent', "queue = '$name'");

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }

    /**
     * getMembers - Get queue members
     * @param <string> $queue
     * @return <array>
     */
    public function getMembers($queue) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('queue_members')
                ->where("queue_members.queue_name = ?", $queue);

        $stmt = $db->query($select);
        $queuemember = $stmt->fetchAll();

        return $queuemember;
    }

    /**
     * getAllMembers - Get all members
     * @return <array>
     */
    public function getAllMembers() {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('peers', array('name', 'canal', 'callerid', 'group'))
                ->where("peers.name != 'admin'")
                ->where("peers.peer_type = 'R'")
                ->where("peers.canal != ''")
                ->order("group");

        $stmt = $db->query($select);
        $allMembers = $stmt->fetchAll();

        return $allMembers;
    }

    /**
     * removeAllMembers - Remove queue members
     * @param <string> $queue
     */
    public function removeAllMembers($queue) {

        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        $db->delete('queue_members', "queue_name = '$queue'");

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }

    /**
     * removeQueuePeers
     * @param <string> $queue
     */
    public function removeQueuePeers($queue) {

        $db = Zend_Registry::get('db');

        $db->beginTransaction();
        $db->delete('queue_peers', "queue_peers.fila = '$queue'");

        try {
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
        }
    }

    /**
     * insertMember - Insert member on queue
     * @param <string> $queue
     * @param <string> $member
     */
    public function insertMember($queue, $member) {

        $db = Zend_Registry::get('db');

        $insert_data = array('membername' => $member,
            'queue_name' => $queue,
            'interface' => $member);

        $db->insert('queue_members', $insert_data);
    }

}
