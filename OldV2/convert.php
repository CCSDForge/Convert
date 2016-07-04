<?php

/**
*
* convert function
*
* @param string $directory base directory
* @param string $format_in input format
* @param string $format_out output format
* @return int Number of converted files in directory/format_in
*
*/
function convert( $directory='', $format_in='DOC', $format_out='PDF' ) {

	$debug = TRUE;
	$correct_formats = array( 'TEX'=>array('PDF'), 'ODF'=>array('PDF'), 'PDF'=>array('TXT'), 'PS'=>array('PDF'), 'IMG'=>array('IMG') );
	$libelle_formats = array('DOC'=>'Word', 'RTF'=>'Rich Text Format', 'ODF'=>'OpenOffice', 'PDF'=>'Adobe PDF', 'PS'=>'PostScript', 'IMG'=>'Image', 'TEX'=>'TeX/LaTeX');
	$mime_formats = array('DOC'=>'application/msword', 'RTF'=>'text/rtf', 'ODF'=>'application/opendocument', 'PDF'=>'application/pdf', 'PS'=>'application/postscript', 'IMG'=>array('image/jpeg', 'image/tiff', 'image/gif', 'image/png', 'application/pdf', 'application/postscript'));
	$cmd = array(	'TEX->PDF'=>'/sites/code/compile/wrapper.pl',
					'PDF->TXT'=>'/opt/xpdf/bin/pdftotext -q',
					'PS->PDF'=>'/usr/bin/ps2pdf14 -sPAPERSIZE=a4 -dMaxSubsetPct=100 -dSubsetFonts=true -dEmbedAllFonts=true',
					'ODF->PDF'=>'/opt/openoffice.org3/program/python /home/ccsd/convert/convert.py',
					'IMG->JPG'=>'/opt/imagemagick/bin/convert -quality 92 -density 300 -quiet');

	$converted_file_number = 0;
	while ( substr($directory, -1) == '/' ) {
		$directory = substr($directory, 0, strlen($directory)-1);
	}
	// Le répertoire de base est connu?
	if ( strlen($directory) && is_dir($directory) ) {
		// Le format d'entrée est connu?
		if ( array_key_exists($format_in, $correct_formats) ) {
			// Le format de sortie est valide?
			if ( in_array($format_out, $correct_formats[$format_in]) ) {
				// Le répertoire d'entrée est valide?
				if ( is_dir($directory.'/'.$format_in) ) {
					// Possibilité de créer le répertoire de sortie
					if ( is_dir($directory.'/'.$format_out) || mkdir($directory.'/'.$format_out) ) {
						if ( $format_in == 'TEX' ) {
							$output = array();
							exec($cmd['TEX->PDF']." '".$directory."'", $output);
							$return = '';
							if ( array_key_exists("0", $output) && $output[0] != "" ) {
								for ( $iline=0; $iline<count($output); $iline++ ) {
									if ( $output[$iline] != '' && mb_stripos($output[$iline], "warning") === false && mb_stripos($output[$iline], "latex warn") === false ) {
										$return .= $output[$iline]."\\n";
									}
								}
							}
							if ( $debug ) {
								$fp = fopen('/sites/php_err/convert.log', 'a');
								if ( $return == '' ) {
									fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' OK'."\n");
								} else {
									fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' NOK'."\n");
								}
								fclose($fp);
							}
							return $return;
						} else {
							foreach ( list_file($directory.'/'.$format_in) as $file ) {
								$filename = str_replace($directory.'/'.$format_in.'/', '', $file);
								$filetype = file_type($file);
								if ( ( is_array($mime_formats[$format_in]) && in_array($filetype, $mime_formats[$format_in]) ) || ( is_string($mime_formats[$format_in]) && $filetype == $mime_formats[$format_in] ) ) {
									$fileout = $directory.'/'.$format_out.'/';
									switch ( $format_in.'->'.$format_out ) {
										case 'ODF->PDF':
											if ( $position = strrpos($filename, '.') ) {
												$fileout .= substr($filename, 0, $position).".pdf";
											} else {
												$fileout .= '.pdf';
											}
											if ( is_file($fileout) ) {
												continue;
											}
											$output = exec($cmd['ODF->PDF'].' '.$file.' '.$fileout, $output, $return);
											if ( $return == 0 ) {
												$converted_file_number++;
												if ( $debug ) {
													$fp = fopen('/sites/php_err/convert.log', 'a');
													fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' - OK for '.$filename."\n");
													fclose($fp);
												}
											}
											break;
										case 'PDF->TXT':
											if ( $position = strrpos($filename, '.') ) {
												$fileout .= substr($filename, 0, $position).".txt";
											} else {
												$fileout .= '.txt';
											}
											if ( is_file($fileout) ) {
												continue;
											}
											putenv('PAPERSIZE=a4');
											$output = exec($cmd['PDF->TXT'].' '.$file.' '.$fileout, $output, $return);
											if ( $return == 0 ) {
												$converted_file_number++;
												if ( $debug ) {
													$fp = fopen('/sites/php_err/convert.log', 'a');
													fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' - OK for '.$filename."\n");
													fclose($fp);
												}
											}
											break;
										case 'PS->PDF':
											if ( $position = strrpos($filename, '.') ) {
												$fileout .= substr($filename, 0, $position).".pdf";
											} else {
												$fileout .= '.pdf';
											}
											if ( is_file($fileout) ) {
												continue;
											}
											$output = exec($cmd['PS->PDF'].' '.$file.' '.$fileout, $output, $return);
											if ( $return == 0 ) {
												$converted_file_number++;
												if ( $debug ) {
													$fp = fopen('/sites/php_err/convert.log', 'a');
													fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' - OK for '.$filename."\n");
													fclose($fp);
												}
											}
											break;
										case 'IMG->IMG':
											if ( $filetype != 'image/jpeg' ) {
												if ( $position = strrpos($filename, '.') ) {
													$fileout .= substr($filename, 0, $position).".jpg";
												} else {
													$fileout .= '.jpg';
												}
												if ( is_file($fileout) ) {
													continue;
												}
												$output = exec($cmd['IMG->JPG'].' '.$file.' '.$fileout, $output, $return);
												if ( $return == 0 ) {
													$converted_file_number++;
													if ( $debug ) {
														$fp = fopen('/sites/php_err/convert.log', 'a');
														fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> JPG - OK for '.$filename."\n");
														fclose($fp);
													}
												}
											}
											break;
									}
								} else {
									if ( $debug ) {
										$fp = fopen('/sites/php_err/convert.log', 'a');
										fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' - Mime type "'.$filetype.'" error'."\n");
										fclose($fp);
									}
								}
							}
							if ( $converted_file_number ) {
								return (int)$converted_file_number;
							} else {
								if ( $debug ) {
									$fp = fopen('/sites/php_err/convert.log', 'a');
									fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' - No convertion have been done'."\n");
									fclose($fp);
								}
								return new SoapFault('Server', "No convertion have been done");
							}
						}
					} else {
						if ( $debug ) {
							$fp = fopen('/sites/php_err/convert.log', 'a');
							fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out." - Can't create directory '".$directory.'/'.$format_out."'"."\n");
							fclose($fp);
						}
						return new SoapFault('Server', "Can't create directory '".$directory.'/'.$format_out."'");
					}
				} else {
					if ( $debug ) {
						$fp = fopen('/sites/php_err/convert.log', 'a');
						fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' - Unknown Directory "'.$directory.'/'.$format_in.'"'."\n");
						fclose($fp);
					}
					return new SoapFault('Server', "Unknown Directory '".$directory.'/'.$format_in."'");
				}
			} else {
				if ( $debug ) {
					$fp = fopen('/sites/php_err/convert.log', 'a');
					fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' - Unsupported output format'."\n");
					fclose($fp);
				}
				return new SoapFault('Server', "Unsupported output format '$format_out' for ".$libelle_formats[$format_in]." conversion");
			}
		} else {
			if ( $debug ) {
				$fp = fopen('/sites/php_err/convert.log', 'a');
				fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' - Unsupported input format'."\n");
				fclose($fp);
			}
			return new SoapFault('Server', "Unsupported input format '$format_in'");
		}
	} else {
		if ( $debug ) {
			$fp = fopen('/sites/php_err/convert.log', 'a');
			fwrite($fp, '['.date('c').'] '.$directory.': '.$format_in.' -> '.$format_out.' - Unknown Directory'."\n");
			fclose($fp);
		}
		return new SoapFault('Server', "Unknown Directory '$directory'");
	}
}

