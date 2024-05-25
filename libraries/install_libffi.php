<?php

/**
 * ██╗     ██╗██████╗ ███████╗███████╗██╗
 * ██║     ██║██╔══██╗██╔════╝██╔════╝██║
 * ██║     ██║██████╔╝█████╗  █████╗  ██║
 * ██║     ██║██╔══██╗██╔══╝  ██╔══╝  ██║
 * ███████╗██║██████╔╝██║     ██║     ██║
 * ╚══════╝╚═╝╚═════╝ ╚═╝     ╚═╝     ╚═╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$ffilog = LOG . 'libffi.log';


// Verify if libffi is installed
if (is_dir($path) && is_file($path . 'win32\vs16_x64\x64\Release\libffi.lib') && is_file(DEPS_PATH . 'lib\libffi.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libffi
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find untar results");
if(!unzip(__DIR__ . '\libffi-msvc.zip', $path)) exit_error("Can't unzip msvc project");
if(!@rename($path . 'win32\vs16_x64\fficonfig.h', $path . 'fficonfig.h')) exit_error("Can't rename fficonfig.h");
if(!@rename($path . 'win32\vs16_x64\ffi.h', $path . 'include\ffi.h')) exit_error("Can't rename ffi.h");


// Compile libffi
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'win32\vs16_x64').RN;
$bat .= 'DEVENV libffi-msvc.sln /rebuild "Release|x64"'.RN;
$batfile = TMP . 'build_libffi.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($ffilog, $ret);


// Verify if the build works
if(!is_file($path . 'win32\vs16_x64\x64\Release\libffi.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $ffilog);
else draw_status($label, "complete", Green);


// Install libffi
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'win32\vs16_x64\x64\Release\libffi.lib'] = 'lib\libffi.lib';
$files[$path . 'fficonfig.h'] = 'include\fficonfig.h';
$files[$path . 'include\ffi.h'] = 'include\ffi.h';
$files[$path . 'src\x86\ffitarget.h'] = 'include\ffitarget.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);