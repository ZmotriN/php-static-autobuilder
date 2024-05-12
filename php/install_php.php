<?php

$path = ARCH_PATH . 'php-' . $MATRIX->php->version . '-src\\';

if(!is_dir($path)) {
    if(curl_file_exists($MATRIX->php->download_url)) $url = $MATRIX->php->download_url;
    else $url = $MATRIX->php->archive_url;
    $tmpfile = TMP.pathinfo($url, PATHINFO_BASENAME);
    if(!download_file($url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
    if(!unzip($tmpfile, ARCH_PATH)) exit_error();
    if(!is_dir($path)) exit_error("Can't find PHP folder");
}

draw_status("PHP-" . $MATRIX->php->version, 'installed', Green);

$patchdir = DIR.'master\patches\\' . $MATRIX->php->version . '\\';
if(!is_dir($patchdir)) exit_error("Can't find PHP-' . $MATRIX->php->version . ' patches folder");


draw_line("Apply patches", 'running', Yellow);

$bat = '@echo off'.RN;
foreach(glob($patchdir.'*.patch') as $patch) {
    $bat .= 'patch --force --directory=' . escapeshellarg($path) . ' -p 2 < ' . escapeshellarg($patch).RN;
}

$batfile = TMP . 'php_patch.bat';
file_put_contents($batfile, $bat);
if(!SPEED_DEV) {
    shell_exec_vs16_phpsdk($batfile);
}

draw_status("Apply patches", 'complete', Green);