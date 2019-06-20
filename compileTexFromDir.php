<?php

/*
  En entree, la requete est:
    de type POST
    parametres:
       - dir: (commence par  /docs/ ou /cache)
       - source: 
       - stopOnError: 
       - withLogFile: 
       - fileName: 
 */
/**
 * LIMITS: Original documents must be in directory name as /docs/ or /cache/
 *         The regep is not in config yet
 */

include __DIR__ . "/tex.php";
ini_set("memory_limit","-1");
set_time_limit(0);

/* Pour transition entre ccsd06 et MV */
$arch = php_uname('m');
if ($arch == 'x86_64') {
    define('ARCH', 'x86_64-linux');
    define('TEXLIVEVERSION', '2016');
} else {
    define('ARCH', 'i386-linux');
    define('TEXLIVEVERSION', '2014');
}

/** Default configuration */

/* Les constantes correspondantes seront definies apres l'include de la configuration */
$CHROOT='/latexRoot';
$BASETEMPREP='/tmp/ccsdtex';
$HOME='/home/nobody';

$GLOBALS['texlive']   = "/usr/local/texlive/" . TEXLIVEVERSION;
$GLOBALS['path']      = "/usr/local/texlive/" . TEXLIVEVERSION . '/bin/' . ARCH . '/';
$GLOBALS['tex']       = "tex -interaction=nonstopmode";
$GLOBALS['latex']     = "latex -interaction=nonstopmode";
$GLOBALS['pdflatex']  = "pdflatex -interaction=nonstopmode";
$GLOBALS['xelatex']   = "xelatex -interaction=nonstopmode";
$GLOBALS['bibtex']    = "bibtex -terse";
$GLOBALS['makeindex'] = "makeindex -q";
$GLOBALS['dvips']     = "dvips -q -Ptype1";
$GLOBALS['ps2pdf']    = "/usr/bin/ps2pdf14";
$GLOBALS['chroot']    = "/usr/sbin/chroot";

$conffile = __DIR__ . "/conf.php";
if (file_exists($conffile)) {
    include $conffile;
}

define('CHROOT', $CHROOT);
define('BASETEMPREP', $BASETEMPREP);
putenv("HOME=$HOME");
putenv('TEXMFVAR=/usr/local/texlive/' . TEXLIVEVERSION . '/texmf-var');
putenv('PATH=/usr/local/texlive/'     . TEXLIVEVERSION . '/bin/' . ARCH . '/:/usr/bin/:/bin');

function internalServerError($msg) {
    header('HTTP/1.1 500 Internal Server Error');
    print("$msg\n");
    exit;
}

if ( $_SERVER['REQUEST_METHOD'] != "POST" ) {
    header('HTTP/1.1 400 Bad Request');
    print('Use POST HTTP method');
    exit;
}

# Traitement des parametres post
$dirparam=isset($_POST['dir']) ? $_POST['dir'] : '';
if ( $dirparam == '' ) {
    header('HTTP/1.1 400 Bad Request');
    print('Source directory undefined');
    exit;
}
// Répertoire des fichiers sources
$dir = realpath(urldecode($dirparam));
if ( $dir == '' || !is_dir($dir) ) {
    internalServerError("Source directory '$dir' not exist");
}
if ( !preg_match('+(^/docs/|/cache|/nas/spm/docs|/nas/docs)+', $dir) ) {   # /docs/... pour Hal ou  pour sc
    # Attention, il faut accepter les /docs/xx/xx/xx
    # Et les compilations de frontpage dans /docs/tmp/... 
    internalServerError("Directory '$dir' is not in the accepted path scope");                                               
}
$dir .= DIRECTORY_SEPARATOR;

$source    = isset($_POST['source']) ? $_POST['source'] : '';
// récupération de la variable stopOnError
$stopOnError = true;   # default value
if ( isset($_POST['stopOnError']) ) {
    $stopOnError = ( $_POST['stopOnError'] == 1 );
}
// récupération de la variable withLogFile
$withLogFile = true;  # default value
if ( isset($_POST['withLogFile']) ) {
    $withLogFile = ( $_POST['withLogFile'] == 1 );
}
$filename = isset($_POST['fileName']) ? $_POST['fileName'] : '';

// création du répertoire tempo privé
$tempchrootrep = BASETEMPREP.DIRECTORY_SEPARATOR.uniqid('', true).DIRECTORY_SEPARATOR;
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
    if ( in_array($dir, array('/docs/tmp/', '/docs/preprod/tmp/', '/docs/test/tmp/','/nas/spm/docs/tmp')) || false === recurse_copy($dir, $temprep, false) ) {
        internalServerError('Can\'t copy directory "'.$dir.'" to temp directory "'.$temprep.'"');
    }
}
// unzip du répertoire $temprep
recurse_unzip($temprep);
// on se place dans le répertoire de travail tempo privé
chdir($temprep);
// recherche des fichiers à compiler

$compilateur =  new Ccsd_Tex_Compile($GLOBALS['texlive'], $GLOBALS, $tempchrootrep, CHROOT, $withLogFile, $stopOnError);

$tex_files = $compilateur -> mainTexFile();
if ( count($tex_files) == 0 ) {
    recurse_rmdir($temprep);
    internalServerError('No TeX, LaTeX or PdfLaTeX primary source file found');
}
$pdfCreated = array();
$fileCreated = array();
// latex ou pdflatex ?
$bin = $compilateur -> checkTexBin();
try {
    $fileCreated = $compilateur -> compile($bin,$dir,$tex_files,$filename);
    foreach ($fileCreated as $file => $destname) {
        copy("$temprep/$file", "$dir/$destname");
        if (preg_match('/\.pdf$/', $file)) {
            $pdfCreated[] = $destname;
        }
    }
} catch (TexCompileException $e) {
    // Recuperation des log en cas d'erreur!
    $logfile = $e -> logfile;
    error_log("$tempchrootrep ne compile pas. Logfile: $logfile . Et message: " . $e ->getMessage());
    if (isset($logfile) && file_exists($logfile)) {
        copy("$temprep/$logfile", "$dir/$logfile");
    }
    recurse_rmdir($temprep);
    internalServerError($e -> getMessage());
}
                                                   
if ( count($pdfCreated) ) {
    header('HTTP/1.1 200 OK');
    echo '<files><pdf>'.implode('</pdf><pdf>', $pdfCreated).'</pdf></files>';
} else {
    recurse_rmdir($temprep);
    internalServerError('No pdf created');
}
if (! file_exists(BASETEMPREP . DIRECTORY_SEPARATOR. "NO_RM")) {
    recurse_rmdir($temprep);
}
exit;
