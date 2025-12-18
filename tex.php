<?php
/*
   Tools for LaTeX compilation
   Auteur: B Marmol from code of  L Capelli/Y Barborini...
   Jul 2016 / May 2017
   You can compile into chroot environnement or not
   The directory of source files is only read access
 */

class TexCompileException extends Exception {

    public $logfile;

    public function __construct($msg, $file = null) {
        parent::__construct($msg);
        $this->logfile =  $file;
    }
}

class Ccsd_Tex_Compile {
    const ACCEPTED_LATEX_COMMAND_REGEXP = "pdflatex|latex|xelatex|tex";

    /** @var string  */
    private $chroot;
    /** @var string  */
    private $compileDir;
    /** @var bool */
    private $stopOnError;
    /** @var bool  */
    private $withLogFile;
    /** @var string[]  */
    private $path=array();
    /** @var bool */
    private $debug=false;
    /** @var string */
    private $Texversion=null;
    /** @var string */
    public $Arch = null;
    /** @var string  */
    private static $ChrootCMD='/usr/sbin/chroot';

    /**
     * Ccsd_Tex_Compile constructor.
     * @param string   $texlivepath
     * @param string[] $paths
     * @param string   $compildir
     * @param string   $chrootdir
     * @param bool     $withLogFile
     * @param bool     $stopOnError
     * @param bool     $debug
     */
    public function __construct($texlivepath, $paths, $compildir='.',$chrootdir='', $withLogFile=true, $stopOnError=true, $debug=false) {
        $this -> chroot      = $chrootdir;
        $this -> compileDir  = $compildir;
        $this -> withLogFile = $withLogFile;
        $this -> stopOnError = $stopOnError;
        $this -> debug       = $debug;

        $config = \Convert\Config::getConfig();

        if ($config->get('use.docker')){
            self::$ChrootCMD = 'docker exec -i convert-latex-1';
        }

        $arch = php_uname('m');
        /** Bon ce serait bien d'avoir ca en config globale...  */
        $this -> Arch = 'x86_64-linux';
        $this -> Texversion= defined('TEXLIVEVERSION') ? TEXLIVEVERSION : '2020';

        foreach ($paths as $type => $cmd) {
            if (preg_match('/tex|latex|pdflatex|bibtex|makeindex|dvips|ps2pdf/',$type)) {
                if (preg_match('+^/+', $cmd)) {
                    $this -> path[$type] = $cmd;
                } else {
                    $this -> path[$type] = $texlivepath . '/bin/' . $this -> Arch . '/' . $cmd;
                }
            }
        }
        putenv("TEXMFVAR=$texlivepath/texmf-var");
        putenv("PATH=$texlivepath/bin/" . $this -> Arch . "/:/usr/bin/:/bin");
    }

    /**
     * @param $msg
     */
    function debug($msg) {
        if ($this->debug) {
            error_log($msg);
        }
    }

    /**
     * @return string
     */
    function get_compileDir() {
        return $this -> compileDir;
    }

    /**
     * @return string
     */
    function get_chroot() {
        return $this -> chroot;
    }
    /**
     * @return string
     */
    function get_chrootcmd() {
        return self::$ChrootCMD;
    }
    /**
     * @return bool
     */
    function stopOnError() {
        return $this -> stopOnError;
    }
    /**
     * @return bool
     */
    function withLogFile() {
        return $this -> withLogFile;
    }
    /**
     * @return bool
     */
    function is_chrooted() {
        return ($this -> chroot != '');
    }

    /**
     * @return string
     */
    function chrootedcompileDir() {
        return $this-> chroot . $this -> compileDir;
    }

    /**
     *  Like php: is_executable but add chroot prefix if necessairy
     * Cmd is comand line with argument, We clean argument before testing execution status
     * @param string
     * @return boolean
     * */
    function is_executable($cmd) {
        if ( strpos($cmd, ' ') >0) {
            $binpath=$this ->chroot . substr($cmd, 0, strpos($cmd, ' '));
        } else {
            $binpath=$this ->chroot . $cmd;
        }
        return (is_executable($binpath));
    }

    /**
     * @param $cmd
     * @return mixed
     */
    function runCmd($cmd) {
        $shellcmd = "cd  " . $this->get_compileDir() .";$cmd";
        $chrootcmd=$this -> get_chrootcmd();
        $chrootdir=$this -> get_chroot();
        if ($this -> is_chrooted()) {
            $chrootedcmd = "sudo $chrootcmd $chrootdir bash -c " . escapeshellarg($shellcmd);
        } else {
            $chrootedcmd = $cmd;
        }

        @exec($chrootedcmd, $output);

        return ($output);
    }

