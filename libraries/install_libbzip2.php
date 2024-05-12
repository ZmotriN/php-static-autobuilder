<?php

$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
if(is_dir($path) && is_file($path . 'lib\libbz2_a.lib')&& is_file(DEPS_PATH . 'lib\libbz2_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}

$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!is_dir($path) && !@mkdir($path, 0777, true)) exit_error("Can't create library folder");
if(!unzip($tmpfile, $path)) exit_error();

foreach(dig($path.'*') as $file) {
    $filename = str_replace($path, '', $file);
    $dest = DEPS_PATH . $filename;
    $destpath = pathinfo($dest, PATHINFO_DIRNAME);
    if(!is_dir($destpath) && !@mkdir($destpath, 0777, true)) exit_error("Can't create library destination folder");
    if(!@copy($file, $dest)) exit_error("Can't copy library files");
}

draw_status($lib->name . '-' . $lib->version, "installed", Green);