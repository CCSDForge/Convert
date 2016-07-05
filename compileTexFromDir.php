<?php

include "tex.php";

ini_set("memory_limit","-1");
set_time_limit(0);
/*
 *
 * compile method
 *
 *
 * @param string $directory base directory
 * @return string TeX/LaTeX/pdfLaTeX error
 *
 */

define('CHROOT', '/latexRoot');
define('TEXLIVEVERSION', '2014');
define('BASETEMPREP', '/tmp/ccsdtex');
putenv('HOME=/home/nobody');
putenv('TEXMFVAR=/usr/local/texlive/" . TEXLIVEVERSION . "/texmf-var');
putenv('PATH=/usr/local/texlive/" . TEXLIVEVERSION . "/bin/i386-linux/:/usr/bin/:/bin');

$GLOBALS['texlive']   = "/usr/local/texlive/" . TEXLIVEVERSION;
$GLOBALS['path']      = "/usr/local/texlive/" . TEXLIVEVERSION . "/bin/i386-linux/";
$GLOBALS['tex']       = "tex -interaction=nonstopmode";
$GLOBALS['latex']     = "latex -interaction=nonstopmode";
$GLOBALS['pdflatex']  = "pdflatex -interaction=nonstopmode";
$GLOBALS['bibtex']    = "bibtex -terse";
$GLOBALS['makeindex'] = "makeindex -q";
$GLOBALS['dvips']     = "dvips -q -Ptype1";
$GLOBALS['ps2pdf']    = "/usr/bin/ps2pdf14";
$GLOBALS['chroot']    = "/usr/sbin/chroot";

// Fonctions semble-t-il non utilisees
// Deplacees dans OldV2 si besoin

// function compile( $rootrep='' ) {
// }
// function compileFromFile( $content='' ) {
// }

function internalServerError($msg) {
    header('HTTP/1.1 500 Internal Server Error');
    error_log($msg);
    echo $msg;
    exit;
}

if ( $_SERVER['REQUEST_METHOD'] != "POST" ) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Use POST HTTP method';
    exit;
}

# Traitement des parametres post
$dirparam=isset($_POST['dir']) ? $_POST['dir'] : '';
if ( $dirparam == '' ) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Source directory undefined';
    exit;
}
// Répertoire des fichiers sources
$dir = realpath(urldecode($dirparam));
if ( $dir == '' || !is_dir($dir) ) {
    internalServerError('Source directory not exist');
}
if ( !preg_match('/docs/', $dir) ) {
    # Attention, il faut accepter les /docs/xx/xx/xx
    # Et les compilations de frontpage dans /docs/tmp/... 
    internalServerError('Directory is not in the accepted path scope');                                               
}
$dir .= DIRECTORY_SEPARATOR;

$source    = isset($_POST['source']) ? $_POST['source'] : '';
// récupération de la variable stopOnError
$stopOnError = true;   # default value
if ( isset($_POST['stopOnError']) ) {
    $stopOnError = ( $_POST['stopOnError'] == 1 ) ? true : false;
}
// récupération de la variable withLogFile
$withLogFile = true;  # default value
if ( isset($_POST['withLogFile']) ) {
    $withLogFile = ( $_POST['withLogFile'] == 1 ) ? true : false;
}
$filename = isset($_POST['fileName']) ? $_POST['fileName'] : '';

// création du répertoire tempo privé
$tempchrootrep = BASETEMPREP.DIRECTORY_SEPARATOR.uniqid().DIRECTORY_SEPARATOR;
$temprep = CHROOT.$tempchrootrep;
if ( !mkdir($temprep, 0777, true) ) {
    internalServerError('Can\'t make temp directory' . $temprep);
}
// copie fichiers sources dans un répertoire tempo privé
if ( ($source != '') && is_file($dir.DIRECTORY_SEPARATOR.$source) ) { // un fichier source est fourni
    if ( false === copy($dir.DIRECTORY_SEPARATOR.$source, $temprep.$source) ) {
        internalServerError('Can\'t copy source file "'.$dir.DIRECTORY_SEPARATOR.$source.'" to temp directory "'.$temprep.$source.'"');
    }
} else { // on copie tout le répertoire $_POST['dir']
    if ( in_array($dir, array('/docs/tmp/', '/docs/preprod/tmp/', '/docs/test/tmp/')) || false === recurse_copy($dir, $temprep, false) ) {
        internalServerError('Can\'t copy directory "'.$dir.'" to temp directory "'.$temprep.'"');
    }
}
// unzip du répertoire $temprep
recurse_unzip($temprep);
// on se place dans le répertoire de travail tempo privé
chdir($temprep);
// recherche des fichiers à compiler

$compilateur =  new Ccsd_Tex_Compile($GLOBALS['texlive'], $GLOBALS, $tempchrootrep, CHROOT);

$tex_files = $compilateur -> mainTexFile();
if ( count($tex_files) == 0 ) {
    internalServerError('No TeX, LaTeX or PdfLaTeX primary source file found');
}
$pdfCreated = array();
$fileCreated = array();
// latex ou pdflatex ?
$bin = $compilateur -> checkTexBin();
try {
    $fileCreated = $compilateur -> compile($bin,$dir,$tex_files,$filename);
    foreach ($fileCreated as $file => $destname) {
        # error_log("copy $temprep/$file vers, $dir/$destname");
        copy("$temprep/$file", "$dir/$destname");
        if (preg_match('/\.pdf$/', $file)) {
            $pdfCreated[] = $destname;
        }
    }
} catch (TexCompileException $e) {
    internalServerError($e -> getMessage());
}
                                                   
if ( count($pdfCreated) ) {
    header('HTTP/1.1 200 OK');
    echo '<files><pdf>'.implode('</pdf><pdf>', $pdfCreated).'</pdf></files>';
} else {
    internalServerError('No pdf created');
}
recurse_rmdir($temprep);
exit;
