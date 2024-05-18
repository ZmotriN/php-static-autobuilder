<?php

/**
 * ███╗   ██╗ ██████╗ ██╗  ██╗████████╗████████╗██████╗ ██████╗ 
 * ████╗  ██║██╔════╝ ██║  ██║╚══██╔══╝╚══██╔══╝██╔══██╗╚════██╗
 * ██╔██╗ ██║██║  ███╗███████║   ██║      ██║   ██████╔╝ █████╔╝
 * ██║╚██╗██║██║   ██║██╔══██║   ██║      ██║   ██╔═══╝ ██╔═══╝ 
 * ██║ ╚████║╚██████╔╝██║  ██║   ██║      ██║   ██║     ███████╗
 * ╚═╝  ╚═══╝ ╚═════╝ ╚═╝  ╚═╝   ╚═╝      ╚═╝   ╚═╝     ╚══════╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$nglog = LOG . 'nghttp2.log';


// Verify if nghttp2 is installed
if (is_dir($path) && is_file($path . 'build\lib\nghttp2.lib') && is_file(DEPS_PATH . 'lib\nghttp2.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip nghttp2
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile nghttp2
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'cmake -DCMAKE_INSTALL_PREFIX="%PREFIX%" -DENABLE_STATIC_LIB=YES -DCMAKE_INSTALL_LIBDIR="%PREFIX%\lib" -G "Visual Studio 16 2019" .'.RN;
$bat .= 'cmake --build . --config Release'.RN;
$batfile = TMP . 'build_nghttp2.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($nglog, $ret);


// Verify if the build works
if(!is_file($path . 'lib\Release\nghttp2.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $nglog);
else draw_status($label, "complete", Green);


// Install nghttp2
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';
$files[$path . 'lib\Release\nghttp2.lib'] = 'lib\nghttp2.lib';
$files[$path . 'lib\includes\nghttp2\nghttp2.h'] = 'include\nghttp2\nghttp2.h';
$files[$path . 'lib\includes\nghttp2\nghttp2ver.h'] = 'include\nghttp2\nghttp2ver.h';
if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);
delete_parent_deps($lib->name);
