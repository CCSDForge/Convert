<?php

define('BASETEMPREP', '/tmp/ccsdtex');
define('CHROOT', '/latexRoot');
define('LATEX', '/usr/local/texlive/2016');     # Latex courant
define('LATEX2016', '/usr/local/texlive/2016'); # Latex version fixe 2016
define('LATEX2014', '/usr/local/texlive/2014'); # Latex version fixe 2014

require __DIR__ . "/../tex.php";

class Ccsd_Compile_Test2 extends PHPUnit_Framework_TestCase {
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
        $this -> Conf['texlive']   = "/usr/local/texlive/2011";
        $this -> Conf['tex']       = "tex -interaction=nonstopmode";
        $this -> Conf['latex']     = "latex -interaction=nonstopmode";
        $this -> Conf['pdflatex']  = "pdflatex -interaction=nonstopmode";
        $this -> Conf['bibtex']    = "bibtex -terse";
        $this -> Conf['makeindex'] = "makeindex -q";
        $this -> Conf['dvips']     = "dvips -q -Ptype1";
        $this -> Conf['ps2pdf']    = "/usr/bin/ps2pdf14";
        $this -> Conf['chroot']    = "/usr/sbin/chroot";
        $this -> compilateur = new Ccsd_Tex_Compile(LATEX, $this -> Conf, $this -> tempchrootrep, CHROOT, true, false, true);

        parent::setUp();
    }

    public function testCompileArxiv() {
        # Le fichier tex est unique mais mal reconnu comme fichier principal
        # Comme il est unique, cela doit marcher

        # Actuellement, il manque un fichier pour la compilation passe, mais c'est un pb tex
        foreach (array('/docs/01/38/07/07') as $dir) {
            mkdir($this -> temprep, 0777, true) || exit;
            recurse_copy($dir, $this -> temprep, false);
            recurse_unzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this->fileCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(false);
            }  catch (TexCompileException $e) {
                if ($e -> getMessage() == "Could not find pdf file for the compilation of dependentTypesForGames") {
                    $this -> assertTrue(true);
                } else {
                    $this -> assertTrue(false);
                }
            }
            recurse_rmdir($this -> temprep);
        }
    }
    
}