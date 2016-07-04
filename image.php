<?php

/**
*
* metaImage function
*
* @param string $image
* @return array exif+IPTC+XMP metadatas
*
*/
function metaImage( $img ) {
	$meta = array();
	//Données exif
	if ( $exif_data = exif_read_data($img, 'EXIF') ) {
		foreach ( array('DateTimeOriginal', 'ExifImageWidth', 'ExifImageLength') as $data ) {
			$meta[$data] = isset($exif_data[$data]) ? $exif_data[$data] : '' ;
		}
	}
	if ( $exif_gps = exif_read_data($img, 'GPS') ) {
		foreach ( array('GPSLatitude', 'GPSLongitude') as $data ) {
			if ( array_key_exists($data, $exif_gps) ) {
				list($deg, $min, $sec) = $exif_gps[$data];
				if ( isset($deg) ) {
					eval("\$deg = $deg;");
				} else {
					$deg = null;
				}
				if ( isset($min) ) {
					eval("\$min = $min;");
				} else {
					$min = null;
				}
				if ( isset($sec) ) {
					eval("\$sec = $sec;");
				} else {
					$sec = null;
				}
				if ( $deg != null && $min != null && $sec != null ) {
					$meta[$data] = DMS2Dec($deg, $min, $sec, $exif_gps[$data.'Ref']);
				}
			}
		}
	}
	//Données XMP
	if ( function_exists('xmp_read') ) {
		try {
			$xml = new SimpleXMLElement(xmp_read($img));
			$xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
			$xml->registerXPathNamespace('iX', 'http://ns.adobe.com/iX/1.0/');
			$xml->registerXPathNamespace('photoshop', 'http://ns.adobe.com/photoshop/1.0/');
			$xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
			//title
			if ( !isset($meta['ObjectName']) || $meta['ObjectName'] == '' ) {
				$value = $xml->xpath("//rdf:RDF/rdf:Description/dc:title/rdf:Alt/rdf:li");
				$meta['ObjectName'] = ( $value ) ? (string)$value[0] : '';
			}
			//title
			if ( !isset($meta['Headline']) || $meta['Headline'] == '' ) {
				$value = $xml->xpath("//rdf:RDF/rdf:Description/photoshop:Headline");
				$meta['Headline'] = ( $value ) ? (string)$value[0] : '';
			}
			//date
			if ( !isset($meta['DateCreated']) || $meta['DateCreated'] == '' ) {
				$value = $xml->xpath("//rdf:RDF/rdf:Description/photoshop:DateCreated");
				$meta['DateCreated'] = ( $value ) ? (string)$value[0] : '';
				if ( preg_match('~([0-9]{2})/([0-9]{2})/([0-9]{4})~', $meta['DateCreated'], $match) ) { #format -> jj/mm/aaaa
					$meta['DateCreated'] = $match[3].'-'.$match[2].'-'.$match[1];
				}
			}
			//keyword
			if ( !isset($meta['Keywords']) || $meta['Keywords'] == '' ) {
				$value = $xml->xpath("//rdf:RDF/rdf:Description/dc:subject/rdf:Bag/rdf:li");
				$meta['Keywords'] = ( $value ) ? implode(';', array_unique($value)) : '';
			}
			//author
			if ( !isset($meta['Byline']) || $meta['Byline'] == '' ) {
				$value = $xml->xpath("//rdf:RDF/rdf:Description/dc:creator/rdf:Seq/rdf:li");
				$meta['Byline'] = ( $value ) ? (string)$value[0] : ''; # on ne garde que le premier auteur
			}
			//resume
			if ( !isset($meta['Caption']) || $meta['Caption'] == '' ) {
				$value = $xml->xpath("//rdf:RDF/rdf:Description/dc:description/rdf:Alt/rdf:li");
				$meta['Caption'] = ( $value ) ? (string)$value[0] : '';
			}
			//source
			if ( !isset($meta['Source']) || $meta['Source'] == '' ) {
				$value = $xml->xpath("//rdf:RDF/rdf:Description/photoshop:Source");
				$meta['Source'] = ( $value ) ? (string)$value[0] : '';
			}
			//city
			if ( !isset($meta['City']) || $meta['City'] == '' ) {
				$value = $xml->xpath("//rdf:RDF/rdf:Description/photoshop:City");
				$meta['City'] = ( $value ) ? (string)$value[0] : '';
			}
			//country
			if ( !isset($meta['Country']) || $meta['Country'] == '' ) {
				$value = $xml->xpath("//rdf:RDF/rdf:Description/photoshop:Country");
				$meta['Country'] = ( $value ) ? (string)$value[0] : '';
			}
			//credit
			if ( !isset($meta['Credits']) || $meta['Credits'] == '' ) {
				$value = $xml->xpath("//rdf:RDF/rdf:Description/photoshop:Credit");
				$meta['Credits'] = ( $value ) ? (string)$value[0] : '';
			}
		} catch (Exception $e) {}
	}
	//Données iptc
	include_once '/sites/code/classes/iptc.php';
	$iptc = new IPTC($img);
	if ( $iptc_data = $iptc->getIPTC() ) {
		foreach ( array('005'=>'ObjectName', '055'=>'DateCreated', '105'=>'Headline', '080'=>'Byline', '025'=>'Keywords', '120'=>'Caption', '115'=>'Source', '090'=>'City', '100'=>'CountryCode', '101'=>'Country', '110'=>'Credits') as $data=>$value ) {
			if ( isset($iptc_data[$data]) ) {
				if ( ! is_array($iptc_data[$data]) ) {
					if ( !isset($meta[$value]) || $meta[$value] == '' ) {
						$meta[$value] = $iptc_data[$data] ;
					}
				} else {
					if ( count($iptc_data[$data])==1 ){
						if ( !isset($meta[$value]) || $meta[$value] == '' ) {
							$meta[$value] = $iptc_data[$data][0];
						}
					} else {
						if ( !isset($meta[$value]) || $meta[$value] == '' ) {
							$meta[$value] = implode(';', array_unique($iptc_data[$data]));
						}
					}
				}
			}
		}
	}
	return $meta;
}

