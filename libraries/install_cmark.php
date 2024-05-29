<?php

/**
 *  ██████╗███╗   ███╗ █████╗ ██████╗ ██╗  ██╗
 * ██╔════╝████╗ ████║██╔══██╗██╔══██╗██║ ██╔╝
 * ██║     ██╔████╔██║███████║██████╔╝█████╔╝ 
 * ██║     ██║╚██╔╝██║██╔══██║██╔══██╗██╔═██╗ 
 * ╚██████╗██║ ╚═╝ ██║██║  ██║██║  ██║██║  ██╗
 *  ╚═════╝╚═╝     ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$cmarklog = LOG . 'cmark.log';


// Verify if cmark is installed
if (is_dir($path) && is_file($path .  'build\src\cmark.lib') && is_file(DEPS_PATH . 'lib\cmark.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip cmark
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Library folder not found");


// Compile cmark
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'nmake /f Makefile.nmake'.RN;
$batfile = TMP . 'build_cmark.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile, true);
file_put_contents($cmarklog, $ret);


// Verify if the build works
if(!is_file($path . 'build\src\cmark.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $direntlog);
else draw_status($label, "complete", Green);


// Install cmark
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build-win\\';

$files[$path . 'build\src\cmark.lib'] = 'lib\cmark.lib';
$files[$path . 'build\src\cmark_export.h'] = 'include\cmark_export.h';
$files[$path . 'build\src\cmark_version.h'] = 'include\cmark_version.h';
$files[$path . 'src\cmark.h'] = 'include\cmark.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);