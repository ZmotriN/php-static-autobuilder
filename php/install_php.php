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

$patchexe = DIR . 'sdk\msys2\usr\bin\patch.exe';
if(!is_file($patchexe)) exit_error("Can't find patch.exe");

if(!SPEED_DEV) {
    draw_line("Apply patches", 'running', Yellow);
    
    $bat = '@echo off'.RN;
    foreach(glob($patchdir.'*.patch') as $patch) {
        $bat .= escapeshellarg($patchexe) . ' --force --directory=' . escapeshellarg($path) . ' -p 2 < ' . escapeshellarg($patch).RN;
    }

    $batfile = TMP . 'php_patch.bat';
    file_put_contents($batfile, $bat);
    shell_exec(escapeshellarg($batfile) . ' 2>&1');

    draw_status("Apply patches", 'complete', Green);
}