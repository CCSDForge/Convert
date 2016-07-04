<?php
set_time_limit(0);
require '/sites/code/utile.php';

$server = new SoapServer(NULL, array('uri'=>'HAL_CONV'));

require 'convert.php';
require 'compile.php';
require 'image.php';
require 'langdetect.php';

$server->addFunction(array("convert", "compile", "metaImage", "addXMP", "compileFromFile", "langdetect"));

$server->handle();
