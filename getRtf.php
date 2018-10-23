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
 * Il ne doit y avoir que UN SEUL fichier tex!
 */

include __DIR__ . "/prefix.php";




$pdfCreated = array();
$fileCreated = array();
// latex ou pdflatex ?
$bin = $compilateur -> checkTexBin();
try {
    $fileCreated  = $compilateur -> compile($bin,$dir,$tex_files,$filename);
    $pdfCreated[] = $compilateur -> runLatex2rtf($tex_files[0]);

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
