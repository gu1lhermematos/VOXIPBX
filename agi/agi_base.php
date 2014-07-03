<?php
/**
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/>.
 */
// Tratamento de sinais vindos do asterisk

// Controle da exibição de erros
error_reporting(E_ALL | E_STRICT);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);

require_once "Bootstrap.php";
new Bootstrap();

require_once "Snep/Config.php";
require_once "Snep/Logger.php";
require_once "PBX/Asterisk/AGI.php";
require_once "Zend/Console/Getopt.php";
ob_implicit_flush(true);

$config = Snep_Config::getConfig();
$log = Snep_Logger::getInstance();
$asterisk = PBX_Asterisk_AGI::getInstance();
$db = Zend_Registry::get("db");
$request = $asterisk->requestObj;
// Adds the modules directory to the snep module system
//require_once "Snep/Modules.php";
//Snep_Modules::getInstance()->addPath(APPLICATION_PATH . "/modules");

// Configuração das opções da linha de comando
try {
    $opts = new Zend_Console_Getopt(array(
        'version|v' => 'Prints version.',
        'outgoing_number|o=s' => 'Define a outgoing number',
        'xfer|x=s' => 'Replace the channel used for source identification.'
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    $log->err($e->getMessage());
    $log->err($e->getUsageMessage());
    exit;
}
Zend_Registry::set("db", Snep_Db::getInstance());

if ($opts->version) {
    echo "ToFalando Version " . Zend_Registry::get('snep_version') . "\n";
    exit;
}
