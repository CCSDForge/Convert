<?php

namespace Ccsd\Convert\Tests;

require_once __DIR__ . "/../tex.php";
require_once __DIR__ . "/../Convert/Config.php";
require_once __DIR__ . "/ccsdTestCase.php";

use CcsdTexCompile;
use TexCompileException;
use Convert\Config;

class FirstTest extends CcsdTestCase {

    public function testValues1() {
        $compil = new CcsdTexCompile(LATEX, $this -> config, '.', '');
        $this -> assertNotEmpty($compil -> checkForCompilationError('f1'));
        $this -> assertTrue((bool) $compil -> checkForBadCitation('f1'));
        $this -> assertFalse((bool) $compil -> checkForBadNatbib('f1'));

    }

    public function testValues2() {
        $file =  __DIR__.'/exemple1';
        $compil = new CcsdTexCompile(LATEX, $this -> config, $file, '');
        $this -> assertEquals( $file, $compil -> chrootedCompileDir());
        $this -> assertEquals('latex',$compil -> checkTexBin(), "Pb pour determiner le type du fichier tex 1");
        $this -> assertEquals($file, $compil -> getCompileDir());
    }

    public function testValues3() {
        $compil = new CcsdTexCompile(LATEX, $this -> config, BASETEMPREP, CHROOT);

        $config= Config::getConfig();
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
            }  catch (\TexCompileException $e) {
                $this -> fail();
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
            }  catch (\TexCompileException $e) {
                $this -> assertTrue(true);
            }
            recurseRmdir($this -> temprep);
        }
    }
    public function testCompile3() {
        foreach (array('/docs/preprod/01/00/04/28', '/docs/preprod/01/00/07/98', '/docs/preprod/01/00/19/84', '/docs/01/38/07/39' ) as $dir) {
            $this->copyDocuments($dir, $this -> temprep);
            recurseUnzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (\TexCompileException $e) {
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
            }  catch (\TexCompileException $e) {
                print "Error: " . $e->getMessage() . "\n";
                $this -> assertTrue(false);
            }
            recurseRmdir($this -> temprep);
        }
    }
    public function testCompile5() {
        foreach (array('/docs/05/10/40/35') as $dir) {
            $this->copyDocuments($dir, $this -> temprep);
            recurseUnzip($this -> temprep);
            chdir($this -> temprep);
            $bin = $this -> compilateur -> checkTexBin();
            $tex_files = $this -> compilateur -> mainTexFile();
            try {
                $this -> pdfCreated = $this -> compilateur -> compile($bin,$dir,$tex_files,'');
                $this -> assertTrue(true);
            }  catch (\TexCompileException $e) {
                $this -> assertTrue(false);
            }
            recurseRmdir($this -> temprep);
        }
    }
}
