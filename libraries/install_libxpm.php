<?php

/**
 * ██╗     ██╗██████╗ ██╗  ██╗██████╗ ███╗   ███╗
 * ██║     ██║██╔══██╗╚██╗██╔╝██╔══██╗████╗ ████║
 * ██║     ██║██████╔╝ ╚███╔╝ ██████╔╝██╔████╔██║
 * ██║     ██║██╔══██╗ ██╔██╗ ██╔═══╝ ██║╚██╔╝██║
 * ███████╗██║██████╔╝██╔╝ ██╗██║     ██║ ╚═╝ ██║
 * ╚══════╝╚═╝╚═════╝ ╚═╝  ╚═╝╚═╝     ╚═╝     ╚═╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$xpmlog = LOG . 'libxpm.log';


// Verify if libxpm is installed
if (is_dir($path) && is_file($path . 'windows\builds\x64\Static Release\libxpm_a.lib') && is_file(DEPS_PATH . 'lib\libxpm_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libxpm
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");
if(!unzip(__DIR__ . '\libxpm-msvc.zip', $path)) exit_error("Can't unzip msvc project");


// Fix X11 version
list($major, $minor, $revision) = explode('.', $lib->version);
$contents = file_get_contents($path . 'include\X11\xpm.h');
$contents = preg_replace('/#define XpmFormat\s+[0-9a-f]+/i', '#define XpmFormat ' . $major, $contents);
$contents = preg_replace('/#define XpmVersion\s+[0-9a-f]+/i', '#define XpmVersion ' . $minor, $contents);
$contents = preg_replace('/#define XpmRevision\s+[0-9a-f]+/i', '#define XpmRevision ' . $revision, $contents);
file_put_contents($path . 'include\X11\xpm.h', $contents);


// Compile static libxpm
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'windows\vs16').RN;
$bat .= 'DEVENV libxpm.sln /rebuild "Static Release|x64"';
$batfile = TMP . 'build_libxpm.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($xpmlog, $ret);


// Verify if the build works
if(!is_file($path . 'windows\builds\x64\Static Release\libxpm_a.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $xpmlog);
else draw_status($label, "complete", Green);


// Download proto headers
$protofile = TMP.pathinfo($lib->xorgproto, PATHINFO_BASENAME);
if(!download_file($lib->xorgproto, $protofile, pathinfo($protofile, PATHINFO_BASENAME))) exit_error("Can't download proto headers");


// Download libx11 headers
$libx11file = TMP.pathinfo($lib->libx11, PATHINFO_BASENAME);
if(!download_file($lib->libx11, $libx11file, pathinfo($libx11file, PATHINFO_BASENAME))) exit_error("Can't download proto headers");


// Install libxpm
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';
$files[$path . 'windows\builds\x64\Static Release\libxpm_a.lib'] = 'lib\libxpm_a.lib';
$files[$path . 'include\X11\xpm.h'] = 'include\X11\xpm.h';
if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);


// Install proto headers
$zip = new ZipArchive;
if ($zip->open($protofile) !== TRUE) draw_status($label, "failed", Red, true, "Invalid proto headers archive");
if(!$contents = $zip->getFromName('xorgproto-master/include/X11/keysym.h')) draw_status($label, "failed", Red, true, "Invalid proto headers archive");
else file_put_contents($builddir . 'include\X11\keysym.h', $contents);
if(!$contents = $zip->getFromName('xorgproto-master/include/X11/keysymdef.h')) draw_status($label, "failed", Red, true, "Invalid proto headers archive");
else file_put_contents($builddir . 'include\X11\keysymdef.h', $contents);
if(!$contents = $zip->getFromName('xorgproto-master/include/X11/X.h')) draw_status($label, "failed", Red, true, "Invalid proto headers archive");
else file_put_contents($builddir . 'include\X11\X.h', $contents);
if(!$contents = $zip->getFromName('xorgproto-master/include/X11/Xfuncproto.h')) draw_status($label, "failed", Red, true, "Invalid proto headers archive");
else file_put_contents($builddir . 'include\X11\Xfuncproto.h', $contents);
if(!$contents = $zip->getFromName('xorgproto-master/include/X11/Xosdefs.h')) draw_status($label, "failed", Red, true, "Invalid proto headers archive");
else file_put_contents($builddir . 'include\X11\Xosdefs.h', $contents);
$zip->close();


// Install libX11 headers
$zip = new ZipArchive;
if ($zip->open($libx11file) !== TRUE) draw_status($label, "failed", Red, true, "Invalid libX11 headers archive");
if(!$contents = $zip->getFromName('libx11-master/include/X11/Xlib.h')) draw_status($label, "failed", Red, true, "Invalid libX11 headers archive");
else file_put_contents($builddir . 'include\X11\Xlib.h', $contents);
if(!$contents = $zip->getFromName('libx11-master/include/X11/Xutil.h')) draw_status($label, "failed", Red, true, "Invalid libX11 headers archive");
else file_put_contents($builddir . 'include\X11\Xutil.h', $contents);
$zip->close();


// Finish install
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);
