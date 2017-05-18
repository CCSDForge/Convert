<?php
/**
 * Pour utiliser ce script:
 * Creer un repertoire contenant les fichiers sources
 * Passer le parametre --dir avec ce repertoire
*/

$options = getopt("D:");

$directory=$options['D'];

if (php_uname('m') == 'x86_64') {
    define('ARCH', 'x86_64-linux');
    define('TEXLIVEVERSION', '2016');
} else {
    define('ARCH', 'i386-linux');
    define('TEXLIVEVERSION', '2014');
}
define('BASETEMPREP', '/tmp/ccsdtex');
define('CHROOT', '');
define('LATEX2016', '/usr/local/texlive/2016'); # Latex version fixe 2016
define('LATEX2014', '/usr/local/texlive/2014'); # Latex version fixe 2014
if (php_uname('m') == 'x86_64') {
    define('LATEX', '/usr/local/texlive/2016');     # Latex courant
} else {
    define('LATEX', '/usr/local/texlive/2014');     # Latex courant
}

require __DIR__ . "/tex.php";
$GLOBALS['texlive']   = LATEX;
$GLOBALS['path']      = LATEX . '/bin/' . ARCH . '/';
$GLOBALS['tex']       = "tex -interaction=nonstopmode";
$GLOBALS['latex']     = "latex -interaction=nonstopmode";
$GLOBALS['pdflatex']  = "pdflatex -interaction=nonstopmode";
$GLOBALS['bibtex']    = "bibtex -terse";
$GLOBALS['makeindex'] = "makeindex -q";
$GLOBALS['dvips']     = "dvips -q -Ptype1";
$GLOBALS['ps2pdf']    = "/usr/bin/ps2pdf14";
$GLOBALS['chroot']    = "/usr/sbin/chroot";

$tempchrootrep = '/home/marmol/tmp/textcompile';
$temprep =  $tempchrootrep;
$withLogFile = true;
$stopOnError = true;

chdir($temprep);
#                                    texlive,             cmd_paths, compile dir  , chroot dir
$compilateur =  new Ccsd_Tex_Compile($GLOBALS['texlive'], $GLOBALS, $tempchrootrep, CHROOT, $withLogFile, $stopOnError);

$tex_files = $compilateur -> mainTexFile();
$bin = $compilateur -> checkTexBin();
$fileCreated = $compilateur -> compile($bin, $tempchrootrep,$tex_files, "out.pdf");
