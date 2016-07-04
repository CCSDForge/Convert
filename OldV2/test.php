<?php  
error_reporting(E_ALL);
require '/sites/code/utile.php';
include 'image.php';

/*
$res = metaImage('fr_CRHST_ANR_U3D_CB_COMPIEGNE_Plaques-verre_003.tif');
echo '<xmp>';print_r($res);echo $xmp;echo '</xmp>';

$res = metaImage('60bbbb51-052d-4e37-b26f-2910d53fcbe8_1.tif');
echo '<xmp>';print_r($res);echo $xmp;echo '</xmp>';

$res = metaImage('fr_CRHST_ANR_U3D_CB_COMPIEGNE_Plaques-verre_002.tif');
echo '<xmp>';print_r($res);echo $xmp;echo '</xmp>';

$res = metaImage('fr_CRHST_ANR_U3D_C5_PH-10CV-22_0023.jpg');
echo '<xmp>';print_r($res);echo $xmp;echo '</xmp>';
*/
$res = metaImage('cemca.jpg');
echo '<xmp>';print_r($res);echo '</xmp>';
$res = metaImage('test.jpg');
echo '<xmp>';print_r($res);echo '</xmp>';

