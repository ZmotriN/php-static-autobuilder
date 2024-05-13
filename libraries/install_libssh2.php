<?php

$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';

if (is_dir($path) && is_file($path . 'build\lib\libssh2.lib') && is_file(DEPS_PATH . 'lib\libssh2.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");

if(!@copy(__DIR__ . '\libssh2.sln', $path . 'win32\libssh2.sln')) exit_error("Can't copy solution file");
if(!@copy(__DIR__ . '\libssh2.vcxproj', $path . 'win32\libssh2.vcxproj')) exit_error("Can't copy project file");



$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);


$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'DEVENV "win32\libssh2.sln" /rebuild "OpenSSL LIB Release|x64"';
$batfile = TMP . 'build_libssh2.bat';
file_put_contents($batfile, $bat);
shell_exec_vs16($batfile);


if(!is_file($path . 'win32\Release_lib\libssh2.lib')) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}


$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

$builddir = $path . 'build\\';
if(!is_dir($builddir) && !@mkdir($builddir, 0777, true))
    draw_status($label, "failed", Red, true);

$libdir = $builddir . 'lib\\';
if(!is_dir($libdir) && !@mkdir($libdir, 0777, true))
    draw_status($label, "failed", Red, true);

$incdir = $builddir . 'include\libssh2\\';
if(!is_dir($incdir) && !@mkdir($incdir, 0777, true))
    draw_status($label, "failed", Red, true);


if(!@copy($path .'win32\Release_lib\libssh2.lib', $libdir . 'libssh2.lib'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'include\libssh2.h', $incdir . 'libssh2.h'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'include\libssh2_publickey.h', $incdir . 'libssh2_publickey.h'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'include\libssh2_sftp.h', $incdir . 'libssh2_sftp.h'))
    draw_status($label, "failed", Red, true);


if(!install_deps($builddir)) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}

