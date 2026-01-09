<?php

namespace Ccsd\Convert\Tests;

require_once __DIR__ . "/../tex.php";
require_once __DIR__ . "/../Convert/Config.php";
require_once __DIR__ . "/ccsdTestCase.php";

use CcsdTexCompile;
use TexCompileException;

class SecondTest extends CcsdTestCase {

    public function testCompileArxiv() {

        foreach (array('/docs/05/10/40/35') as $dir) {
            $this->copyDocuments($dir, $this -> temprep);
            recurseUnzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this->fileCreated = $this -> compilateur -> compile($bin,$this->testDocRoot . $dir,$tex_files,'');
                $this -> fail("This test must fail");
            }  catch (TexCompileException $e) {
                $this -> assertTrue(true);
            }
            recurseRmdir($this -> temprep);
        }
    }
}
