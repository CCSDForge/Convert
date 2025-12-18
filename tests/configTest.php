<?php



namespace Convert;
require __DIR__ . '/../Convert/Config.php';

use Convert\Config;

$config = Config::getConfig();


$key = 'param1';
$value = $config->get($key);

if ($value != 'yes') {
    print "erreur";
} else {
    print "ok\n";
}

    
$key = 'param2.subkey1';
$value = $config->get($key);

if ($value != 'bonne clef') {
    print "erreur";

}else {
    print "ok\n";
}


    
