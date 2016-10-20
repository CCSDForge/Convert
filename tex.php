<?php

/* 
   Utilitaire de compilation latex
   Auteur: B Marmol a partir du code de L Capelli/Y Barborini...
   Jul 2016
   Permet la compilation en environnement Chroot ou non/
   Le repertoire src des fichiers n'est accede qu'en lecture
 */

class TexCompileException extends Exception {
    public $logfile;

    function __construct($msg, $file = null) {
        parent::__construct($msg);
        $this->logfile =  $file;
    }
}

class Ccsd_Tex_Compile {
    private $chroot;
    private $compileDir;
    private $stopOnError;
    private $withLogFile;
    private $MyLogFile;
    private $path=array();
    private static $ChrootCMD='/usr/sbin/chroot';

    function __construct($texlivepath, $paths, $compildir='.',$chrootdir='', $withLogFile=true, $stopOnError=true, $debug=false) {
        $this -> chroot = $chrootdir;
        $this -> compileDir = $compildir;
        $this -> withLogFile = $withLogFile;
        $this -> stopOnError = $stopOnError;
        $this -> debug = $debug;

        foreach ($paths as $type => $cmd) {
            if (preg_match('/tex|latex|pdflatex|bibtex|makeindex|dvips|ps2pdf/',$type)) {
                if (preg_match('+^/+', $cmd)) {
                    $this -> path[$type] = $cmd;
                } else {
                    $this -> path[$type] = $texlivepath . '/bin/i386-linux/' . $cmd;
                }
            }
        }
        putenv("TEXMFVAR=$texlivepath/texmf-var");
        putenv("PATH=$texlivepath/bin/i386-linux/:/usr/bin/:/bin");

    }

    function debug($msg) {
        if ($this->debug) {
            error_log($msg);
        }
    }

    function get_compileDir() {
        return $this -> compileDir;
    }
    function get_chroot() {
        return $this -> chroot;
    }
    function get_chrootcmd() {
        return self::$ChrootCMD;
    }
    function stopOnError() {
        return $this -> stopOnError;
    }
    function withLogFile() {
        return $this -> withLogFile;
    }
    function is_chrooted() {
        return ($this -> chroot != '');
    }
    function chrootedcompileDir() {
        return $this-> chroot . $this -> compileDir;
    }
    # Comme is_executable mais ajoute le prefix de chroot si necessaire
    # Cmd est une ligne de commande avec argument, on retire les arguments
    function is_executable($cmd) {
        $chroot = $this ->chroot;
        if ( strpos($cmd, ' ') >0) {
            $binpath=$chroot . substr($cmd, 0, strpos($cmd, ' '));
        } else {
            $binpath=$chroot . $cmd;
        }
        return (is_executable($binpath));
    }

    function runCmd($cmd) {
        $shellcmd = "cd  " . $this->get_compileDir() .";$cmd";
        $chrootcmd=$this -> get_chrootcmd();
        $chrootdir=$this -> get_chroot();
        if ($this -> is_chrooted()) {
            $chrootedcmd = "sudo $chrootcmd $chrootdir bash -c " . escapeshellarg($shellcmd);
        } else {
            $chrootedcmd = $cmd;
        }
        # $this -> debug("Run: $chrootedcmd  in  " . getcwd());
        @exec($chrootedcmd, $output);
        # $this -> debug(implode($output));
        return ($output);
    }

    function runTex($bin, $file) {
        $cmd = $this->path[$bin];
        if ( $this->is_executable($cmd) ) {
            $latexcmd = $cmd." ".escapeshellarg($file);
            $output = $this->runCmd($latexcmd);
            return ( count($output) ) ? true : false;
        }
        return false;
    }

    /* Lance bibtex si necessaire et retourne la sortie de la commande bibtex ou vide */
    function maybeRunBibtex($main_tex_file) {
        $bibtex = '';
        $cmd = $this->path['bibtex'];
        if ( !is_file($main_tex_file.'.bbl') || filesize($main_tex_file.'.bbl') == 0 || $this -> check_for_bad_citation($main_tex_file) ) {
            $cmd =  $cmd." ".escapeshellarg($main_tex_file)." > bibtex.log 2>&1";
            $bibtex = $this -> runCmd($cmd);
        }
        return $bibtex;
    }

