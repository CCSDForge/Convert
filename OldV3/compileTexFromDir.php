<?php

include_once 'compile.php';


if ( $_SERVER['REQUEST_METHOD'] == "POST" ) {
    # Traitement des parametre post
    $dirparam=isset($_POST['dir']) ? $_POST['dir'] : '';
	if ( $dirparam == '' ) {
		header('HTTP/1.1 400 Bad Request');
		echo 'Source directory undefined';
		exit;
	}
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
	
	// Répertoire des fichiers sources
	$dir = realpath(urldecode($dirparam));
	if ( $dir == '' || !is_dir($dir) ) {
		header('HTTP/1.1 500 Internal Server Error');
		echo 'Source directory not exist';
		exit;
	}
	$dir .= DIRECTORY_SEPARATOR;
	// création du répertoire tempo privé
	$temprep = BASETEMPREP.DIRECTORY_SEPARATOR.uniqid().DIRECTORY_SEPARATOR;
	if ( !mkdir($temprep, 0777, true) ) {
		header('HTTP/1.1 500 Internal Server Error');
		echo 'Can\'t make temp directory' . $temprep;
		exit;
	}
	// copie fichiers sources dans un répertoire tempo privé
	if ( ($source != '') && is_file($dir.DIRECTORY_SEPARATOR.$source) ) { // un fichier source est fourni
        if ( false === copy($dir.DIRECTORY_SEPARATOR.$source, $temprep.$source) ) {
			header('HTTP/1.1 500 Internal Server Error');
			echo 'Can\'t copy source file "'.$dir.DIRECTORY_SEPARATOR.$source.'" to temp directory "'.$temprep.$source.'"';
			exit;
		}
	} else { // on copie tout le répertoire $_POST['dir']
		if ( in_array($dir, array('/docs/tmp/', '/docs/preprod/tmp/', '/docs/test/tmp/')) || false === recurse_copy($dir, $temprep, false) ) {
			header('HTTP/1.1 500 Internal Server Error');
			echo 'Can\'t copy directory "'.$dir.'" to temp directory "'.$temprep.'"';
			exit;
		}
	}
	// unzip du répertoire $temprep
	recurse_unzip($temprep);
	// on se place dans le répertoire de travail tempo privé
	chdir($temprep);
	// recherche des fichiers à compiler
	$tex_files = mainTexFile();
	if ( count($tex_files) == 0 ) {
		header('HTTP/1.1 500 Internal Server Error');
		echo 'No TeX, LaTeX or PdfLaTeX primary source file found';
		exit;
	}
	$pdfCreated = array();
	// latex ou pdflatex ?
	$bin = checkTexBin();
	foreach ( $tex_files as $tex_file ) {
		$main_tex_file = mb_substr($tex_file, 0, -4);
		// compilation
		unlinkTexTmp($main_tex_file);
		if ( runTex($bin, $main_tex_file) ) {
			if ( $stopOnError && ( ( $if = check_for_bad_inputfile($main_tex_file) ) != '' ) ) {
				header('HTTP/1.1 500 Internal Server Error');
				echo $if.' not found';
				exit;
			}
			if ( $stopOnError && ( !(is_file($main_tex_file.'.dvi') || is_file($main_tex_file.'.pdf')) ) ) {
				if ( $withLogFile && is_file($main_tex_file.'.log') && filesize($main_tex_file.'.log') > 0 ) {
					copy($main_tex_file.'.log', $dir.$main_tex_file.'.log');
				}
				header('HTTP/1.1 500 Internal Server Error');
				echo 'Latex produced no output for the compilation of '.$main_tex_file;
				exit;
			}
			// Check LaTeX Error
			if ( $stopOnError && ( ( $error = check_for_compilation_error($main_tex_file) ) != '')  ) {
				if ( $withLogFile && is_file($main_tex_file.'.log') && filesize($main_tex_file.'.log') > 0 ) {
					copy($main_tex_file.'.log', $dir.$main_tex_file.'.log');
				}
				header('HTTP/1.1 500 Internal Server Error');
				echo 'Latex produced an error "'.$error.'" for the compilation of '.$main_tex_file;
				exit;
			}
			// MakeIndex ?
			if ( is_file($main_tex_file.'.idx') && is_executable(substr($GLOBALS['makeindex'], 0, strpos($GLOBALS['makeindex'], ' '))) ) {
				@exec($GLOBALS['makeindex']." ".escapeshellarg($main_tex_file), $makeindex);
				if ( $stopOnError && count($makeindex) ) {
					header('HTTP/1.1 500 Internal Server Error');
					echo 'Makeindex found an error for the compilation of '.$main_tex_file;
					exit;
				}
			}
			// BibTeX, Citations resolved
			if ( !is_file($main_tex_file.'.bbl') || filesize($main_tex_file.'.bbl') == 0 || check_for_bad_citation($main_tex_file) ) {
				@exec($GLOBALS['bibtex']." ".escapeshellarg($main_tex_file)." > bibtex.log 2>&1", $bibtex);
			}
			if ( $stopOnError && is_file($main_tex_file.'.bib') && check_for_bibtex_errors($main_tex_file) ) {
				header('HTTP/1.1 500 Internal Server Error');
				echo 'Bibtex reported an error for the compilation of '.$main_tex_file;
				exit;
			}
			runTex($bin, $main_tex_file);
            check_for_reference_change($main_tex_file) and runTex($bin, $main_tex_file);
            check_for_reference_change($main_tex_file) and runTex($bin, $main_tex_file);

            # Recuperation des logs Latex
            if ( $withLogFile && is_file($main_tex_file.'.log') && filesize($main_tex_file.'.log') > 0 ) {
                copy($main_tex_file.'.log', $dir.$main_tex_file.'.log');
            }
			if ( $stopOnError && check_for_bad_reference($main_tex_file) ) {
				header('HTTP/1.1 500 Internal Server Error');
				echo 'LaTeX could not resolve all references for the compilation of '.$main_tex_file;
				exit;
			}
			if ( $stopOnError && check_for_bad_citation($main_tex_file) ) {
				header('HTTP/1.1 500 Internal Server Error');
				echo 'LaTeX could not resolve all citations or labels for the compilation of '.$main_tex_file;
				exit;
			}
			if ( $stopOnError && check_for_bad_natbib($main_tex_file) ) {
				header('HTTP/1.1 500 Internal Server Error');
				echo 'Package natbib Error: Bibliography not compatible with author-year citations for the compilation of '.$main_tex_file;
				exit;
			}
			// Make ps and pdf from dvi if needed
			if ( is_file($main_tex_file.'.dvi') && is_executable(substr($GLOBALS['dvips'], 0, strpos($GLOBALS['dvips'], ' '))) ) {
				@exec($GLOBALS['dvips'].' "'.$main_tex_file.'.dvi" -o "'.$main_tex_file.'.ps" > ./dvips.log 2>&1');
				if ( is_file($main_tex_file.'.ps') && is_executable($GLOBALS['ps2pdf']) ) {
					@exec($GLOBALS['ps2pdf'].' "'.$main_tex_file.'.ps" "'.$main_tex_file.'.pdf"');
				} else {
					header('HTTP/1.1 500 Internal Server Error');
					echo 'Could not find or make ps from dvi file for the compilation of '.$main_tex_file;
					exit;
				}
			}
			// Copy pdf compiled file
			if ( is_file($main_tex_file.'.pdf') ) {
				$out = ( count($tex_files) == 1 && ($filename != '') ) ? $filename :  $main_tex_file.'.pdf';
				copy($main_tex_file.'.pdf', $dir.$out);
				$pdfCreated[] = $out;
				if ( $withLogFile && is_file($main_tex_file.'.bbl') && filesize($main_tex_file.'.bbl') > 0 ) {
					copy($main_tex_file.'.bbl', $dir.$main_tex_file.'.bbl');
				}
			} else {
				header('HTTP/1.1 500 Internal Server Error');
				echo 'Could not find or save pdf file for the compilation of '.$main_tex_file;
				exit;
			}
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			echo 'Could not compile file '.$main_tex_file;
			exit;
		}
	}
	recurse_rmdir($temprep);
	if ( count($pdfCreated) ) {
		header('HTTP/1.1 200 OK');
		echo '<files><pdf>'.implode('</pdf><pdf>', $pdfCreated).'</pdf></files>';
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		echo 'No pdf created';
	}
	exit;
} else {
	header('HTTP/1.1 400 Bad Request');
	echo 'Use POST HTTP method';
	exit;
}

function recurse_copy($src, $dst, $create=true) {
    $copy = true;
    $dir = opendir($src);
    if ( $create ) {
    	@mkdir($dst);
    }
    while(false !== ( $file = readdir($dir) ) ) {
        if ( ( $file != '.' ) && ( $file != '..' ) ) {
            if ( is_dir($src . DIRECTORY_SEPARATOR . $file) ) {
                $copy = $copy && recurse_copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
            } else {
                $copy = $copy && copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
            }
        }
    }
    closedir($dir);
    return $copy;
}

function recurse_rmdir($dir) {
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
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
