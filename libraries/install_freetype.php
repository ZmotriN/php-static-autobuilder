<?php

/**
 * ███████╗██████╗ ███████╗███████╗████████╗██╗   ██╗██████╗ ███████╗
 * ██╔════╝██╔══██╗██╔════╝██╔════╝╚══██╔══╝╚██╗ ██╔╝██╔══██╗██╔════╝
 * █████╗  ██████╔╝█████╗  █████╗     ██║    ╚████╔╝ ██████╔╝█████╗  
 * ██╔══╝  ██╔══██╗██╔══╝  ██╔══╝     ██║     ╚██╔╝  ██╔═══╝ ██╔══╝  
 * ██║     ██║  ██║███████╗███████╗   ██║      ██║   ██║     ███████╗
 * ╚═╝     ╚═╝  ╚═╝╚══════╝╚══════╝   ╚═╝      ╚═╝   ╚═╝     ╚══════╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$freelog = LOG . 'freetype.log';


// Verify if freetype is installed
if (is_dir($path) && is_file($path .  'objs\x64\Release Static\freetype.lib') && is_file(DEPS_PATH . 'lib\freetype_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip freetype
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile freetype
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'builds\windows\vc2010').RN;
$bat .= 'devenv freetype.sln /upgrade'.RN;
$bat .= 'devenv freetype.sln /rebuild "Release Static|x64"'.RN;
$batfile = TMP . 'build_freetype.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($freelog, $ret);


// Verify if the build works
if(!is_file($path . 'objs\x64\Release Static\freetype.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $freelog);
else draw_status($label, "complete", Green);


// Install freetype
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'objs\x64\Release Static\freetype.lib'] = 'lib\freetype_a.lib';
$files[$path . 'include\ft2build.h'] = 'include\freetype2\ft2build.h';

foreach(glob($path . 'include\freetype\*.h') as $file)
    $files[$file] = 'include\freetype2\freetype\\' . pathinfo($file, PATHINFO_BASENAME);

foreach(glob($path . 'include\freetype\config\*.h') as $file)
    $files[$file] = 'include\freetype2\freetype\config\\' . pathinfo($file, PATHINFO_BASENAME);

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);