    /* Lance makeindex si necessaire et retourne la sortie de la commande makeindex ou vide */
    function maybeRunMakeindex($main_tex_file) {
        $makeindex = '';
        $cmd = $this->path['makeindex'];
        if ( is_file($main_tex_file.'.idx') && $this->is_executable($cmd) ) {
            $cmd = $cmd ." ".escapeshellarg($main_tex_file);
            $makeindex = $this -> runCmd($cmd);
            if ( $this -> stopOnError () && count($makeindex) ) {
                throw new TexCompileException('Makeindex found an error for the compilation of '.$main_tex_file);
            }
        }
        return $makeindex;
    }

    function dvi2pdf($main_tex_file) {
        // Make ps and pdf from dvi if needed
        $cmd = $this->path['dvips'];
        if ( is_file($main_tex_file.'.dvi') && $this->is_executable($cmd)) {
            $cmd = "$cmd '$main_tex_file.dvi' -o '$main_tex_file.ps' > ./dvips.log 2>&1";
            $this->runCmd($cmd);
            $cmd = $this->path['ps2pdf'];
            if ( is_file($main_tex_file.'.ps') && $this->is_executable($cmd) ) {
                $cmd = "$cmd '$main_tex_file.ps' '$main_tex_file.pdf'";
                $this->runCmd($cmd);
            } else {
                throw new TexCompileException('Could not find or make ps from dvi file for the compilation of '.$main_tex_file);
            }
        }
    }

