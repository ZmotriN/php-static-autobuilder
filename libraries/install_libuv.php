<?php

/**
 * ██╗     ██╗██████╗ ██╗   ██╗██╗   ██╗
 * ██║     ██║██╔══██╗██║   ██║██║   ██║
 * ██║     ██║██████╔╝██║   ██║██║   ██║
 * ██║     ██║██╔══██╗██║   ██║╚██╗ ██╔╝
 * ███████╗██║██████╔╝╚██████╔╝ ╚████╔╝ 
 * ╚══════╝╚═╝╚═════╝  ╚═════╝   ╚═══╝  
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$uvlog = LOG . 'libuv.log';


// Verify if libuv is installed
if (is_dir($path) && is_file($builddir . 'lib\libuv.lib') && is_file(DEPS_PATH . 'lib\libuv.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libuv
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find unzip results");


// Compile libuv
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'mkdir build-win'.RN;
$bat .= 'cd build-win'.RN;
$bat .= 'cmake -G "Visual Studio 16 2019" ..'.RN;
$bat .= 'devenv libuv.sln /rebuild "Release|x64" /project uv_a'.RN;
$batfile = TMP . 'build_libuv.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($uvlog, $ret);


// Verify if the build works
if(!is_file($path . 'build-win\Release\libuv.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $uvlog);
else draw_status($label, "complete", Green);


// Install libuv
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'build-win\Release\libuv.lib'] = 'lib\libuv.lib';
$files[$path . 'include\uv.h'] = 'include\uv.h';

foreach(glob($path . 'include\uv\*.h') as $file)
    $files[$file] = 'include\uv\\' . pathinfo($file, PATHINFO_BASENAME);

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);
