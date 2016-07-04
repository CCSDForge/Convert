<?php

/**
*
* langdetect
*
* @param string $text data
* @return code langue or null
*
*/

$GLOBALS['langdetect'] = "/usr/bin/java -jar /soft/langdetect/lib/langdetect.jar --detectlang -d /soft/langdetect/profiles/";

function langdetect( $text='' ) {
	if ( $text && file_put_contents($tmpfname = tempnam("/tmp", "ld"), mb_strtolower($text)) ) {
		@exec($GLOBALS['langdetect']." ".escapeshellarg($tmpfname), $output);
		unlink($tmpfname);
		if ( count($output) && preg_match('/:\[([a-z]+):([0-9\.]+)(\]|,)/', $output[0], $match) ) {
			// on retourne tjrs qu'une seule langue
			return array('langid'=>$match[1], 'proba'=>$match[2]);
		} else {
			return new SoapFault('Server', 'detection error');
		}
	}
	return new SoapFault('Server', 'no text');
}
