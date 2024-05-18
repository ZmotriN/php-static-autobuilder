<?php

/**
 * ██╗     ██╗██████╗ ██╗  ██╗ █████╗ ██████╗ ██╗   ██╗
 * ██║     ██║██╔══██╗██║  ██║██╔══██╗██╔══██╗██║   ██║
 * ██║     ██║██████╔╝███████║███████║██████╔╝██║   ██║
 * ██║     ██║██╔══██╗██╔══██║██╔══██║██╔══██╗██║   ██║
 * ███████╗██║██████╔╝██║  ██║██║  ██║██║  ██║╚██████╔╝
 * ╚══════╝╚═╝╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝ ╚═════╝ 
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$harulog = LOG . 'libharu.log';


// Verify if libharu is installed
if (is_dir($path) && is_file($path . 'build-win\src\Release\libhpdfs.lib') && is_file(DEPS_PATH . 'lib\libhpdf.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip library
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile libharu
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$sln = $path . 'build-win\libharu.sln';
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'mkdir build-win'.RN;
$bat .= 'cd build-win'.RN;
$bat .= 'cmake -G "Visual Studio 16 2019" -DBUILD_SHARED_LIBS=OFF ..'.RN;
$bat .= 'DEVENV ' . escapeshellarg($sln) . ' /rebuild "Release|x64"'.RN;
$batfile = TMP . 'build_libharu.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($harulog, $ret);


// Verify if the build works
if(!is_file($path . 'build-win\src\Release\libhpdfs.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $harulog);
else draw_status($label, "complete", Green);


// Install library
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';
$files[$path . 'build-win\src\Release\libhpdfs.lib'] = 'lib\libhpdf.lib';
$files[$path . 'build-win\include\hpdf_config.h'] = 'include\libharu\hpdf_config.h';
foreach(glob($path . 'include\*.h') as $file)
    $files[$file] = 'include\libharu\\' . pathinfo($file, PATHINFO_BASENAME);
if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);