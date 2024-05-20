<?php

/**
 * ██╗     ██╗██████╗ ██╗  ██╗███╗   ███╗██╗     ██████╗ 
 * ██║     ██║██╔══██╗╚██╗██╔╝████╗ ████║██║     ╚════██╗
 * ██║     ██║██████╔╝ ╚███╔╝ ██╔████╔██║██║      █████╔╝
 * ██║     ██║██╔══██╗ ██╔██╗ ██║╚██╔╝██║██║     ██╔═══╝ 
 * ███████╗██║██████╔╝██╔╝ ██╗██║ ╚═╝ ██║███████╗███████╗
 * ╚══════╝╚═╝╚═════╝ ╚═╝  ╚═╝╚═╝     ╚═╝╚══════╝╚══════╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$xmllog = LOG . 'libxml2.log';


// Verify if libxml2 is installed
if (is_dir($path) && is_file($path . 'win32\bin.msvc\libxml2_a.lib') && is_file(DEPS_PATH . 'lib\libxml2_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libxml2
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Patch Makefile.msvc
$contents = file_get_contents($path . 'win32\Makefile.msvc');
$contents = str_replace('LIBS = $(LIBS) iconv.lib', 'LIBS = $(LIBS) libiconv_a.lib', $contents);
file_put_contents($path . 'win32\Makefile.msvc', $contents);


// Compile libxml2
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'win32').RN;
$bat .= 'cscript configure.js lib="..\..\deps\lib" include="..\..\deps\include" vcmanifest=yes'.RN;
$bat .= 'nmake'.RN;
$batfile = TMP . 'build_libxml2.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($xmllog, $ret);


// Verify if the build works
if(!is_file($path . 'win32\bin.msvc\libxml2_a.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $xmllog);
else draw_status($label, "complete", Green);


// Install ibxml2
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'win32\bin.msvc\libxml2_a.lib'] = 'lib\libxml2_a.lib';
$files[$path . 'win32\bin.msvc\libxml2_a_dll.lib'] = 'lib\libxml2_a_dll.lib';
foreach(glob($path . 'include\libxml\*.h') as $file)
    $files[$file] = 'include\libxml2\libxml\\' . pathinfo($file, PATHINFO_BASENAME);

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);