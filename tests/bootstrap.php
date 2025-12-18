<?php

require_once __DIR__ . "/../Convert/Config.php";
class Bootstrap {
    public function __construct() {}

    public function run() {
        $config = \Convert\Config::getConfig();
        $latexRoot = '/latexRoot';
        if ($configLatexRoot = $config->get('test.latexRoot')) {
            $latexRoot = $configLatexRoot;
        }
        define('CHROOT', $latexRoot);

        $compilationDir =  '/tmp/ccsdtex';
        if ($configCompilDir = $config->get('test.compilationDir')) {
            $compilationDir = $configCompilDir;
        }
        define('BASETEMPREP',$compilationDir);
    }
}

$bootstrap = new Bootstrap();
$bootstrap->run();
