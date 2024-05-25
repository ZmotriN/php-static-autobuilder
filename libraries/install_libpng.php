<?php

/**
 * ██╗     ██╗██████╗ ██████╗ ███╗   ██╗ ██████╗ 
 * ██║     ██║██╔══██╗██╔══██╗████╗  ██║██╔════╝ 
 * ██║     ██║██████╔╝██████╔╝██╔██╗ ██║██║  ███╗
 * ██║     ██║██╔══██╗██╔═══╝ ██║╚██╗██║██║   ██║
 * ███████╗██║██████╔╝██║     ██║ ╚████║╚██████╔╝
 * ╚══════╝╚═╝╚═════╝ ╚═╝     ╚═╝  ╚═══╝ ╚═════╝ 
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$pnglog = LOG . 'libpng.log';


// Verify if libpng is installed
if (is_dir($path) && is_file($path . 'projects\vstudio2019\x64\Release Library\libpng_a.lib') && is_file(DEPS_PATH . 'lib\libpng_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libpng
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find unzip results");
if(!unzip(__DIR__ . '\libpng-msvc.zip', $path . 'projects')) exit_error("Can't unzip msvc project");


// Find zlib
foreach($MATRIX->libraries as $_lib) if($_lib->name == 'zlib') break;
if($_lib->name != 'zlib') exit_error("Can't find zlib");
$zlib = $_lib->name . '-' . $_lib->version;
if(!is_dir(ARCH_PATH . $zlib)) exit_error("Can't find zlib");


// Patch zlib path
$props = $path . 'projects\vstudio2019\zlib.props';
$contents = file_get_contents($props);
$contents = preg_replace('#<ZLibSrcDir>(.*?)</ZLibSrcDir>#i', '<ZLibSrcDir>..\..\..\..\\' . $zlib . '</ZLibSrcDir>', $contents);
file_put_contents($props, $contents);


// Compile static libpng
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'projects\vstudio2019').RN;
$bat .= 'DEVENV vstudio.sln /rebuild "Release Library|x64"';
$batfile = TMP . 'build_libpng.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($pnglog, $ret);


// Verify if the build works
if(!is_file($path . 'projects\vstudio2019\x64\Release Library\libpng_a.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $nglog);
else draw_status($label, "complete", Green);


// Install libpng
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'projects\vstudio2019\x64\Release Library\libpng_a.lib']  = 'lib\libpng_a.lib';
$files[$path . 'png.h'] = 'include\libpng16\png.h';
$files[$path . 'pngconf.h'] = 'include\libpng16\pngconf.h';
$files[$path . 'pnglibconf.h'] = 'include\libpng16\pnglibconf.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);