<?php


define('LATEX', '/usr/local/texlive/2023');     # Latex courant
define('LATEX2020', '/usr/local/texlive/2020'); # Latex version
define('LATEX2016', '/usr/local/texlive/2016'); # Latex version fixe 2016
define('LATEX2014', '/usr/local/texlive/2014'); # Latex version fixe 2014

require_once __DIR__ . "/../tex.php";
require_once __DIR__ . "/../Convert/Config.php";

class Ccsd_Compile_Test1 extends \PHPUnit\Framework\TestCase {
    private $tempchrootrep ='';
    private $temprep = '';
    /**
     * @var CcsdTexCompile
     */
    private $compilateur = null;
    private $Conf = array();

    public $pdfCreated = array();
    public $fileCreated = array();

    // ...
    protected function setUp(): void
    {

        $this->tempchrootrep = BASETEMPREP . DIRECTORY_SEPARATOR . uniqid() . DIRECTORY_SEPARATOR;
        $this->temprep = CHROOT . $this->tempchrootrep;
        $this->pdfCreated = array();
        $this->Conf['texlive'] = LATEX;
        $this->Conf['tex'] = "tex -interaction=nonstopmode";
        $this->Conf['latex'] = "latex -interaction=nonstopmode";
        $this->Conf['pdflatex'] = "pdflatex -interaction=nonstopmode";
        $this->Conf['bibtex'] = "bibtex -terse";
        $this->Conf['makeindex'] = "makeindex -q";
        $this->Conf['dvips'] = "dvips -q -Ptype1";
        $this->Conf['ps2pdf'] = "/usr/bin/ps2pdf14";
        $this->Conf['chroot'] = "/usr/sbin/chroot";
        $this->compilateur = new CcsdTexCompile(LATEX, $this->Conf, $this->tempchrootrep, CHROOT);

        $config = \Convert\Config::getConfig();
        if ($testDocRoot = $config->get('test.docroot')) {
            $this->testDocRoot = $testDocRoot;
        }
        // printf("DocRoot = %s\n", $this->testDocRoot);

        parent::setUp();
    }

    private function copyDocuments($fromDir, $toDir): void {
        // printf("Copy %s%s to %s\n",$this->testDocRoot, $fromDir, $toDir);
        if (! mkdir($toDir, 0777, true)) {
            print "Error in mkdir $toDir\n";
            exit;
        }
        recurseCopy($this->testDocRoot . $fromDir, $toDir, false);
    }

    public function testValues1() {
        $compil = new CcsdTexCompile(LATEX, $this -> Conf, '.', '');
        $this -> assertNotEmpty($compil -> checkForCompilationError('f1'));
        $this -> assertTrue((bool) $compil -> checkForBadCitation('f1'));
        $this -> assertFalse((bool) $compil -> checkForBadNatbib('f1'));

    }

    public function testValues2() {
        $compil = new CcsdTexCompile(LATEX, $this -> Conf, __DIR__.'/exemple1', '');
        $this -> assertEquals( __DIR__.'/exemple1', $compil -> chrootedCompileDir());
        $this -> assertEquals('latex',$compil -> checkTexBin(), "Pb pour determiner le type du fichier tex 1");
        $this -> assertEquals(__DIR__.'/exemple1', $compil -> getCompileDir());
    }

    public function testValues3() {
        $compil = new CcsdTexCompile(LATEX, $this -> Conf, BASETEMPREP, CHROOT);

        $config= \Convert\Config::getConfig();
        if (! $config->get('use.docker')) {
            $this->assertTrue($compil->isChrooted());
            $this->assertTrue($compil->isExecutable(LATEX . '/bin/' . $compil->arch . '/pdflatex'), "Pb d'exe latex");
            $this->assertFalse($compil->isExecutable(LATEX . '/bin/' . $compil->arch . '/Fooprgm'), "Pb d'exe autre");
            $this -> assertEquals('/usr/sbin/chroot'      , $compil -> getChrootCmd());
        } else {
            $this -> assertEquals('docker run --rm -u root -v convert_tmpCompil:/tmp/ccsdtex   convert-latex', $compil -> getChrootCmd());
        }
        $this -> assertEquals('/tmp/ccsdtex'          , $compil -> getCompileDir());
        $this -> assertEquals('/latexRoot'            , $compil -> getChroot());
        $this -> assertEquals('/latexRoot/tmp/ccsdtex', $compil -> chrootedCompileDir());

    }
    
    public function testCompile() {
        foreach (array('/docs/01/01/01/06', '/docs/01/02/01/61', '/docs/01/02/01/56') as $dir) {
            $this->copyDocuments($dir, $this -> temprep);
            recurseUnzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurseRmdir($this -> temprep);
        }
    }

    public function testCompile2() {
        # Tests devant echouer... normalement
        foreach (array('/docs/01/10/08/11') as $dir) {
            $this->copyDocuments($dir, $this -> temprep);
            recurseUnzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(false);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(true);
            }
            recurseRmdir($this -> temprep);
        }
    }
    public function testCompile3() {
        foreach (array('/docs/preprod/01/00/04/28', '/docs/preprod/01/00/07/98', '/docs/preprod/01/00/19/84') as $dir) {
            $this->copyDocuments($dir, $this -> temprep);
            recurseUnzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurseRmdir($this -> temprep);
        }
    }
    public function testCompile4() {
        foreach (array('/docs/01/34/40/90') as $dir) {
            $this->copyDocuments($dir, $this -> temprep);
            recurseUnzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurseRmdir($this -> temprep);
        }
    }
    public function testCompile5() {
        foreach (array('/docs/01/37/67/31') as $dir) {
            $this->copyDocuments($dir, $this -> temprep);
            recurseUnzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurseRmdir($this -> temprep);
        }
    }
}