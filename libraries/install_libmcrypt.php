<?php

/**
 * ██╗     ██╗██████╗ ███╗   ███╗ ██████╗██████╗ ██╗   ██╗██████╗ ████████╗
 * ██║     ██║██╔══██╗████╗ ████║██╔════╝██╔══██╗╚██╗ ██╔╝██╔══██╗╚══██╔══╝
 * ██║     ██║██████╔╝██╔████╔██║██║     ██████╔╝ ╚████╔╝ ██████╔╝   ██║   
 * ██║     ██║██╔══██╗██║╚██╔╝██║██║     ██╔══██╗  ╚██╔╝  ██╔═══╝    ██║   
 * ███████╗██║██████╔╝██║ ╚═╝ ██║╚██████╗██║  ██║   ██║   ██║        ██║   
 * ╚══════╝╚═╝╚═════╝ ╚═╝     ╚═╝ ╚═════╝╚═╝  ╚═╝   ╚═╝   ╚═╝        ╚═╝   
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$mcryptlog = LOG . 'libmcrypt.log';


// Verify if dirent is libmcrypt
if (is_dir($path) && is_file($path .  'win32\vc15\x64\Release lib\libmcrypt.lib') && is_file(DEPS_PATH . 'lib\libmcrypt.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libmcrypt
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile libmcrypt
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'win32\vc15').RN;
$bat .= 'set PATH=%PATH%;' . ARCH_PATH . 'deps'.RN;
$bat .= 'devenv libmcrypt.sln /upgrade'.RN;
$bat .= 'devenv libmcrypt.sln /rebuild "Release lib|x64"'.RN;
$batfile = TMP . 'build_libmcrypt.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($mcryptlog, $ret);


// Verify if the build works
if(!is_file($path . 'win32\vc15\x64\Release lib\libmcrypt.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $mcryptlog);
else draw_status($label, "complete", Green);


// Install libmcrypt
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'win32\vc15\x64\Release lib\libmcrypt.lib'] = 'lib\libmcrypt.lib';
$files[$path . 'include\mcrypt.h'] = 'include\mcrypt.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);