<?php

/**
 *  ██████╗  █████╗ ██████╗ ███████╗███████╗
 * ██╔════╝ ██╔══██╗██╔══██╗██╔════╝██╔════╝
 * ██║█████╗███████║██████╔╝█████╗  ███████╗
 * ██║╚════╝██╔══██║██╔══██╗██╔══╝  ╚════██║
 * ╚██████╗ ██║  ██║██║  ██║███████╗███████║
 *  ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝╚══════╝╚══════╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$careslog = LOG . 'c-ares.log';


// Verify if libevent is installed
if (is_dir($path) && is_file($path . 'build-win\lib\Release\cares.lib') && is_file(DEPS_PATH . 'lib\libcares_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip c-ares
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile c-ares
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'md build-win'.RN;
$bat .= 'cd build-win'.RN;
$bat .= 'cmake -G "Visual Studio 16 2019" -DCARES_STATIC=ON -DCARES_SHARED=OFF ..'.RN;
$bat .= 'DEVENV "c-ares.sln" /rebuild "Release|x64" /project "c-ares"';
$batfile = TMP . 'build_cares.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile, true);
file_put_contents($careslog, $ret);


// Verify if the build works
if(!is_file($path . 'build-win\lib\Release\cares.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $careslog);
else draw_status($label, "complete", Green);


// Install c-ares
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'build-win\lib\Release\cares.lib'] = 'lib\libcares_a.lib';
$files[$path . 'include\ares_build.h.dist'] = 'include\ares_build.h';

foreach(glob($path . 'include\*.h') as $file)
    $files[$file] = 'include\\' . basename($file);

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);
