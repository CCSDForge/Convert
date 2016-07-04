<?php

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

define('TEXLIVEVERSION', '2014');
define('BASETEMPREP', '/tmp/ccsdtex');
putenv('HOME=/home/nobody');
putenv('TEXMFVAR=/usr/local/texlive/" . TEXLIVEVERSION . "/texmf-var');
putenv('PATH=/usr/local/texlive/" . TEXLIVEVERSION . "/bin/i386-linux/:/usr/bin/:/bin');

$GLOBALS['path']      = "/usr/local/texlive/" . TEXLIVEVERSION . "/bin/i386-linux/";
$GLOBALS['tex']       = $GLOBALS['path']."tex -interaction=nonstopmode";
$GLOBALS['latex']     = $GLOBALS['path']."latex -interaction=nonstopmode";
$GLOBALS['pdflatex']  = $GLOBALS['path']."pdflatex -interaction=nonstopmode";
$GLOBALS['bibtex']    = $GLOBALS['path']."bibtex -terse";
$GLOBALS['makeindex'] = $GLOBALS['path']."makeindex -q";
$GLOBALS['dvips']     = $GLOBALS['path']."dvips -q -Ptype1";
$GLOBALS['ps2pdf']    = "/usr/bin/ps2pdf14";
$GLOBALS['chroot']    = "/usr/sbin/chroot";

// Fonctions semble-t-il non utilisees
// Deplacees dans OldV2 si besoin

// function compile( $rootrep='' ) {
// }
// function compileFromFile( $content='' ) {
// }

function runTex($bin, $file, $chrootdir="", $dir='') {
    #if ( is_executable(substr($GLOBALS[$bin], 0, strpos($GLOBALS[$bin], ' '))) ) {
    echo 'Execution du chroot';
    $latexcmd = $GLOBALS[$bin]." ".escapeshellarg($file);
    $shellcmd = "cd  $dir;$latexcmd";
    $chroot=$GLOBALS['chroot'];
    $cmd = "sudo $chroot $chrootdir bash -c " . escapeshellarg($shellcmd);
    echo $cmd;
    exec($cmd, $output);
    return ( count($output) ) ? true : false;
	#}
	return false;
}

function check_for_bad_inputfile($file) {
	if ( is_file($file.'.log') ) {
		foreach ( file($file.'.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
			if ( preg_match('/^No file (.*)\./', $line, $match) || preg_match('/File (.*) not found\./', $line, $match) ) {
				if ( preg_match('/["`\']?'.$file.'["`\']?\.(aux|bbl|ind|toc|brf|lof|lot)/', $match[1]) ) {
					continue;
				}
				return str_replace(array("'", '"', '`'), '', $match[1]);
			}
		}
		return '';
	}
	return $file.'.log';
}

function check_for_compilation_error($file) {
	if ( is_file($file.'.log') ) {
		foreach ( file($file.'.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
			if (    preg_match('/latex error/i', $line)
                 || preg_match('/found error:/i', $line)
                 || preg_match('/dvips error/i', $line)
                 || preg_match('/emergency stop/i', $line)
                 || preg_match('/Undefined control sequence/i', $line) ) {
				return str_replace(array("'", '"', '`'), '', $line);
			}
		}
		return '';
	}
	return '';
}

function check_for_bad_citation($file) {
	if ( is_file($file.'.log') ) {
		foreach ( file($file.'.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
			if ( preg_match('/Warning: Citation.*undefined/', $line) || preg_match('/Warning:.*undefined citations/', $line) ) {
				return true;
			}
		}
		return false;
	}
	return true;
}

function check_for_bad_natbib($file) {
	if ( is_file($file.'.log') ) {
		foreach ( file($file.'.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
			if ( preg_match('/^! Package natbib Error/', $line) ) {
				return true;
			}
		}
		return false;
	}
	return true;
}

function check_for_bad_reference($file) {
	if ( is_file($file.'.log') ) {
		foreach ( file($file.'.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
			if ( preg_match('/Warning: Reference.*&quot;/', $line) ) {
				return true;
			}
		}
		return false;
	}
	return true;
}

function check_for_reference_change($file) {
	if ( is_file($file.'.log') ) {
		foreach ( file($file.'.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
			if ( preg_match('/Rerun/', $line) ) {
				return true;
			}
		}
		return false;
	}
	return true;
}

function check_for_bibtex_errors($file) {
	if ( is_file($file.'.blg') ) {
		foreach ( file($file.'.blg', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
			if ( preg_match('/error message/', $line) ) {
				return true;
			}
		}
		return false;
	}
	return true;
}

function mainTexFile() {
	$tex_files = array();
	foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator('.') ) as $file ) {
		if ( $file->isFile() && preg_match('/\.tex$/i', $file->getFilename()) ) {
			foreach ( file($file->getFilename(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
				if ( preg_match('/^\s*(\\\\begin\s*{document}|\\\\bye\s*$|\\\\end\s*$|\\\\documentstyle)/', $line) && !in_array($file->getFilename(), $tex_files) ) {
					$tex_files[] = $file->getFilename();
				}
			}
		}
	}
	return $tex_files;
}

function checkTexBin() {
	$bin = 'latex';
	foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator('.') ) as $file ) {
		if ( $file->isFile() && preg_match('/\.(tex|pdf_t|tex_t)$/i', $file->getFilename()) ) {
			$il = 0;
			foreach ( file($file->getFilename(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line ) {
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
