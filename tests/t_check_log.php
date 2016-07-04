<?php

include __DIR__ . "/../tex.php";

chdir(__DIR__);

$nbok=0;
$nbnok=0;
$nbtests=0;

function assert_equal($res, $value, $msg='') {
    global $nbtests,$nbnok,$nbok;
    $nbtests++;
    if ($res == $value) {
        #print "ok\n";
        $nbok++;
    } else {
        print "nok: ($res != $value)  $msg\n";
        $nbnok++;
    }
}
function assert_true($value, $msg='') {
    global $nbtests,$nbnok,$nbok;
    $nbtests++;
    if ($value) {
        #print "ok\n";
        $nbok++;
    } else {
        print "nok: $msg\n";
        $nbnok++;
    }
}

function assert_false($value, $msg='') {
    global $nbtests,$nbnok,$nbok;
    $nbtests++;
    if ($value) {
        print "nok: $msg\n";
        $nbnok++;
    } else {
        #print "ok\n";
        $nbok++;
    }
}
function assert_non_empty($value, $msg='') {
    global $nbtests,$nbnok,$nbok;
    $nbtests++;
    if ($value != '') {
        #print "ok\n";
        $nbok++;
    } else {
        print "nok: $msg\n";
        $nbnok++;
    }
}

$Conf['texlive']   = "/usr/local/texlive/2014";
$Conf['tex']       = "tex -interaction=nonstopmode";
$Conf['latex']     = "latex -interaction=nonstopmode";
$Conf['pdflatex']  = "pdflatex -interaction=nonstopmode";
$Conf['bibtex']    = "bibtex -terse";
$Conf['makeindex'] = "makeindex -q";
$Conf['dvips']     = "dvips -q -Ptype1";
$Conf['ps2pdf']    = "/usr/bin/ps2pdf14";
$Conf['chroot']    = "/usr/sbin/chroot";

define('BASETEMPREP', '/tmp/ccsdtex');
define('CHROOT', '/latexRoot');

$compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $Conf, '.', '');

assert_non_empty($compilateur -> check_for_compilation_error('f1'));
assert_true($compilateur -> check_for_bad_citation('f1'));
assert_false($compilateur -> check_for_bad_natbib('f1'));

$compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $Conf, __DIR__.'/exemple1', '');
assert_equal( __DIR__.'/exemple1', $compilateur -> chrootedcompileDir());
assert_equal('latex',$compilateur -> checkTexBin(), "Pb pour determiner le type du fichier tex 1");
assert_equal(__DIR__.'/exemple1', $compilateur -> get_compileDir());


$compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $Conf, BASETEMPREP, CHROOT);

assert_true($compilateur -> is_chrooted());
assert_true($compilateur -> is_executable('/usr/local/texlive/2014/bin/i386-linux/pdflatex'), "Pb d'exe latex");
assert_false($compilateur -> is_executable('/usr/local/texlive/2014/bin/i386-linux/Fooprgm'), "Pb d'exe autre");
assert_equal('/tmp/ccsdtex'          , $compilateur -> get_compileDir());
assert_equal('/latexRoot'            , $compilateur -> get_chroot());
assert_equal('/latexRoot/tmp/ccsdtex', $compilateur -> chrootedcompileDir());
assert_equal('/usr/sbin/chroot'      , $compilateur -> get_chrootcmd());




if (is_dir('/docs/00')) {
    # on peut tester avec de vrai exemple
    
    foreach (array('/docs/01/01/01/06', '/docs/01/02/01/61', '/docs/01/02/01/56') as $dir) {
        $tempchrootrep = BASETEMPREP.DIRECTORY_SEPARATOR.uniqid().DIRECTORY_SEPARATOR;
        $temprep = CHROOT.$tempchrootrep;
        $pdfCreated = array();
        $compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $Conf, $tempchrootrep, CHROOT);

        mkdir($temprep, 0777, true) or exit;
        recurse_copy($dir, $temprep, false);
        recurse_unzip($temprep);
        chdir($temprep);
        $bin = $compilateur -> checkTexBin();
        $tex_files = $compilateur -> mainTexFile();
        try {
            $pdfCreated = $compilateur -> compile($bin,$dir,$tex_files,'');
            var_dump($pdfCreated);
            assert_true(true);
        }  catch (TexCompileException $e) {
            assert_true(false);
        }
    }


    foreach (array('/docs/01/10/08/11') as $dir) {
        $tempchrootrep = BASETEMPREP.DIRECTORY_SEPARATOR.uniqid().DIRECTORY_SEPARATOR;
        $temprep = CHROOT.$tempchrootrep;
        $pdfCreated = array();
        $compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $Conf, $tempchrootrep, CHROOT);

        mkdir($temprep, 0777, true) or exit;
        recurse_copy($dir, $temprep, false);
        recurse_unzip($temprep);
        chdir($temprep);
        $bin = $compilateur -> checkTexBin();
        $tex_files = $compilateur -> mainTexFile();
        try {
            $pdfCreated = $compilateur -> compile($bin,$dir,$tex_files,'');
            assert_true(false);
        } catch (TexCompileException $e) {
            assert_true(true);
        }
    }

    foreach (array('/docs/preprod/01/00/04/28', '/docs/preprod/01/00/07/98', '/docs/preprod/01/00/19/84') as $dir) {
        $tempchrootrep = BASETEMPREP.DIRECTORY_SEPARATOR.uniqid().DIRECTORY_SEPARATOR;
        $temprep = CHROOT.$tempchrootrep;
        $pdfCreated = array();
        $compilateur = new Ccsd_Tex_Compile("/usr/local/texlive/2014", $Conf, $tempchrootrep, CHROOT);

        mkdir($temprep, 0777, true) or exit;
        recurse_copy($dir, $temprep, false);
        recurse_unzip($temprep);
        chdir($temprep);
        $bin = $compilateur -> checkTexBin();
        $tex_files = $compilateur -> mainTexFile();
        try {
            $pdfCreated = $compilateur -> compile($bin,$dir,$tex_files,'');
            var_dump($pdfCreated);
            assert_true(true);
        }  catch (TexCompileException $e) {
            assert_true(false);
        }
    }

}

print "Nb tests: $nbtests\n";
print "   Ok: $nbok\n";
print "   NOk: $nbnok\n";
?>