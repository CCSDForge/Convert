<?php

class Ccsd_Meta_Pdf_Test extends PHPUnit_Framework_TestCase {
    // ...
    public function setUp() {
        $this -> tempchrootrep = BASETEMPREP.DIRECTORY_SEPARATOR.uniqid().DIRECTORY_SEPARATOR;
        $this -> temprep = CHROOT.$this -> tempchrootrep;
        $this -> pdfCreated = array();
        $this -> compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $Conf, $tempchrootrep, CHROOT);
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
                $pdfCreated = $compilateur -> compile($bin,$dir,$tex_files,'');
                var_dump($pdfCreated);
                assert_true(true);
            }  catch (TexCompileException $e) {
                assert_true(false);
            }
        
    }
}