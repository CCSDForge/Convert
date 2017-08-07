<?php

/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 13/07/17
 * Time: 15:53
 */

require_once "tex.php";

class Ccsd_Tex_CompileTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provide_testcheckTexBinForFile
     * @param $file
     * @param $result
     */
    public function testcheckTexBinForFile($file, $result) {
        $compiler = new Ccsd_Tex_compile('',[]);
        $file_obj = new SplFileObject($file);
        $this -> assertEquals($result, $compiler->checkTexBinForFile($file_obj));
    }

    public function provide_testcheckTexBinForFile() {
        return [
            'pdflatex en latex-command' => [__DIR__ . "/ressources/checkLatexCmd/texWithPdflatex.tex", 'pdflatex'],
            'xelatex  en latex-command' => [__DIR__ . "/ressources/checkLatexCmd/texWithXelatex.tex",'xelatex'],
            'pdflatex en auto'          => [__DIR__ . "/ressources/checkLatexCmd/texWithPdflatexAuto.tex",'pdflatex'],
            'xelatex  en auto'          => [__DIR__ . "/ressources/checkLatexCmd/texWithXelatexAuto.tex",'xelatex'],
            'tex      en auto'          => [__DIR__ . "/ressources/checkLatexCmd/texWithTex.tex",'tex'],
            'Mauvais parametre en latex-commande' => [__DIR__ . "/ressources/checkLatexCmd/MauvaisLatex.tex", ''],
        ];
    }

    /**
     * @dataProvider provide_checklogs
     */
    public function test_checklogs($checkFunction, $file, $result) {
        $compiler = new Ccsd_Tex_compile('',[]);
        $this->assertEquals($result, $compiler -> $checkFunction($file));
    }

    public function provide_checklogs() {
        return [
            1 => ['check_for_bad_citation',    __DIR__ . '/ressources/logs/foo.log', true],
            2 => ['check_for_bad_citation',    __DIR__ . '/ressources/logs/good.log', false],
            3 => ['check_for_bad_natbib',      __DIR__ . '/ressources/logs/Paper.log', true],
            4 => ['check_for_bad_natbib',      __DIR__ . '/ressources/logs/good.log', false],
            5 => ['check_for_bad_reference',   __DIR__ . '/ressources/logs/foo.log', true],
            6 => ['check_for_bad_reference',   __DIR__ . '/ressources/logs/good.log', false],
            7 => ['check_for_reference_change',__DIR__ . '/ressources/logs/foo.log', true],
            8 => ['check_for_reference_change',__DIR__ . '/ressources/logs/good.log', false],
        ];
    }
}
