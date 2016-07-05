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
                var_dump($pdfCreated);
                $this -> assertTrue(true);
            }  catch (TexCompileException $e) {
                $this -> assertTrue(false);
            }
        }
    }
}