    /**
     * @param string $bin
     * @param string $file
     * @return bool
     */
    function runTex($bin, $file) {
        $cmd = $this->path[$bin];
        if ( $this->is_executable($cmd) ) {
            $latexcmd = $cmd." ".escapeshellarg($file);
            $output = $this->runCmd($latexcmd);
            return (count($output) > 0);
        }
        return false;
    }

    /**
     * @param string $texfile
     * @return string
     * @throws TexCompileException
     */
    function runLatex2rtf($texfile) {

        $cmd = $this->path['latex2rtf'];
        $rtffile = str_replace('.tex', '.rtf', $texfile);
        if (is_file($texfile) && $this->is_executable($cmd) ) {
            $cmd = $cmd ." ".escapeshellarg($texfile);
            $this -> runCmd($cmd);
            if ( ! is_file($rtffile)) {
                throw new TexCompileException("Latex2Rtf can't produce rtf file");
            }
        }
        return [ $rtffile => $rtffile ];
    }


    /**
     * Run bibtex if needed
     * @param string $main_tex_file
     * @return string // bibtex output or empty
     */
    function maybeRunBibtex($main_tex_file)
    {
        $bibtex = '';
        $cmd = $this->path['bibtex'];
        $cmd = $cmd." ".escapeshellarg($main_tex_file)." > bibtex.log 2>&1";

        if (($this->check_for_bad_citation($main_tex_file) && is_file($main_tex_file . '.bib'))
            /* There is biblio errors and a bib file exists: we generate a bbl file. */
        || ( !is_file($main_tex_file.'.bbl') || filesize($main_tex_file.'.bbl') == 0)) {
            /* This case when there is only a bibliographie into tex file, so non reference  on error but we need a bbl */
            $bibtex = $this->runCmd($cmd);
        }
        /* OLD TEST
           !is_file($main_tex_file.'.bbl')
        || filesize($main_tex_file.'.bbl') == 0
        || $this -> check_for_bad_citation($main_tex_file)
        */
        return $bibtex;
    }

    /**
     * Run makeindex if necessary and return makeindex cmd output or empty
     * @param string $main_tex_file  // filename
     * @return string
     * @throws TexCompileException
     */
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

    /**
     * Make ps and pdf from dvi if needed
     * @param string $main_tex_file  // filename
     * @throws TexCompileException
     */
    function dvi2pdf($main_tex_file) {
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
        // Else If pdflatex, no dvi created...
    }

