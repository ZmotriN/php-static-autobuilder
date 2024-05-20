<?php

/**
 * ██╗     ██╗██████╗ ██╗    ██╗███████╗██████╗ ██████╗ 
 * ██║     ██║██╔══██╗██║    ██║██╔════╝██╔══██╗██╔══██╗
 * ██║     ██║██████╔╝██║ █╗ ██║█████╗  ██████╔╝██████╔╝
 * ██║     ██║██╔══██╗██║███╗██║██╔══╝  ██╔══██╗██╔═══╝ 
 * ███████╗██║██████╔╝╚███╔███╔╝███████╗██████╔╝██║     
 * ╚══════╝╚═╝╚═════╝  ╚══╝╚══╝ ╚══════╝╚═════╝ ╚═╝     
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$project = $path . 'output\release-static\x64\lib\\';
$webplog = LOG . 'libwebp.log';


// Verify if libwebp is installed
if (is_dir($path) && is_file($project . 'libwebp.lib') && is_file(DEPS_PATH . 'lib\libwebp_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libwebp
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find unzip results");


// Compile libwebp
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path ).RN;
$bat .= 'nmake /f Makefile.vc CFG=release-static RTLIBCFG=dynamic OBJDIR=output'.RN;
$batfile = TMP . 'build_libwebp.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($webplog, $ret);


// Verify if the build works
if(!is_file($project . 'libwebp.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $webplog);
else draw_status($label, "complete", Green);


// Install libwebp
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$project . 'libsharpyuv.lib'] = 'lib\libsharpyuv_a.lib';
$files[$project . 'libwebp.lib'] = 'lib\libwebp_a.lib';
$files[$project . 'libwebpdecoder.lib'] = 'lib\libwebpdecoder_a.lib';
$files[$project . 'libwebpdemux.lib'] = 'lib\libwebpdemux_a.lib';
foreach(glob($path . 'src\webp\*.h') as $file)
    $files[$file] = 'include\webp\\' . pathinfo($file, PATHINFO_BASENAME);

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);