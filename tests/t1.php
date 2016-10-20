<?php

define('BASETEMPREP', '/tmp/ccsdtex');
define('CHROOT', '/latexRoot');
require __DIR__ . "/../tex.php";

class Ccsd_Compile_Test extends PHPUnit_Framework_TestCase {
    // ...
    public function setUp() {
        $this -> tempchrootrep = BASETEMPREP.DIRECTORY_SEPARATOR.uniqid().DIRECTORY_SEPARATOR;
        $this -> temprep = CHROOT.$this -> tempchrootrep;
        $this -> pdfCreated = array();
        $this -> Conf['texlive']   = "/usr/local/texlive/2014";
        $this -> Conf['tex']       = "tex -interaction=nonstopmode";
        $this -> Conf['latex']     = "latex -interaction=nonstopmode";
        $this -> Conf['pdflatex']  = "pdflatex -interaction=nonstopmode";
        $this -> Conf['bibtex']    = "bibtex -terse";
        $this -> Conf['makeindex'] = "makeindex -q";
        $this -> Conf['dvips']     = "dvips -q -Ptype1";
        $this -> Conf['ps2pdf']    = "/usr/bin/ps2pdf14";
        $this -> Conf['chroot']    = "/usr/sbin/chroot";
        $this -> compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $this -> Conf, $this -> tempchrootrep, CHROOT);
        
        
        parent::setUp();
    }

    public function testValues1() {
        $compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $this -> Conf, '.', '');
        $this -> assertNotEmpty($compilateur -> check_for_compilation_error('f1'));
        $this -> assertTrue($compilateur -> check_for_bad_citation('f1'));
        $this -> assertFalse($compilateur -> check_for_bad_natbib('f1'));

    }

    public function testValues2() {
        $compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $this -> Conf, __DIR__.'/exemple1', '');
        $this -> assertEquals( __DIR__.'/exemple1', $compilateur -> chrootedcompileDir());
        $this -> assertEquals('latex',$compilateur -> checkTexBin(), "Pb pour determiner le type du fichier tex 1");
        $this -> assertEquals(__DIR__.'/exemple1', $compilateur -> get_compileDir());
    }

    public function testValues3() {
        $compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $this -> Conf, BASETEMPREP, CHROOT);

        $this -> assertTrue($compilateur -> is_chrooted());
        $this -> assertTrue($compilateur -> is_executable('/usr/local/texlive/2014/bin/i386-linux/pdflatex'), "Pb d'exe latex");
        $this -> assertFalse($compilateur -> is_executable('/usr/local/texlive/2014/bin/i386-linux/Fooprgm'), "Pb d'exe autre");
        $this -> assertEquals('/tmp/ccsdtex'          , $compilateur -> get_compileDir());
        $this -> assertEquals('/latexRoot'            , $compilateur -> get_chroot());
        $this -> assertEquals('/latexRoot/tmp/ccsdtex', $compilateur -> chrootedcompileDir());
        $this -> assertEquals('/usr/sbin/chroot'      , $compilateur -> get_chrootcmd());
    }
    
    public function testCompile() {
        foreach (array('/docs/01/01/01/06', '/docs/01/02/01/61', '/docs/01/02/01/56') as $dir) {
            mkdir($this -> temprep, 0777, true) or exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
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
            mkdir($this -> temprep, 0777, true) or exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(false);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(true);
            }
            recurse_rmdir($this -> temprep);
        }
    }
    public function testCompile3() {
        foreach (array('/docs/preprod/01/00/04/28', '/docs/preprod/01/00/07/98', '/docs/preprod/01/00/19/84') as $dir) {
            mkdir($this -> temprep, 0777, true) or exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurse_rmdir($this -> temprep);
        }
    }
    public function testCompile4() {
        foreach (array('/docs/01/34/40/90') as $dir) {
            mkdir($this -> temprep, 0777, true) or exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurse_rmdir($this -> temprep);
        }
    }
    public function testCompile5() {
        foreach (array('/docs/01/37/67/31') as $dir) {
            mkdir($this -> temprep, 0777, true) or exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurse_rmdir($this -> temprep);
        }
    }
}