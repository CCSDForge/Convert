<?php

define('BASETEMPREP', '/tmp/ccsdtex');
define('CHROOT', '/latexRoot');
define('LATEX', '/usr/local/texlive/2023');     # Latex courant
define('LATEX2020', '/usr/local/texlive/2020'); # Latex version
define('LATEX2016', '/usr/local/texlive/2016'); # Latex version fixe 2016
define('LATEX2014', '/usr/local/texlive/2014'); # Latex version fixe 2014

require __DIR__ . "/../tex.php";
require __DIR__ . "/../Convert/Config.php";

class Ccsd_Compile_Test2 extends \PHPUnit\Framework\TestCase {
    private $tempchrootrep ='';
    private $temprep = '';
    /**
     * @var CcsdTexCompile
     */
    private $compilateur = null;
    private $Conf = [];
    private $testDocRoot = '';
    public $pdfCreated = [];
    public $fileCreated = [];

    // ...
    protected function setUp(): void {
        $this -> tempchrootrep = BASETEMPREP.DIRECTORY_SEPARATOR.uniqid().DIRECTORY_SEPARATOR;
        $this -> temprep = CHROOT.$this -> tempchrootrep;
        $this -> pdfCreated = array();
        $this -> Conf['texlive']   = "/usr/local/texlive/2011";
        $this -> Conf['tex']       = "tex -interaction=nonstopmode";
        $this -> Conf['latex']     = "latex -interaction=nonstopmode";
        $this -> Conf['pdflatex']  = "pdflatex -interaction=nonstopmode";
        $this -> Conf['bibtex']    = "bibtex -terse";
        $this -> Conf['makeindex'] = "makeindex -q";
        $this -> Conf['dvips']     = "dvips -q -Ptype1";
        $this -> Conf['ps2pdf']    = "/usr/bin/ps2pdf14";
        $this -> Conf['chroot']    = "/usr/sbin/chroot";
        $this -> compilateur = new CcsdTexCompile(LATEX, $this -> Conf, $this -> tempchrootrep, CHROOT, true, false, true);

        $config= \Convert\Config::getConfig();
        if ($testDocRoot = $config->get('test.docroot')) {
            $this->testDocRoot = $testDocRoot;
        }
        parent::setUp();
    }

    private function copyDocuments($fromDir, $toDir): void {
        mkdir($toDir, 0777, true) || exit;
        recurseCopy($this->testDocRoot . $fromDir, $toDir, false);
    }
    public function testCompileArxiv() {

        foreach (array('/docs/01/38/07/39') as $dir) {
            $this->copyDocuments($dir, $this -> temprep);
            recurseUnzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this->fileCreated = $this -> compilateur -> compile($bin,$this->testDocRoot . $dir,$tex_files,'');
                $this -> assertTrue(false);
            }  catch (TexCompileException $e) {
                if ($e -> getMessage() == "Could not find pdf file for the compilation of dependentTypesForGames") {
                    $this -> assertTrue(true);
                } else {
                    $this -> assertTrue(false);
                }
            }
            recurseRmdir($this -> temprep);
        }
    }
    
}