    function mainTexFile() {
        $tex_files = array();
        $only_one_file = '';
        $nbr_tex_file = 0;
        foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator($this->chrootedcompileDir()) ) as $file ) {
            $filename = $file->getFilename(); // seulement le nom final: mieux pour tester l'extension
            $pathname = $file->getPathname();
            if ( $file->isFile() && preg_match('/\.tex$/i', $filename) ) {
                $nbr_tex_file++;
                $only_one_file = $filename;
                foreach ( file($pathname, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
                    if ( preg_match('/^\s*(\\\\begin\s*{document}|\\\\bye\s*$|\\\\end\s*$|\\\\documentstyle)/', $line) && !in_array($filename, $tex_files) ) {
                        $tex_files[] = $filename;
                        // Pas la peine de continuer, le fichier est bien un tex
                        // le !in_array doit aussi etre en trop!
                        break;
                    }
                }
            }
        }
        if (($nbr_tex_file == 1) && (count($tex_files) == 0)) {
            # Seulement un fichier tex et il n'a pas ete detecte...
            # On le prends quand meme
            $tex_files[] = $only_one_file;
        }
        return $tex_files;
    }

    /*
     * Determine le type du fichier tex pour savoir si on lance latex, pdflatex, tex ou xelatex
     * Bon, le dernier a affecter la valeur a raison!
     */
    function checkTexBin() {
        $bin = 'latex';
        foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator($this->chrootedcompileDir()) ) as $file ) {
            $filename = $file->getFilename();  // seulement le nom final: mieux pour tester l'extension
            $pathname = $file->getPathname();
            if ( $file->isFile() && preg_match('/\.(tex|pdf_t|tex_t)$/i', $filename) ) {
                $il = 0;
                foreach ( file($pathname, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
                    if (   preg_match('/^[^%]*\\\\(includegraphics|includepdf|epsfig)[^%]*\.(pdf|png|gif|jpg)\s*}/i', $line)
                           || ( $il++ < 10 &&  preg_match('/^[^%]*\\\\pdfoutput\s*=\s*1/', $line) ) ) {
                        $bin = 'pdflatex';
                        break;
                    }
                    if ( preg_match('/^[^%]*(\\\\bye|\\\\end)\s*$/', $line) ) {
                        $bin = 'tex';
                        break;
                    }
                    if ( preg_match('/^[^%]*\\\\usepackage{xltxtra}\s*$/', $line) ) {
                        $bin = 'xelatex';
                        break;
                    }
                }
            }
        }
        return $bin;
    }

    /*
     * Chercher dans file le pattern
     * Si bool est vrai, la valeur de retour est un booleen (vrai si trouve, faux si pas trouve)
     * Si bool est faux, alors la ligne matchant est retourne, chaine vide en cas d'echec
     * La valeur de retour chaine est filtrer en supprimant les caracteres du tableau $filter
     * $option correspond aux options de la fonction php: file()
     */
    static function check_for_line($file, $pattern, $bool=false, $option=null, $filter = array("'", '"', '`')) {
        if ($option == null) {
            $option=FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES;
        }
        if ( is_array($file)) {
            $content = $file;
        } elseif (is_file($file) ) {
            $content = file($file, $option);
        }
        foreach ($content as $line) {
            if ( preg_match($pattern, $line)) {
                if ($filter) {
                    return $bool ? true : str_replace($filter, '', $line);
                } else {
                    return $bool ? true : $line;
                }
            }
        }
        return $bool ? false : '';
    }

    static function check_for_compilation_error($file) {
        return self::check_for_line($file.'.log','/latex error|found error:|dvips error|emergency stop|Undefined control sequence/');
    }

    static function check_for_bad_citation($file) {
        return self::check_for_line($file.'.log', '/Warning: (Citation.*undefined|.*undefined citations)/', true);
    }

    static function check_for_bad_natbib($file) {
        return self::check_for_line($file.'.log', '/^! Package natbib Error/', true);
    }

    static function check_for_bad_reference($file) {
        return self::check_for_line($file.'.log', '/Warning: Reference.*&quot;/', true) ;
    }

    static function check_for_reference_change($file) {
        return self::check_for_line($file.'.log', '/Rerun/', true);
    }

    static function check_for_bibtex_errors($file) {
        return self::check_for_line($file.'.blg', '/error message/', true);
    }
    /*
     * Valeur de retour, le nom d'un fichier manquant
     *     $file.'.log' ou le nom trouve dans l'expression $file.aux, $file.bbl,...
     */
    static function check_for_bad_inputfile($file) {
        if ( is_file($file.'.log') ) {
            foreach ( file($file.'.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
                if ( preg_match('/^No file (.*)\./', $line, $match) || preg_match('/File (.*) not found\./', $line, $match) ) {
                    if ( preg_match('/[\"\`\']?'.$file.'[\'\`\"]?\.(aux|bbl|ind|toc|brf|lof|lot)/', $match[1]) ) {
                        continue;
                    }
                    return str_replace(array("'", '"', '`'), '', $match[1]);
                }
            }
            return '';
        }
        return $file.'.log';
    }

    function compile($bin, $fromdir, $tex_files, $filename) {
        $destdir = $this -> chrootedcompileDir();
        $filesCreated = array();
        foreach ( $tex_files as $tex_file ) {
            $this -> debug("Treat file: $tex_file");
            $logfile=null;
            $main_tex_file = mb_substr($tex_file, 0, -4);
            // compilation
            unlinkTexTmp($main_tex_file);
            // Premiere compilation
            if ( $this -> runTex($bin, $main_tex_file) == false ) {
                throw new TexCompileException('Could not compile file '.$main_tex_file);
            }
            if ( $this -> stopOnError() && ( ( $if = $this -> check_for_bad_inputfile($main_tex_file) ) != '' ) ) {
                throw new TexCompileException($if.' not found');
            }
            if ( $this -> stopOnError() && ( !(is_file($main_tex_file.'.dvi') || is_file($main_tex_file.'.pdf')) ) ) {
                if ( $this -> withLogFile() && is_file($main_tex_file.'.log') && filesize($main_tex_file.'.log') > 0 ) {
                    $logfile = $main_tex_file.'.log';
                }
                throw new TexCompileException('Latex produced no output for the compilation of '.$main_tex_file, $logfile);
            }
            // Check LaTeX Error
            if ( $this -> stopOnError() && ( ( $error = $this -> check_for_compilation_error($main_tex_file) ) != '')  ) {
                if ( $this -> withLogFile() && is_file($main_tex_file.'.log') && filesize($main_tex_file.'.log') > 0 ) {
                    $logfile = $main_tex_file.'.log';
                    # copy($main_tex_file.'.log', $fromdir.$main_tex_file.'.log');
                }
                throw new TexCompileException('Latex produced an error "'.$error.'" for the compilation of '.$main_tex_file, $logfile);
            }
            // MakeIndex ?
            $makeindex = $this -> maybeRunMakeindex($main_tex_file);
            // BibTeX, Citations resolved
            $bibtex = $this -> maybeRunBibtex($main_tex_file);

            if ( $this -> stopOnError() && is_file($main_tex_file.'.bib') && $this -> check_for_bibtex_errors($main_tex_file) ) {
                throw new TexCompileException('Bibtex reported an error for the compilation of '.$main_tex_file);
            }

            $this -> runTex($bin, $main_tex_file);
            $this -> check_for_reference_change($main_tex_file) and $this -> runTex($bin, $main_tex_file);
            $this -> check_for_reference_change($main_tex_file) and $this -> runTex($bin, $main_tex_file);

            # Recuperation des logs Latex

            if ( $this -> withLogFile() && is_file($main_tex_file.'.log') && filesize($main_tex_file.'.log') > 0 ) {
                $logfile = $main_tex_file.'.log';
            }
            if ( $this -> stopOnError() && $this -> check_for_bad_reference($main_tex_file) ) {
                throw new TexCompileException('LaTeX could not resolve all references for the compilation of '.$main_tex_file, $logfile);
            }
            if ( $this -> stopOnError() && $this -> check_for_bad_citation($main_tex_file) ) {
                throw new TexCompileException('LaTeX could not resolve all citations or labels for the compilation of '.$main_tex_file, $logfile);
            }
            if ( $this -> stopOnError() && $this -> check_for_bad_natbib($main_tex_file) ) {
                throw new TexCompileException('Package natbib Error: Bibliography not compatible with author-year citations for the compilation of '.$main_tex_file, $logfile);
            }
            $this -> dvi2pdf($main_tex_file);


            // Preparation de la valeur de retour:
            //  ==> L'ensemble des fichiers construits interessants (log, bbl, pdf)
            if ($logfile) {
                $filesCreated[$logfile] = $logfile;
            }
            if ( is_file($main_tex_file.'.pdf') ) {
                $out = ( count($tex_files) == 1 && ($filename != '') ) ? $filename :  $main_tex_file.'.pdf';
                $filesCreated[$main_tex_file.'.pdf'] = $out;
                if ( $this -> withLogFile() && is_file($main_tex_file.'.bbl') && filesize($main_tex_file.'.bbl') > 0 ) {
                    $filesCreated[$main_tex_file.'.bbl'] = $main_tex_file.'.bbl';
                }
            } else {
                throw new TexCompileException('Could not find pdf file for the compilation of '.$main_tex_file, $logfile );
            }
        }
        return($filesCreated);
    }
}

