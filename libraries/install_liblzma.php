<?php

/**
 * ██╗     ██╗██████╗ ██╗     ███████╗███╗   ███╗ █████╗ 
 * ██║     ██║██╔══██╗██║     ╚══███╔╝████╗ ████║██╔══██╗
 * ██║     ██║██████╔╝██║       ███╔╝ ██╔████╔██║███████║
 * ██║     ██║██╔══██╗██║      ███╔╝  ██║╚██╔╝██║██╔══██║
 * ███████╗██║██████╔╝███████╗███████╗██║ ╚═╝ ██║██║  ██║
 * ╚══════╝╚═╝╚═════╝ ╚══════╝╚══════╝╚═╝     ╚═╝╚═╝  ╚═╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$lzmalog = LOG . 'liblzma.log';


// Verify if freetype is installed
if (is_dir($path) && is_file($path .  'build\lib\liblzma_a.lib') && is_file(DEPS_PATH . 'lib\liblzma_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip liblzma
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile liblzma
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'windows\vs2019').RN;
$bat .= 'devenv xz_win.sln /rebuild "Release|x64" /project liblzma'.RN;
$batfile = TMP . 'build_liblzma.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($lzmalog, $ret);


// Verify if the build works
if(!is_file($path . 'windows\vs2019\Release\x64\liblzma\liblzma.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $lzmalog);
else draw_status($label, "complete", Green);


// Install liblzma
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'windows\vs2019\Release\x64\liblzma\liblzma.lib'] = 'lib\liblzma_a.lib';
$files[$path . 'src\liblzma\api\lzma.h'] = 'include\lzma.h';

foreach(glob($path . 'src\liblzma\api\lzma\*.h') as $file)
    $files[$file] = 'include\lzma\\' . basename($file);

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);
