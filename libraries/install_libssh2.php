<?php

/**
 * ██╗     ██╗██████╗ ███████╗███████╗██╗  ██╗██████╗ 
 * ██║     ██║██╔══██╗██╔════╝██╔════╝██║  ██║╚════██╗
 * ██║     ██║██████╔╝███████╗███████╗███████║ █████╔╝
 * ██║     ██║██╔══██╗╚════██║╚════██║██╔══██║██╔═══╝ 
 * ███████╗██║██████╔╝███████║███████║██║  ██║███████╗
 * ╚══════╝╚═╝╚═════╝ ╚══════╝╚══════╝╚═╝  ╚═╝╚══════╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$ssh2log = LOG . 'libssh2.log';


// Verify if libssh2 is installed
if (is_dir($path) && is_file($path . 'build\lib\libssh2.lib') && is_file(DEPS_PATH . 'lib\libssh2.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libssh2
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");
if(!unzip(__DIR__ . '\libssh2-msvc.zip', $path . 'win32')) exit_error("Can't unzip msvc project");


// Compile static libssh2
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'DEVENV "win32\libssh2.sln" /rebuild "OpenSSL LIB Release|x64"';
$batfile = TMP . 'build_libssh2.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($ssh2log, $ret);


// Verify if the build works
if(!is_file($path . 'win32\Release_lib\libssh2.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $ssh2log);
else draw_status($label, "complete", Green);


// Install libssh2
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

$builddir = $path . 'build\\';
$files[$path . 'win32\Release_lib\libssh2.lib'] = 'lib\libssh2.lib';
$files[$path . 'include\libssh2.h'] = 'include\libssh2\libssh2.h';
$files[$path . 'include\libssh2_publickey.h'] = 'include\libssh2\libssh2_publickey.h';
$files[$path . 'include\libssh2_sftp.h'] = 'include\libssh2\libssh2_sftp.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);