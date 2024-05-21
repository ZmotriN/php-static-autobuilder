<?php

/**
 * ██╗     ██╗██████╗      ██╗██████╗ ███████╗ ██████╗    ████████╗██╗   ██╗██████╗ ██████╗  ██████╗ 
 * ██║     ██║██╔══██╗     ██║██╔══██╗██╔════╝██╔════╝    ╚══██╔══╝██║   ██║██╔══██╗██╔══██╗██╔═══██╗
 * ██║     ██║██████╔╝     ██║██████╔╝█████╗  ██║  ███╗█████╗██║   ██║   ██║██████╔╝██████╔╝██║   ██║
 * ██║     ██║██╔══██╗██   ██║██╔═══╝ ██╔══╝  ██║   ██║╚════╝██║   ██║   ██║██╔══██╗██╔══██╗██║   ██║
 * ███████╗██║██████╔╝╚█████╔╝██║     ███████╗╚██████╔╝      ██║   ╚██████╔╝██║  ██║██████╔╝╚██████╔╝
 * ╚══════╝╚═╝╚═════╝  ╚════╝ ╚═╝     ╚══════╝ ╚═════╝       ╚═╝    ╚═════╝ ╚═╝  ╚═╝╚═════╝  ╚═════╝ 
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$jpeglog = LOG . 'libjpeg-turbo.log';


// Verify if libjpeg-turbo is installed
if (is_dir($path) && is_file($path . 'build-win\Release\jpeg-static.lib') && is_file(DEPS_PATH . 'lib\libjpeg_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libjpeg-turbo
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find unzip results");


// Compile libjpeg-turbo
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$sln = $path . 'build-win\libjpeg-turbo.sln';
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'mkdir build-win'.RN;
$bat .= 'cd build-win'.RN;
$bat .= 'cmake -G "Visual Studio 16 2019" -DCMAKE_BUILD_TYPE=Release ..'.RN;
$bat .= 'DEVENV ' . escapeshellarg($sln) . ' /rebuild "Release|x64"'.RN;
$batfile = TMP . 'build_libjpeg-turbo.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($jpeglog, $ret);


// Verify if the build works
if(!is_file($path . 'build-win\Release\jpeg-static.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $jpeglog);
else draw_status($label, "complete", Green);


// Install libjpeg-turbo
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'build-win\Release\jpeg-static.lib'] = 'lib\libjpeg_a.lib';
$files[$path . 'build-win\jconfig.h'] = 'include\jconfig.h';
$files[$path . 'build-win\jversion.h'] = 'include\jversion.h';
$files[$path . 'jerror.h'] = 'include\jerror.h';
$files[$path . 'jmorecfg.h'] = 'include\jmorecfg.h';
$files[$path . 'jpeglib.h'] = 'include\jpeglib.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);