<?php

/**
 * ██╗     ██╗██████╗ ███████╗██╗   ██╗███╗   ███╗
 * ██║     ██║██╔══██╗██╔════╝██║   ██║████╗ ████║
 * ██║     ██║██████╔╝███████╗██║   ██║██╔████╔██║
 * ██║     ██║██╔══██╗╚════██║╚██╗ ██╔╝██║╚██╔╝██║
 * ███████╗██║██████╔╝███████║ ╚████╔╝ ██║ ╚═╝ ██║
 * ╚══════╝╚═╝╚═════╝ ╚══════╝  ╚═══╝  ╚═╝     ╚═╝
 */


$libsvm = TMP.pathinfo($ext->libsvm, PATHINFO_BASENAME);
if(!download_file($ext->libsvm, $libsvm, "libsvm-3.3.2")) exit_error("Can't download libsvm");
if(!$firstdir = zip_first_dir($libsvm)) exit_error("Invalid zip archive");

$label = 'Install latest libsvm';
draw_line($label, 'running', Yellow);

$zip = new ZipArchive;
if ($zip->open($libsvm) !== TRUE) draw_status($label, "failed", Red, true, "Invalid proto headers archive");
if(!$contents = $zip->getFromName($firstdir . '/svm.cpp')) draw_status($label, "failed", Red, true);
else file_put_contents($path . 'libsvm\svm.cpp', $contents);
if(!$contents = $zip->getFromName($firstdir . '/svm.h')) draw_status($label, "failed", Red, true);
else file_put_contents($path . 'libsvm\svm.h', $contents);
$zip->close();

draw_status($label, 'complete', Green);
