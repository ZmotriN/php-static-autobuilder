<?php

/**
 * ██╗     ██╗██████╗ ██╗  ██╗██████╗ ██╗███████╗███████╗
 * ██║     ██║██╔══██╗╚██╗██╔╝██╔══██╗██║██╔════╝██╔════╝
 * ██║     ██║██████╔╝ ╚███╔╝ ██║  ██║██║█████╗  █████╗  
 * ██║     ██║██╔══██╗ ██╔██╗ ██║  ██║██║██╔══╝  ██╔══╝  
 * ███████╗██║██████╔╝██╔╝ ██╗██████╔╝██║██║     ██║     
 * ╚══════╝╚═╝╚═════╝ ╚═╝  ╚═╝╚═════╝ ╚═╝╚═╝     ╚═╝     
 */

 
$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$xdifflog = LOG . 'libxdiff.log';


// Download and unzip libxdiff
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!untar($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find untar results");


// Patch makefile.win32
$contents = file_get_contents($path . 'makefile.win32');
$contents = str_replace(':I386', ':X64', $contents);
file_put_contents($path . 'makefile.win32', $contents);


// Compile libxdiff
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'nmake /f makefile.win32 CFG=release'.RN;
$batfile = TMP . 'build_libxdiff.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($xdifflog, $ret);


// Verify if the build works
if(!is_file($path . 'release\xdiff.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $xdifflog);
else draw_status($label, "complete", Green);


// Install libxdiff
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'release\xdiff.lib'] = 'lib\xdiff_a.lib';
$files[$path . 'xdiff\xdiff.h'] = 'include\xdiff.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);