/**
*
* addXMP function
*
* @param string $img image filename
* @param array $metas metadatas
* @return bool
*
*/
function addXMP( $img, $metas ) {
	if ( function_exists('xmp_can_write') && function_exists('xmp_write') ) {
		$xmlData = '<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="XMP Core 4.4.0"> <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"> <rdf:Description rdf:about="" xmlns:xmp="http://ns.adobe.com/xap/1.0/"> <xmp:MetadataDate>'.date('c').'</xmp:MetadataDate> <xmp:CreateDate>'.date('c').'</xmp:CreateDate> <xmp:ModifyDate>'.date('c').'</xmp:ModifyDate> <xmp:CreatorTool>MediHAL CCSD</xmp:CreatorTool> </rdf:Description> <rdf:Description rdf:about="" xmlns:dc="http://purl.org/dc/elements/1.1/"> %%TITLE%% %%KEY%% %%RIGHT%% %%DESCRIPTION%% %%CREATOR%% </rdf:Description> <rdf:Description rdf:about="" xmlns:photoshop="http://ns.adobe.com/photoshop/1.0/"> <photoshop:Source>%%SOURCE%%</photoshop:Source> <photoshop:Credit>%%CREDIT%%</photoshop:Credit> <photoshop:Country>%%COUNTRY%%</photoshop:Country> <photoshop:City>%%CITY%%</photoshop:City> <photoshop:DateCreated>%%DATE%%</photoshop:DateCreated> </rdf:Description> </rdf:RDF> </x:xmpmeta>';
		$xmlData = str_replace('%%KEY%%', '<dc:subject> <rdf:Bag> <rdf:li>'.implode('</rdf:li> <rdf:li>', $metas['keyword']).'</rdf:li> </rdf:Bag> </dc:subject>', $xmlData);
		$xmlData = str_replace('%%TITLE%%', '<dc:title> <rdf:Alt> <rdf:li xml:lang="x-default">'.$metas['title'].'</rdf:li> </rdf:Alt> </dc:title>', $xmlData);
		$xmlData = str_replace('%%RIGHT%%', '<dc:rights> <rdf:Alt> <rdf:li xml:lang="x-default">'.$metas['copyright'].'</rdf:li> </rdf:Alt> </dc:rights>', $xmlData);
		$xmlData = str_replace('%%DESCRIPTION%%', '<dc:description> <rdf:Alt> <rdf:li xml:lang="x-default">'.$metas['description'].'</rdf:li> </rdf:Alt> </dc:description>', $xmlData);
		$xmlData = str_replace('%%CREATOR%%', '<dc:creator> <rdf:Seq> <rdf:li>'.$metas['creator'].'</rdf:li> </rdf:Seq> </dc:creator>', $xmlData);
		$xmlData = str_replace('%%COUNTRY%%', $metas['country'], $xmlData);
		$xmlData = str_replace('%%CITY%%', $metas['city'], $xmlData);
		$xmlData = str_replace('%%DATE%%', $metas['date'], $xmlData);
		$xmlData = str_replace('%%SOURCE%%', $metas['source'], $xmlData);
		$xmlData = str_replace('%%CREDIT%%', $metas['right'], $xmlData);
		if ( xmp_can_write($img, $xmlData) ) {
			return xmp_write($img, $xmlData);
		}
	}
	return false;
}
