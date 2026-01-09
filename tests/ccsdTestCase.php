<?php

namespace Ccsd\Convert\Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../tex.php";
require_once __DIR__ . "/../Convert/Config.php";

use CcsdTexCompile;
use Convert\Config;

abstract class CcsdTestCase extends TestCase
{
    protected array $config = array();
    public array $pdfCreated = [];
    public array $fileCreated = [];
    protected $temprep = '';
    protected ?CcsdTexCompile $compilateur = null;
    protected string $testDocRoot = '';
    protected string $tempchrootrep ='';
    // ...
    protected function setUp(): void
    {

        $this->tempchrootrep = BASETEMPREP . DIRECTORY_SEPARATOR . uniqid() . DIRECTORY_SEPARATOR;
        $this->temprep = CHROOT . $this->tempchrootrep;
        $this->pdfCreated = array();
        $this->config['texlive'] = LATEX;
        $this->config['tex'] = "tex -interaction=errorstopmode";
        $this->config['latex'] = "latex -interaction=errorstopmode";
        $this->config['pdflatex'] = "pdflatex -interaction=errorstopmode";
        $this->config['bibtex'] = "bibtex -terse";
        $this->config['makeindex'] = "makeindex -q";
        $this->config['dvips'] = "dvips -q -Ptype1";
        $this->config['ps2pdf'] = "/usr/bin/ps2pdf14";
        $this->config['chroot'] = "/usr/sbin/chroot";
        $this->compilateur = new CcsdTexCompile(LATEX, $this->config, $this->tempchrootrep, CHROOT);

        $config = Config::getConfig();
        if ($testDocRoot = $config->get('test.docroot')) {
            $this->testDocRoot = $testDocRoot;
        }
        parent::setUp();
    }

    protected function copyDocuments($fromDir, $toDir): void {
        if (! mkdir($toDir, 0777, true)) {
            print "Error in mkdir $toDir\n";
            exit;
        }
        recurseCopy($this->testDocRoot . $fromDir, $toDir, false);
    }
}
