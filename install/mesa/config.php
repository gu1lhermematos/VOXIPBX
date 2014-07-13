<?php
// Database connection details
//
// Only needed if you do not use FreePBX or want to specify
// a different database than the freepbx one.

$DBHOST = 'localhost';
$DBNAME = 'fop2';
$DBUSER = 'root';
$DBPASS = 'tofalando2014';

// ---------------------------------------------------------
// Do not modify below this line
// ---------------------------------------------------------

$phpver = substr(phpversion(),0,1);
$DBSETUP="fop2setup";

define('DEBUG',false);

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors',     0);
ini_set("session.cookie_lifetime", "0");    // conservar session hasta que se cierre el navegador
ini_set("session.gc_maxlifetime", 60*60*9); // duracion maxima de la session

// Site specific
define("MYAP",  "FOP2");
define("TITLE", "Flash Operator Panel 2");
if(isset($_SERVER['PATH_INFO'])) {
    define("SELF",  substr($_SERVER['PHP_SELF'], 0, (strlen($_SERVER['PHP_SELF']) - @strlen($_SERVER['PATH_INFO']))));
} else {
    define("SELF",  $_SERVER['PHP_SELF']);
}

// Parsing time calculation
$time      = explode(' ', microtime());
$time      = $time[1] + $time[0];
$begintime = $time;

// Session start
session_start();
//session_register(MYAP);

// General classes inclussion
require_once("lib/func.php");

if($phpver>4) {
    require_once("lib/dblib.php");
    require_once("lib/dbgrid.php");
} else {
    require_once("lib/dblib4.php");
    require_once("lib/dbgrid4.php");
}

set_error_handler("funcErrorHandler",E_ALL);

if(function_exists('mysql_connect')) { 

    $db = new dbcon($DBHOST, $DBUSER, $DBPASS, $DBNAME, false);

    if(!$db->is_connected()) {
        // Database connection details from amportal 
        if (is_readable("/etc/freepbx/freepbx.conf")) {
            $amp_conf = parse_amportal_conf("/etc/freepbx/freepbx.conf");
            $DBHOST = $amp_conf['AMPDBHOST'];
            $DBNAME = $amp_conf['AMPDBNAME'];
            $DBUSER = $amp_conf['AMPDBUSER'];
            $DBPASS = $amp_conf['AMPDBPASS'];
        } else if (is_readable("/etc/amportal.conf")) {
            $amp_conf = parse_amportal_conf("/etc/amportal.conf");
            $DBHOST = $amp_conf['AMPDBHOST'];
            $DBNAME = $amp_conf['AMPDBNAME'];
            $DBUSER = $amp_conf['AMPDBUSER'];
            $DBPASS = $amp_conf['AMPDBPASS'];
        } 
        $db = new dbcon($DBHOST, $DBUSER, $DBPASS, $DBNAME, true);
    }

    set_config($DBSETUP);

}

$traduccionesQueFaltan = Array();

$language="pt_BR";
if(isset($_SESSION[MYAP]['language'])) {
   $language = $_SESSION[MYAP]['language'];
   if(!is_file("lang/$language.php")) {
       $language="pt_BR";
   } 
}

require_once("lang/$language.php");

header('content-type: text/html; charset: utf-8');

function parse_amportal_conf($filename) {

    $file = file($filename);
    if (is_array($file)) {
        foreach ($file as $line) {
            if (preg_match("/^\s*([a-zA-Z0-9_]+)=([a-zA-Z0-9 .&-@=_!<>\"\']+)\s*$/",$line,$matches)) {
                $conf[ $matches[1] ] = trim($matches[2]);
            }
        }
    } else {
        die("<h1>".sprintf("Missing or unreadable config file (%s)...cannot continue", $filename)."</h1>");
    }
    return $conf;
} 

?>
