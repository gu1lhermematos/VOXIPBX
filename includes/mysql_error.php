<?php
/*
 * Error shown when mysql cannot be initialized by bootstrap.php
 */
$i18n = Zend_Registry::get("i18n");
?>
<!doctype html>
<html>
<head>
    <title>Snep 2.0 - Banco de dados não encontrado!</title>
    <style>
        body{
            margin: 0;
            padding: 0;
            font-family: tahoma, geneva, kalimati, sans-serif;
            color: #333;
            background: #dfdfdf url('<?php echo str_replace("/index.php", "", Zend_Controller_Front::getInstance()->getBaseUrl());?>/modules/default/img/system_background.png') repeat-x;
        }

        a{
            text-decoration: none;
            color: #666;
        }

        a img{
            border: none;
        }
        a:hover{
            color: #000;
        }

        ul{
        list-style: none;
        padding: 0;
        margin: 0;
        }

        #container{
            width: 960px;
            margin: 20px auto;
            display: table;
        }
        
        #errorBox{
            width: 920px;
            margin: 20px auto;
            padding: 0 20px;
            
            background: #f0f0f0;
            
            border: 1px solid #ccc;
            border-radius: 7px;
        }
        
        .exception{
            width: 940px;
            margin: 0 auto;
            padding: 10px;
            
            background: #f0f0f0;
            
            border: 1px solid #ccc;
            border-radius: 7px;
            
            font-size: 12px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div id="container">
        <img src="<?php echo str_replace("/index.php", "", Zend_Controller_Front::getInstance()->getBaseUrl());?>/modules/default/img/logo_snep_system.png" alt="SNEP 2.0" id="snep_logo" />
        <div id="errorBox">
            <h1><?php echo $i18n->translate("Error!");?></h1>
            <p><?php echo $i18n->translate("Database not found. Please contact system administrator.");?></p>
        </div>
    </div>
</body>
</html>