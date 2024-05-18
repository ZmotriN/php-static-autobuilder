<?php

/**
 * ██╗     ██╗██████╗ ███╗   ███╗ █████╗ ██╗  ██╗███╗   ███╗██╗███╗   ██╗██████╗ ██████╗ ██████╗ 
 * ██║     ██║██╔══██╗████╗ ████║██╔══██╗╚██╗██╔╝████╗ ████║██║████╗  ██║██╔══██╗██╔══██╗██╔══██╗
 * ██║     ██║██████╔╝██╔████╔██║███████║ ╚███╔╝ ██╔████╔██║██║██╔██╗ ██║██║  ██║██║  ██║██████╔╝
 * ██║     ██║██╔══██╗██║╚██╔╝██║██╔══██║ ██╔██╗ ██║╚██╔╝██║██║██║╚██╗██║██║  ██║██║  ██║██╔══██╗
 * ███████╗██║██████╔╝██║ ╚═╝ ██║██║  ██║██╔╝ ██╗██║ ╚═╝ ██║██║██║ ╚████║██████╔╝██████╔╝██████╔╝
 * ╚══════╝╚═╝╚═════╝ ╚═╝     ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝     ╚═╝╚═╝╚═╝  ╚═══╝╚═════╝ ╚═════╝ ╚═════╝ 
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$lmmlog = LOG . 'libmaxminddb.log';


// Verify if maxminddb is installed
if (is_dir($path) && is_file($path . 'build-win\Release\maxminddb.lib') && is_file(DEPS_PATH . 'lib\libmaxminddb.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip library
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!untar($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find untar results");


// Compile libmaxminddb
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$sln = $path . 'build-win\maxminddb.sln';
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'mkdir build-win'.RN;
$bat .= 'cd build-win'.RN;
$bat .= 'cmake -DMSVC_STATIC_RUNTIME=ON -DBUILD_SHARED_LIBS=OFF ..'.RN;
$bat .= 'cmake --build .'.RN;
$bat .= 'DEVENV ' . escapeshellarg($sln) . ' /rebuild "Release|x64"'.RN;
$batfile = TMP . 'build_libmaxminddb.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($lmmlog, $ret);


// Verify if the build works
if(!is_file($path . 'build-win\Release\maxminddb.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $lmmlog);
else draw_status($label, "complete", Green);


// Install library
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';
$files[$path . 'build-win\Release\maxminddb.lib'] = 'lib\libmaxminddb.lib';
$files[$path . 'build-win\generated\maxminddb_config.h'] = 'include\maxminddb_config.h';
foreach(glob($path . 'include\*') as $file)
    $files[$file] = 'include\\' . pathinfo($file, PATHINFO_BASENAME);
if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);