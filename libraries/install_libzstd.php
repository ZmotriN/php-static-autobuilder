<?php

/**
 * ██╗     ██╗██████╗ ███████╗███████╗████████╗██████╗ 
 * ██║     ██║██╔══██╗╚══███╔╝██╔════╝╚══██╔══╝██╔══██╗
 * ██║     ██║██████╔╝  ███╔╝ ███████╗   ██║   ██║  ██║
 * ██║     ██║██╔══██╗ ███╔╝  ╚════██║   ██║   ██║  ██║
 * ███████╗██║██████╔╝███████╗███████║   ██║   ██████╔╝
 * ╚══════╝╚═╝╚═════╝ ╚══════╝╚══════╝   ╚═╝   ╚═════╝ 
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$zstdlog = LOG . 'libzstd.log';


// Verify if libzstd is installed
if (is_dir($path) && is_file($path .  'build\VS2010\bin\x64_Release\libzstd_static.lib') && is_file(DEPS_PATH . 'lib\libzstd.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libzstd
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile libzstd
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'build\VS2010').RN;
$bat .= 'devenv zstd.sln /upgrade'.RN;
$bat .= 'devenv zstd.sln /rebuild "Release|x64"'.RN;
$batfile = TMP . 'build_libzstd.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile, true);
file_put_contents($zstdlog, $ret);


// Verify if the build works
if(!is_file($path . 'build\VS2010\bin\x64_Release\libzstd_static.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $zstdlog);
else draw_status($label, "complete", Green);


// Install libzstd
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build-win\\';

$files[$path . 'build\VS2010\bin\x64_Release\libzstd_static.lib'] = 'lib\libzstd.lib';
$files[$path . 'lib\zstd.h'] = 'include\zstd.h';
$files[$path . 'lib\zdict.h'] = 'include\zdict.h';
$files[$path . 'lib\zstd_errors.h'] = 'include\zstd_errors.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);