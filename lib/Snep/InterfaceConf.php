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
 * Class to work with the config files of the different extension technologies on snep.
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2011 OpenS Tecnologia
 * @author    Lucas Ivan Seidenfus
 * 
 */
class Snep_InterfaceConf {

    /**
     * loadConfFromDb
     * @throws PBX_Exception_IO
     */
    public static function loadConfFromDb() {
        $view = new Zend_View();
        $db = Snep_Db::getInstance();

        foreach (array("sip", "iax2") as $tech) {
            $config = Zend_Registry::get('config');
            $asteriskDirectory = $config->system->path->asterisk->conf;

            $extenFileConf = "$asteriskDirectory/snep/snep-$tech.conf";
            $trunkFileConf = "$asteriskDirectory/snep/snep-$tech-trunks.conf";

            if (!is_writable($extenFileConf)) {
                throw new PBX_Exception_IO($view->translate("Failed to open file %s with write permission.", $extenFileConf));
            }
            if (!is_writable($trunkFileConf)) {
                throw new PBX_Exception_IO($view->translate("Failed to open file %s with write permission.", $trunkFileConf));
            }
            /* clean snep-sip.conf file */
            file_put_contents($extenFileConf, '');

            /* Register header on output string of the file */
            $todayDate = date("d/m/Y H:m:s");
            $header = ";-----------------------------------------------------------------------------------\n";
            $header .= "; Arquivo: snep-$tech.conf - Cadastro de ramais e Troncos                           \n";
            $header .= ";                                                                                   \n";
            $header .= "; Atualizado em: $todayDate                                                         \n";
            $header .= "; Copyright(c) 2008-2014 Opens Tecnologia                                           \n";
            $header .= ";-----------------------------------------------------------------------------------\n";
            $header .= "; Os registros a Seguir sao gerados pelo Software SNEP.                             \n";
            $header .= "; Este Arquivo NAO DEVE ser editado Manualmente sob riscos de                       \n";
            $header .= "; causar mal funcionamento do Asterisk                                              \n";
            $header .= ";-----------------------------------------------------------------------------------\n";

            /* query that gets information of the peers on the DB */
            $sql = "SELECT * FROM peers WHERE name != 'admin' AND canal like '" . strtoupper($tech) . "%'";
            $peer_data = $db->query($sql)->fetchAll();

            $peers = "\n";
            $trunk_config = "\n";

            if (count($peer_data) > 0) {
                foreach ($peer_data as $peer) {

                    $sipallow = explode(";", $peer['allow']);
                    $allow = '';
                    foreach ($sipallow as $siper) {
                        if ($siper != '') {
                            $allow .= $siper . ",";
                        }
                    }
                    $allow = substr($allow, 0, strlen($allow) - 1);

                    if ($peer['peer_type'] == 'T') {

                        $select = $db->select()->from('trunks')->where("name = {$peer['name']}")->limit(1);
                        $trunk = $db->query($select)->fetchObject();


                        if ($trunk->type == "SNEPSIP") {

                            /* Assemble trunk entries */
                            $peers .= '[' . $peer['username'] . "]\n";
                            $peers .= 'type=' . $peer['type'] . "\n";
                            $peers .= 'context=' . $peer['context'] . "\n";
                            $peers .= 'dtmfmode=' . ($peer['dtmfmode'] ? $peer['dtmfmode'] : "rfc2833") . "\n";
                            $peers .= 'host=' . $peer['host'] . "\n";
                            $peers .= 'qualify=' . ($peer['qualify'] == "no" ? "no" : "yes") . "\n";
                            $peers .= 'nat=' . $peer['nat'] . "\n";
                            $peers .= 'disallow=' . $peer['disallow'] . "\n";
                            $peers .= 'allow=' . $allow . "\n";
                            $peers .= "\n";
                        } else if ($trunk->type == "SIP" && $trunk->secret == "") {
                            /* Monta entrada do tronco */
                            $peers .= '[' . $peer['host'] . "]\n";
                            $peers .= 'type=peer' . "\n";
                            $peers .= 'context=' . $peer['context'] . "\n";
                            $peers .= 'dtmfmode=' . ($peer['dtmfmode'] ? $peer['dtmfmode'] : "rfc2833") . "\n";
                            $peers .= 'host=' . $peer['host'] . "\n";
                            $peers .= 'qualify=' . ($peer['qualify'] == "no" ? "no" : "yes") . "\n";
                            $peers .= 'nat=' . $peer['nat'] . "\n";
                            $peers .= 'disallow=' . $peer['disallow'] . "\n";
                            $peers .= 'allow=' . $allow . "\n";
                            $peers .= "\n";
                        } else if ($trunk->type == "SNEPIAX2") {
                            /* Assemble Extension entries */
                            $peers .= '[' . $peer['username'] . "]\n";
                            $peers .= 'type=' . $peer['type'] . "\n";
                            $peers .= 'username=' . $peer['username'] . "\n";
                            $peers .= 'secret=' . $peer['username'] . "\n";
                            $peers .= 'context=' . $peer['context'] . "\n";
                            $peers .= 'dtmfmode=' . ($peer['dtmfmode'] ? $peer['dtmfmode'] : "rfc2833") . "\n";
                            $peers .= 'host=' . $peer['host'] . "\n";
                            $peers .= 'qualify=' . ($peer['qualify'] == "no" ? "no" : "yes") . "\n";
                            $peers .= 'nat=' . $peer['nat'] . "\n";
                            $peers .= 'disallow=' . $peer['disallow'] . "\n";
                            $peers .= 'allow=' . $allow . "\n";
                            $peers .= 'trunk=' . $peer['trunk'] . "\n";
                            $peers .= "\n";
                        } else if ($trunk->dialmethod != "NOAUTH" && $trunk->secret != "") {

                            /* Assemble trunk entries */
                            $peers .= '[' . $peer['username'] . "]\n";
                            $peers .= 'type=' . $peer['type'] . "\n";
                            $peers .= 'context=' . $peer['context'] . "\n";
                            $peers .= ( $peer['fromdomain'] != "") ? ('fromdomain=' . $peer['fromdomain'] . "\n") : "";
                            // $peers .= ( $peer['fromuser'] != "") ? ('fromuser=' . $peer['fromuser'] . "\n") : "";
                            $peers .= 'dtmfmode=' . ($peer['dtmfmode'] ? $peer['dtmfmode'] : "rfc2833") . "\n";
                            $peers .= 'host=' . $peer['host'] . "\n";
                            $peers .= 'qualify=' . $peer['qualify'] . "\n";
                            $peers .= 'nat=' . $peer['nat'] . "\n";
                            $peers .= 'disallow=' . $peer['disallow'] . "\n";
                            $peers .= 'allow=' . $allow . "\n";

                            if ($peer['port'] != "") {
                                $peers .= 'port=' . $peer['port'] . "\n";
                            }
                            if ($peer['call-limit'] != "" && $trunk->type == "SIP") {
                                $peers .= 'call-limit=' . $peer['call-limit'] . "\n";
                            }
                            if ($trunk->insecure != "") {
                                $peers .= 'insecure=' . $trunk->insecure . "\n";
                            }
                            if ($trunk->domain != "" && $trunk->type == "SIP") {
                                $peers .= 'domain=' . $trunk->domain . "\n";
                            }
                            if ($trunk->type == "IAX2") {
                                $peers .= 'trunk=' . $peer['trunk'] . "\n";
                            }
                            if ($trunk->reverse_auth) {
                                $peers .= 'username=' . $peer['username'] . "\n";
                                $peers .= 'secret=' . $peer['secret'] . "\n";
                            }
                            $peers .= "\n";
                        }
                        $trunk_config .= ( $trunk->dialmethod != "NOAUTH" && !preg_match("/SNEP/", $trunk->type) ? "register => " . $peer['username'] . ":" . $peer['secret'] . "@" . $peer['host'] . "\n" : "");
                    } else {

                        /* Assemble Extension entries */
                        $peers .= '[' . $peer['name'] . "]\n";
                        $peers .= 'type=' . $peer['type'] . "\n";
                        $peers .= 'context=' . $peer['context'] . "\n";
                        $peers .= 'host=' . $peer['host'] . "\n"; # dinamyc
                        $peers .= 'secret=' . $peer['secret'] . "\n";
                        $peers .= 'callerid=' . $peer['callerid'] . "\n";
                        $peers .= 'dtmfmode=' . ($peer['dtmfmode'] ? $peer['dtmfmode'] : "rfc2833") . "\n";
                        $peers .= 'nat=' . $peer['nat'] . "\n";
                        $peers .= 'qualify=' . $peer['qualify'] . "\n";
                        $peers .= 'disallow=' . $peer['disallow'] . "\n";
                        $peers .= 'allow=' . $allow . "\n";

                        $peers .= 'username=' . $peer['name'] . "\n";

                        // $peers .= 'fromuser=' . $peer['name'] . "\n";

                        $peers .= 'call-limit=' . $peer['call-limit'] . "\n";
                        $peers .= 'directmedia=' . $peer['directmedia'] . "\n";

                        $peers .= "\n";
                    }
                }
            }

            /* Create file snep-siup-trunks.conf */
            $trunkcont = str_replace(".conf", "-trunks.conf", $header);
            file_put_contents($trunkFileConf, $trunkcont);

            //if ($trunk->secret != "") {
            if (isset($trunk_config)) {

                $trunkcont .= $trunk_config;

                file_put_contents($trunkFileConf, $trunkcont);
            }

            /* Create file snep-sip.conf */
            $content = $header . $peers;

            file_put_contents($extenFileConf, $content);
        }


        // Forcing asterisk to reload the configs
        $asteriskAmi = PBX_Asterisk_AMI::getInstance();
        $asteriskAmi->Command("sip reload");
        $asteriskAmi->Command("iax2 reload");
    }

}
