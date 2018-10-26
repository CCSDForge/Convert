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

$fileCreated1 = [];
$fileCreated2 = [];

// latex ou pdflatex ?
$bin = $compilateur -> checkTexBin();
try {
    $fileCreated1 = $compilateur -> compile($bin,$dir,$tex_files,$filename);
    $fileCreated2 = $compilateur -> runLatex2rtf($tex_files[0]);
    $fileCreated = array_merge($fileCreated1, $fileCreated2);
    foreach ($fileCreated as $file => $destname) {
        copy("$temprep/$file", "$dir/$destname");
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
                                                   
if ( count($fileCreated) ) {
    header('HTTP/1.1 200 OK');
    echo '<files>';
    foreach ($fileCreated as $file) {
        switch (pathinfo($file, PATHINFO_EXTENSION)) {
            case 'pdf': echo '<pdf>' . $file .'</pdf>';
            break;
            case 'rtf': echo '<rdf>' . $file . '</rdf>';
            break;
        }
    }
    echo '</files>';
} else {
    recurse_rmdir($temprep);
    internalServerError('No pdf created');
}
if (! file_exists(BASETEMPREP . DIRECTORY_SEPARATOR. "NO_RM")) {
    recurse_rmdir($temprep);
}
exit;