    /**
     * @return string[] // List of filename of TeX file to compile
     *                  // Ie: contening a \begin{document}, or other TeX mark...
     */
    function mainTexFile() {
        $tex_files = array();
        $only_one_file = '';
        $nbr_tex_file = 0;
        foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator($this->chrootedcompileDir()) ) as $file ) {
            $filename = $file->getFilename(); // Only final name: better for testing extension
            $pathname = $file->getPathname();
            if ( $file->isFile() && preg_match('/\.tex$/i', $filename) ) {
                $nbr_tex_file++;
                $only_one_file = $filename;
                foreach ( file($pathname, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
                    if ( preg_match('/^\s*(\\\\begin\s*{document}|\\\\bye\s*$|\\\\end\s*$|\\\\documentstyle)/', $line) && !in_array($filename, $tex_files) ) {
                        $tex_files[] = $filename;
                        // No need to continue, file est a TeX file,
                        // TODO The !in_array seems to be not usefull
                        break;
                    }
                }
            }
        }
        if (($nbr_tex_file == 1) && (count($tex_files) == 0)) {
            # Seulement un fichier tex et il n'a pas ete detecte comme contenant un begin document...
            # On le prends quand meme
            $tex_files[] = $only_one_file;
        }
        return $tex_files;
    }

    /**
     * @param RecursiveDirectoryIterator $file
     * @return string
     */
    function checkTexBinForFile($file) {
        $il = 0;
        $pathname = $file->getPathname();
        $command = '';
        foreach ( file($pathname, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
            $matches = [];
            if ( preg_match('/%% +-\*- +latex-command: *(' . self::ACCEPTED_LATEX_COMMAND_REGEXP .') *-\*-/', $line, $matches)) {
                $command =  $matches[1];
                break;
            }
            if ( preg_match('/^[^%]*\\\\(includegraphics|includepdf|epsfig)[^%]*\.(pdf|png|gif|jpg)\s*}/i', $line)
                || ( $il++ < 10 &&  preg_match('/^[^%]*\\\\pdfoutput\s*=\s*1/', $line) ) ) {
                $command =  'pdflatex';
                break;
            }
            if ( preg_match('/^[^%]*(\\\\bye|\\\\end)\s*$/', $line) ) {
                $command =  'tex';
                break;
                            }
            if ( preg_match('/^[^%]*\\\\usepackage{xltxtra}\s*$/', $line) ) {
                $command = 'xelatex';
                break;
            }
        }
        return $command;
    }
    /*
     * Determine flavor of TeX for the file: latex, pdflatex, tex or xelatex
     * Ok... the last is the winner!
     */
    function checkTexBin() {
        $bin = 'latex';  // par defaut
        foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator($this->chrootedcompileDir()) ) as $file ) {
            $filename = $file->getFilename();  // Only final name: better for testing extension
            if ( $file->isFile() && preg_match('/\.(tex|pdf_t|tex_t)$/i', $filename) ) {
                $bin4file = $this->checkTexBinForFile($file);
                if ($bin4file != '') {
                    $bin = $bin4file;
                    break;
                }
            }
        }
        return $bin;
    }

    /*
     * Search pattern in file
     *       file is either an array of string line, either a filename
     * If bool is true, return value is a boolean (true if we find pattern, false if not)
     * If bool is false, return matching line if exists or empty string in case of no match
     * characters of array $filter are suppressed  from the string result
     * $option corresponds to the php: file() function options
     */
    static function check_for_line($file, $pattern, $bool=false, $option=null, $filter = array("'", '"', '`')) {
        if ($option == null) {
            $option=FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES;
        }
        $content=array();
        if ( is_array($file)) {
            $content = $file;
        } elseif (is_file($file) ) {
            $content = file($file, $option);
        } else {
            error_log("$file is a bad param for check_for_line");
            return $bool ? false : '';
        }
        foreach ($content as $line) {
            if ( preg_match($pattern, $line)) {
                if ($filter) {
                    return $bool || str_replace($filter, '', $line);
                } else {
                    return $bool || $line;
                }
            }
        }
        return !$bool && '';
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
        // Rerun is not sufficient... : Package: rerunfilecheck 2016/05/16 v1.8 Rerun checks for auxiliary file
        return self::check_for_line($file.'.log', '/Rerun to /', true);
    }

    static function check_for_bibtex_errors($file) {
        $bibtexlogfile = $file.'.blg';
        if (file_exists($bibtexlogfile)) {
            return self::check_for_line($bibtexlogfile, '/error message/', true);
        } else {
            return false;
        }
    }
    /*
     * @return string // filename of needed file marked as 'Not found by tex cmd in log file
     *     $file.'.log' if it doesn't exists
     *     filename found in log: eg $file.aux, $file.bbl,...
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

    /**
     * @param string $bin
     * @param string $fromdir
     * @param string[] $tex_files
     * @param string $filename
     * @return array
     * @throws TexCompileException
     */
    function compile($bin, $fromdir, $tex_files, $filename) {
        $filesCreated = array();
        foreach ( $tex_files as $tex_file ) {
            $this -> debug("Treat file: $tex_file");
            $logfile=null;
            $main_tex_file = mb_substr($tex_file, 0, -4);
            // compilation
            unlinkTexTmp($main_tex_file);
            // -----------------------------------------------------
            // Premiere compilation
            if ( !$this -> runTex($bin, $main_tex_file)) {
                throw new TexCompileException('Could not compile file '.$main_tex_file);
            }
            if ($this -> withLogFile() && is_file($main_tex_file.'.log') && filesize($main_tex_file.'.log') > 0 ) {
                    $logfile = $main_tex_file.'.log';
            }
            if ( $this -> stopOnError()) {
                if (($if = $this->check_for_bad_inputfile($main_tex_file)) != '') {
                    throw new TexCompileException($if . ' not found', $logfile);
                }
                if (!(is_file($main_tex_file . '.dvi') || is_file($main_tex_file . '.pdf'))) {
                    throw new TexCompileException('Latex produced no output for the compilation of ' . $main_tex_file, $logfile);
                }
                // Check LaTeX Error
                if (($error = $this->check_for_compilation_error($main_tex_file)) != '') {
                    throw new TexCompileException('Latex produced an error "' . $error . '" for the compilation of ' . $main_tex_file, $logfile);
                }
            }

            // MakeIndex ?
            $this -> maybeRunMakeindex($main_tex_file);
            // -----------------------------------------------------
            // BibTeX, Citations resolved
            $this -> maybeRunBibtex($main_tex_file);
            if ( $this -> stopOnError() && is_file($main_tex_file.'.bib') && $this -> check_for_bibtex_errors($main_tex_file) ) {
                throw new TexCompileException('Bibtex reported an error for the compilation of '.$main_tex_file);
            }

            /** TODO  We should read tex log file once...
             * To avoid reading it  for  maybeRunBibtex, check_for_bad_citation, check_for_bad_reference,...
             * runtex returns those logs
             * */
            // -----------------------------------------------------
            // Second and supplementary latex compilation for crossreferences
            $this -> runTex($bin, $main_tex_file);
            $this -> check_for_reference_change($main_tex_file) && $this -> runTex($bin, $main_tex_file);
            $this -> check_for_reference_change($main_tex_file) && $this -> runTex($bin, $main_tex_file);

            # getting latex logs filename
            if ( $this -> withLogFile() && is_file($main_tex_file.'.log') && filesize($main_tex_file.'.log') > 0 ) {
                $logfile = $main_tex_file.'.log';
            }
            if ($this -> stopOnError()) {
                if ($this->check_for_bad_reference($main_tex_file)) {
                    throw new TexCompileException('LaTeX could not resolve all references for the compilation of ' . $main_tex_file, $logfile);
                }
                if ($this->check_for_bad_citation($main_tex_file)) {
                    throw new TexCompileException('LaTeX could not resolve all citations or labels for the compilation of ' . $main_tex_file, $logfile);
                }
                if ($this->check_for_bad_natbib($main_tex_file)) {
                    throw new TexCompileException('Package natbib Error: Bibliography not compatible with author-year citations for the compilation of ' . $main_tex_file, $logfile);
                }
            }
            // -----------------------------------------------------
            $this -> dvi2pdf($main_tex_file);

            // Prepare return value:
            //  ==> The set of all interesting files (log, bbl, pdf)
            if ($logfile) {
                // Log file in all cases!
                $filesCreated[$logfile] = $logfile;
            }
            if ( is_file($main_tex_file.'.pdf') ) {
                // bbl is given only with pdf
                $out = ( count($tex_files) == 1 && ($filename != '') ) ? $filename :  $main_tex_file.'.pdf';
                $filesCreated[$main_tex_file.'.pdf'] = $out;
                if ( $this -> withLogFile() && is_file($main_tex_file.'.bbl') && filesize($main_tex_file.'.bbl') > 0 ) {
                    $filesCreated[$main_tex_file.'.bbl'] = $main_tex_file.'.bbl';
                }
            } else {
                $logmode = $this -> withLogFile() ? "WithLog" : "WithOutLog";
                $mode    = $this -> stopOnError() ? "StopOnError" : "NoStopOnError";
                throw new TexCompileException('Could not find pdf file for the compilation of '.$main_tex_file . "  (mode: $mode, $logmode)" , $logfile );
            }
        }
        return($filesCreated);
    }
}
/**
 * Delete temporary latex file to begin a clean compilation
 * bbl file can be given by user: don't delete it!
 * @param string $file
 * @return true
 */
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
}
/*
 * Copy  recursively $src to $dst
 * @return boolean // true if ok, false if A copy generate an error
 * BEWARE:
 *    Symbolic link relatively linked to outside of $src will be incorrect
 *    Absolute Symbolic link to inside of $src will be false
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
/**
 * @param $dir
 * @return bool
 */
function recurse_rmdir($dir) {
    foreach (scandir($dir) as $file) {
        if ( ( $file == '.' ) || ( $file == '..' ) ) {
            continue;
        }
        (is_dir($dir.DIRECTORY_SEPARATOR.$file)) ? recurse_rmdir($dir.DIRECTORY_SEPARATOR.$file) : unlink($dir.DIRECTORY_SEPARATOR.$file);
    }
    return rmdir($dir);
}
/**
 * @param $src
 */
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
