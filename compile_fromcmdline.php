<?php
/**
 * Pour utiliser ce script:
 * Creer un repertoire contenant les fichiers sources
 * Passer le parametre --dir avec ce repertoire
*/

$options = getopt("D:");

$directory=$options['D'];
/** Bon ce serait bien d'avoir ca en config globale...  */
define('TEXLIVEVERSION', '2023');
define('BASETEMPREP', '/tmp/ccsdtex');
define('CHROOT', '');
define('LATEX2020', '/usr/local/texlive/2020'); # Latex version fixe 2020
define('LATEX2016', '/usr/local/texlive/2016'); # Latex version fixe 2016
define('LATEX2014', '/usr/local/texlive/2014'); # Latex version fixe 2014
define('LATEX2023', '/usr/local/texlive/2023'); # Latex version fixe 2014

define('ARCH', 'x86_64-linux');
define('LATEX', LATEX2023);     # Latex courant

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
$GLOBALS['xelatex']   = "xelatex -interaction=nonstopmode";

$tempchrootrep = '/home/marmol/tmp/textcompile';
$temprep =  $tempchrootrep;
$withLogFile = true;
$stopOnError = true;

chdir($temprep);
#                                    texlive,             cmd_paths, compile dir  , chroot dir
$compilateur =  new CcsdTexCompile($GLOBALS['texlive'], $GLOBALS, $tempchrootrep, CHROOT, $withLogFile, $stopOnError);

$tex_files = $compilateur -> mainTexFile();
$bin = $compilateur -> checkTexBin();
$fileCreated = $compilateur -> compile($bin, $tempchrootrep,$tex_files, "out.pdf");
