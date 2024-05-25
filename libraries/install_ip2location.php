<?php

/**
 * ██╗██████╗ ██████╗ ██╗      ██████╗  ██████╗ █████╗ ████████╗██╗ ██████╗ ███╗   ██╗
 * ██║██╔══██╗╚════██╗██║     ██╔═══██╗██╔════╝██╔══██╗╚══██╔══╝██║██╔═══██╗████╗  ██║
 * ██║██████╔╝ █████╔╝██║     ██║   ██║██║     ███████║   ██║   ██║██║   ██║██╔██╗ ██║
 * ██║██╔═══╝ ██╔═══╝ ██║     ██║   ██║██║     ██╔══██║   ██║   ██║██║   ██║██║╚██╗██║
 * ██║██║     ███████╗███████╗╚██████╔╝╚██████╗██║  ██║   ██║   ██║╚██████╔╝██║ ╚████║
 * ╚═╝╚═╝     ╚══════╝╚══════╝ ╚═════╝  ╚═════╝╚═╝  ╚═╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$ip2log = LOG . 'ip2location.log';


// Verify if libevent is installed
if (is_dir($path) && is_file($path . 'build\lib\IP2Location.lib') && is_file(DEPS_PATH . 'lib\IP2Location.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip ip2location
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");
if(!$contents = curl_get_contents($lib->win_patch_url)) exit_error("Can't download patch");
file_put_contents($path . 'libIP2Location\IP2Location.c', $contents);


// Compile ip2location
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'nmake Makefile.win'.RN;
$batfile = TMP . 'build_ip2location.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($ip2log, $ret);


// Verify if the build works
if(!is_file($path . 'libIP2Location\IP2Location.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $ip2log);
else draw_status($label, "complete", Green);


// Install ip2location
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'libIP2Location\IP2Location.lib'] = 'lib\IP2Location.lib';
$files[$path . 'libIP2Location\IP2Location.h'] = 'include\IP2Location.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);