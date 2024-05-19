<?php

/**
 * ██████╗ ██╗██████╗ ███████╗███╗   ██╗████████╗
 * ██╔══██╗██║██╔══██╗██╔════╝████╗  ██║╚══██╔══╝
 * ██║  ██║██║██████╔╝█████╗  ██╔██╗ ██║   ██║   
 * ██║  ██║██║██╔══██╗██╔══╝  ██║╚██╗██║   ██║   
 * ██████╔╝██║██║  ██║███████╗██║ ╚████║   ██║   
 * ╚═════╝ ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═══╝   ╚═╝   
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$direntlog = LOG . 'dirent.log';


// Verify if dirent is installed
if (is_dir($path) && is_file($path .  'vc15\x64\Release\dirent_a.lib') && is_file(DEPS_PATH . 'lib\dirent_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip dirent
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile dirent
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'vc15').RN;
$bat .= 'devenv dirent.sln /upgrade'.RN;
$bat .= 'devenv dirent.sln /rebuild "Release|x64"'.RN;
$batfile = TMP . 'build_dirent.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($direntlog, $ret);


// Verify if the build works
if(!is_file($path . 'vc15\x64\Release\dirent_a.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $dirent);
else draw_status($label, "complete", Green);


// Install libjudy
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'vc15\x64\Release\dirent_a.lib'] = 'lib\dirent_a.lib';
$files[$path . 'src\dirent.h'] = 'include\dirent.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);