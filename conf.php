<?php
/**
 * You can change default here
 */


$GLOBALS['texlive']   = "/usr/local/texlive/" . TEXLIVEVERSION;
$GLOBALS['path']      = "/usr/local/texlive/" . TEXLIVEVERSION . '/bin/' . ARCH . '/';
$GLOBALS['tex']       = "tex -interaction=nonstopmode";
$GLOBALS['latex']     = "latex -interaction=nonstopmode";
$GLOBALS['pdflatex']  = "pdflatex -interaction=nonstopmode";
$GLOBALS['xelatex']   = "xelatex -interaction=nonstopmode";
$GLOBALS['bibtex']    = "bibtex -terse";
$GLOBALS['makeindex'] = "makeindex -q";
$GLOBALS['dvips']     = "dvips -q -Ptype1";
$GLOBALS['ps2pdf']    = "/usr/bin/ps2pdf14";
$GLOBALS['chroot']    = "/usr/sbin/chroot";
$GLOBALS['latex2rtf'] = "/usr/local/bin/latex2rtf";
