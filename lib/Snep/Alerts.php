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
 * Class to manager Alerts
 *
 * @see Snep_Alerts
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Rafael Pereira Bozzetti <rafael@opens.com.br>
 *
 */
class Snep_Alerts {

    public function __construct() {
        
    }

    public function __destruct() {
        
    }

    public function __clone() {
        
    }

    /**
     * getAlert - Get alert by name
     * @param <string> $name
     * @return <array>
     */
    public function getAlert($name) {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('alertas')
                ->where("recurso = '$name'");

        $stmt = $db->query($select);
        $alertas = $stmt->fetchAll();

        return $alertas;
    }

    /**
     * getTimeOut - Get a Queue timeout
     * @return <array> $ret
     */
    public function getTimeOut() {

        $db = Zend_Registry::get('db');

        $select = $db->select()
                ->from('queues', array('name', 'servicelevel'))
                ->order('name');

        $stmt = $db->query($select);
        $timeout = $stmt->fetchAll();

        $ret = array();
        foreach ($timeout as $x) {
            if ($x['servicelevel'] > 0) {
                $ret[$x['name']] = $x['servicelevel'];
            }
        }
        return $ret;
    }

    /**
     * sendEmail - Send mail alert
     * @param <array> $alert 
     */
    public function sendEmail($alerta) {

        $config = Zend_Registry::get('config');

        if (is_null($alerta['destino'])) {
            $alerta['destino'] = $config->system->mail;
        } elseif (strpos($alerta['destino'], ",") > 0) {

            $email = explode(",", $alerta['destino']);
            foreach ($email as $mail) {

                $msg = $this->translate("SNEP - The queue") . $alerta['recurso'];
                $mail = new Zend_Mail();
                $mail->setBodyText($alerta['message']);
                $mail->setFrom($mail);
                $mail->addTo($alerta['destino']);
                $mail->setSubject($this->translate('Queue Alert'));
                $mail->send();
            }
        } else {

            $msg = $this->translate("SNEP - The queue") . $alerta['recurso'];
            $mail = new Zend_Mail();
            $mail->setBodyText($alerta['message']);
            $mail->setFrom($config->system->mail);
            $mail->addTo($alerta['destino']);
            $mail->setSubject($this->translate('Queue Alert'));
            $mail->send();
        }
    }

}
