<?php

define('BASETEMPREP', '/tmp/ccsdtex');
define('CHROOT', '/latexRoot');
define('LATEX', '/usr/local/texlive/2016');     # Latex courant
define('LATEX2016', '/usr/local/texlive/2016'); # Latex version fixe 2016
define('LATEX2014', '/usr/local/texlive/2014'); # Latex version fixe 2014

require __DIR__ . "/../tex.php";

class Ccsd_Compile_Test1 extends PHPUnit_Framework_TestCase {
    private $tempchrootrep ='';
    private $temprep = '';
    /**
     * @var Ccsd_Tex_Compile
     */
    private $compilateur = null;
    private $Conf = [];

    public $pdfCreated = [];
    public $fileCreated = [];

    // ...
    public function setUp() {

        $this -> tempchrootrep = BASETEMPREP.DIRECTORY_SEPARATOR.uniqid().DIRECTORY_SEPARATOR;
        $this -> temprep = CHROOT.$this -> tempchrootrep;
        $this -> pdfCreated = array();
        $this -> Conf['texlive']   = LATEX;
        $this -> Conf['tex']       = "tex -interaction=nonstopmode";
        $this -> Conf['latex']     = "latex -interaction=nonstopmode";
        $this -> Conf['pdflatex']  = "pdflatex -interaction=nonstopmode";
        $this -> Conf['bibtex']    = "bibtex -terse";
        $this -> Conf['makeindex'] = "makeindex -q";
        $this -> Conf['dvips']     = "dvips -q -Ptype1";
        $this -> Conf['ps2pdf']    = "/usr/bin/ps2pdf14";
        $this -> Conf['chroot']    = "/usr/sbin/chroot";
        $this -> compilateur = new Ccsd_Tex_Compile(LATEX, $this -> Conf, $this -> tempchrootrep, CHROOT);
        
        parent::setUp();
    }

    public function testValues1() {
        $compil = new Ccsd_Tex_Compile(LATEX, $this -> Conf, '.', '');
        $this -> assertNotEmpty($compil -> check_for_compilation_error('f1'));
        $this -> assertTrue($compil -> check_for_bad_citation('f1'));
        $this -> assertFalse($compil -> check_for_bad_natbib('f1'));

    }

    public function testValues2() {
        $compil = new Ccsd_Tex_Compile(LATEX, $this -> Conf, __DIR__.'/exemple1', '');
        $this -> assertEquals( __DIR__.'/exemple1', $compil -> chrootedcompileDir());
        $this -> assertEquals('latex',$compil -> checkTexBin(), "Pb pour determiner le type du fichier tex 1");
        $this -> assertEquals(__DIR__.'/exemple1', $compil -> get_compileDir());
    }

    public function testValues3() {
        $compil = new Ccsd_Tex_Compile(LATEX, $this -> Conf, BASETEMPREP, CHROOT);

        
        $this -> assertTrue($compil -> is_chrooted());
        $this -> assertTrue($compil -> is_executable(LATEX . '/bin/' . $compil -> Arch . '/pdflatex'), "Pb d'exe latex");
        $this -> assertFalse($compil -> is_executable(LATEX . '/bin/' . $compil -> Arch . '/Fooprgm'), "Pb d'exe autre");
        $this -> assertEquals('/tmp/ccsdtex'          , $compil -> get_compileDir());
        $this -> assertEquals('/latexRoot'            , $compil -> get_chroot());
        $this -> assertEquals('/latexRoot/tmp/ccsdtex', $compil -> chrootedcompileDir());
        $this -> assertEquals('/usr/sbin/chroot'      , $compil -> get_chrootcmd());
    }
    
    public function testCompile() {
        foreach (array('/docs/01/01/01/06', '/docs/01/02/01/61', '/docs/01/02/01/56') as $dir) {
            mkdir($this -> temprep, 0777, true) || exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurse_rmdir($this -> temprep);
        }
    }

    public function testCompile2() {
        # Tests devant echouer... normalement
        foreach (array('/docs/01/10/08/11') as $dir) {
            mkdir($this -> temprep, 0777, true) || exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(false);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(true);
            }
            recurse_rmdir($this -> temprep);
        }
    }
    public function testCompile3() {
        foreach (array('/docs/preprod/01/00/04/28', '/docs/preprod/01/00/07/98', '/docs/preprod/01/00/19/84') as $dir) {
            mkdir($this -> temprep, 0777, true) || exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurse_rmdir($this -> temprep);
        }
    }
    public function testCompile4() {
        foreach (array('/docs/01/34/40/90') as $dir) {
            mkdir($this -> temprep, 0777, true) || exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurse_rmdir($this -> temprep);
        }
    }
    public function testCompile5() {
        foreach (array('/docs/01/37/67/31') as $dir) {
            mkdir($this -> temprep, 0777, true) || exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurse_rmdir($this -> temprep);
        }
    }
}