/* Suppression des fichiers temporaire Latex pour commencer une compilation propre */
function unlinkTexTmp($file) {
	foreach( array('.dvi', '.pdf', '.ps', '.log', '.toc', '.idx', '.aux', '.auk', '.blg', '.ilg', '.lof', '.lot', '.dep', '.out') as $ext ) {
		if ( is_file($file.$ext) ) {
			@unlink($file.$ext);
		}
	}
	foreach( array('bibtex.log', 'dvips.log') as $f ) {
		if ( is_file($f) ) {
			@unlink($f);
		}
	}
	return true;
};

/*
 * Copie recursivement $src vers $dst
 * Valeur de retour: true si ok, false si une copy a generee une erreur.
 * Attention:
 *    Les liens symboliques pointant en relatif a l'exterieur de $src seront faux
 *    Les liens symboliques pointant en absolus a l'interieur de la zone seront faux
 */

function recurse_copy($src, $dst, $create=true) {
    $copy = true;
    $dir = opendir($src);
    if ( $create ) {
        @mkdir($dst);
    }
    while(false !== ( $file = readdir($dir) ) ) {
        if ( ( $file == '.' ) || ( $file == '..' ) ) {
            continue;
        }
        if ( is_dir($src . DIRECTORY_SEPARATOR . $file) ) {
            $copy = $copy && recurse_copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
        } else {
            $copy = $copy && copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
        }
    }
    closedir($dir);
    return $copy;
}

function recurse_rmdir($dir) {
    foreach (scandir($dir) as $file) {
        if ( ( $file == '.' ) || ( $file == '..' ) ) {
            continue;
        }
        (is_dir($dir.DIRECTORY_SEPARATOR.$file)) ? recurse_rmdir($dir.DIRECTORY_SEPARATOR.$file) : unlink($dir.DIRECTORY_SEPARATOR.$file);
    }
    return rmdir($dir);
}

function recurse_unzip($src) {
    do {
        $continue = false;
        foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator($src) ) as $file ) {
            if ( $file->isFile() && preg_match('/\.zip$/i', $file->getFilename()) ) {
                $zip = new ZipArchive;
                if ( $zip->open($file->getPathname()) ) {
                    $zip->extractTo($file->getPathInfo()->getRealPath());
                    unlink($file->getPathname());
                }
                $zip -> close();
                $continue = true;
            }
        }
    } while ($continue);
}
