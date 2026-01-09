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

        define('LATEX', '/usr/local/texlive/2023');     # Latex courant
        define('LATEX2020', '/usr/local/texlive/2020'); # Latex version
        define('LATEX2016', '/usr/local/texlive/2016'); # Latex version fixe 2016
        define('LATEX2014', '/usr/local/texlive/2014'); # Latex version fixe 2014
    }
}

$bootstrap = new Bootstrap();
$bootstrap->run();
