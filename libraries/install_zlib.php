<?php

$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';

if(is_dir($path) && is_file($path . 'lib\zlib_a.lib')&& is_file(DEPS_PATH . 'lib\zlib_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!is_dir($path) && !@mkdir($path, 0777, true)) exit_error("Can't create library folder");
if(!unzip($tmpfile, $path)) exit_error();


$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

if(!install_deps($path)) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}

