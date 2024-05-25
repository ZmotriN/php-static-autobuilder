<?php

/**
 * ██████╗ ██████╗  ██████╗ ████████╗██╗     ██╗
 * ██╔══██╗██╔══██╗██╔═══██╗╚══██╔══╝██║     ██║
 * ██████╔╝██████╔╝██║   ██║   ██║   ██║     ██║
 * ██╔══██╗██╔══██╗██║   ██║   ██║   ██║     ██║
 * ██████╔╝██║  ██║╚██████╔╝   ██║   ███████╗██║
 * ╚═════╝ ╚═╝  ╚═╝ ╚═════╝    ╚═╝   ╚══════╝╚═╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$brotlilog = LOG . 'brotli.log';


// Verify if libevent is installed
if (is_dir($path) && is_file($path . 'build\lib\brotlicommon.lib') && is_file(DEPS_PATH . 'lib\brotlicommon.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip brotli
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Library folder not found");


// Compile brotli
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'md build-win'.RN;
$bat .= 'cd build-win'.RN;
$bat .= 'cmake -G "Visual Studio 16 2019" -DBUILD_SHARED_LIBS=OFF -DCMAKE_BUILD_TYPE=Release ..'.RN;
$bat .= 'DEVENV "brotli.sln" /rebuild "Release|x64"';
$batfile = TMP . 'build_brotli.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile, true);
file_put_contents($brotlilog, $ret);


// Verify if the build works
if(!is_file($path . 'build-win\Release\brotlicommon.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $lelog);
else draw_status($label, "complete", Green);


// Install brotli
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'build-win\Release\brotlicommon.lib'] = 'lib\brotlicommon.lib';
$files[$path . 'build-win\Release\brotlidec.lib'] = 'lib\brotlidec.lib';
$files[$path . 'build-win\Release\brotlienc.lib'] = 'lib\brotlienc.lib';

foreach(glob($path . 'c\include\brotli\*.h') as $file)
    $files[$file] = 'include\brotli\\' . basename($file);

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);