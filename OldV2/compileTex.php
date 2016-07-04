<?php

include_once 'compile.php';

if ( $_SERVER['REQUEST_METHOD'] == "POST" ) {
	$content = file_get_contents('php://input');
	// copie du zip dans un répertoire tempo privé
	$temprep = BASETEMPREP.'/'.uniqid();
	if ( !mkdir($temprep, 0777, true) ) {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
	if (false === file_put_contents($temprep.'/tmp.zip', base64_decode($content))) {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
	$zip = new ZipArchive;
	if ( $zip->open($temprep.'/tmp.zip') === true ) {
		$zip->extractTo($temprep);
		$zip->close();
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
	// on se place dans le répertoire de travail tempo privé
	chdir($temprep);
	// recherche du fichier principal
	$tex_file = mainTexFile();
	if ( count($tex_file) != 1 ) {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
	$main_tex_file = mb_substr($tex_file[0], 0, -4);
	// latex ou pdflatex ?
	$bin = checkTexBin();
	// compilation
	unlinkTexTmp($main_tex_file);
	if ( runTex($bin, $main_tex_file) ) {
		if ( ( $if = check_for_bad_inputfile($main_tex_file) ) != '' ) {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
		if ( !(is_file($main_tex_file.'.dvi') || is_file($main_tex_file.'.pdf')) ) {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
		// MakeIndex ?
		if ( is_file($main_tex_file.'.idx') && is_executable(substr($GLOBALS['makeindex'], 0, strpos($GLOBALS['makeindex'], ' '))) ) {
			@exec($GLOBALS['makeindex']." ".escapeshellarg($main_tex_file), $makeindex);
			if ( count($makeindex) ) {
				header('HTTP/1.1 500 Internal Server Error');
				exit;
			}
		}
		// BibTeX, Citations resolved
		if ( !is_file($main_tex_file.'.bbl') || filesize($main_tex_file.'.bbl') == 0 || check_for_bad_citation($main_tex_file) ) {
			@exec($GLOBALS['bibtex']." ".escapeshellarg($main_tex_file)." > bibtex.log 2>&1", $bibtex);
		}
		if ( is_file($main_tex_file.'.bib') && check_for_bibtex_errors($main_tex_file) ) {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
		runTex($bin, $main_tex_file);
		if ( check_for_reference_change($main_tex_file) ) {
			runTex($bin, $main_tex_file);
		}
		// Make ps and pdf from dvi if needed
		if ( is_file($main_tex_file.'.dvi') && is_executable(substr($GLOBALS['dvips'], 0, strpos($GLOBALS['dvips'], ' '))) ) {
			@exec($GLOBALS['dvips'].' "'.$main_tex_file.'.dvi" -o "'.$main_tex_file.'.ps" > ./dvips.log 2>&1');
			if ( is_file($main_tex_file.'.ps') && is_executable($GLOBALS['ps2pdf']) ) {
				@exec($GLOBALS['ps2pdf'].' "'.$main_tex_file.'.ps" "'.$main_tex_file.'.pdf"');
			} else {
				header('HTTP/1.1 500 Internal Server Error');
				exit;
			}
		}
		//Output pdf compiled file
		if ( is_file($main_tex_file.'.pdf') ) {
			echo base64_encode(file_get_contents($main_tex_file.'.pdf'));
			exit;
		} else {
			header('HTTP/1.1 500 Internal Server Error');
			exit;
		}
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		exit;
	}
} else {
	header('HTTP/1.1 400 Bad Request');
	exit;
}
