<?php

/**
 * ███╗   ███╗██████╗ ██████╗ ███████╗ ██████╗██╗███╗   ███╗ █████╗ ██╗     
 * ████╗ ████║██╔══██╗██╔══██╗██╔════╝██╔════╝██║████╗ ████║██╔══██╗██║     
 * ██╔████╔██║██████╔╝██║  ██║█████╗  ██║     ██║██╔████╔██║███████║██║     
 * ██║╚██╔╝██║██╔═══╝ ██║  ██║██╔══╝  ██║     ██║██║╚██╔╝██║██╔══██║██║     
 * ██║ ╚═╝ ██║██║     ██████╔╝███████╗╚██████╗██║██║ ╚═╝ ██║██║  ██║███████╗
 * ╚═╝     ╚═╝╚═╝     ╚═════╝ ╚══════╝ ╚═════╝╚═╝╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$mpdlog = LOG . 'mpdecimal.log';


// Verify if mpdecimal is installed
if (is_dir($path) && is_file($path . 'libmpdec\libmpdec-4.0.0.lib') && is_file(DEPS_PATH . 'lib\libmpdec_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip mpdecimal
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find untar results");
if(!@copy($path . 'libmpdec\Makefile.vc', $path . 'libmpdec\Makefile')) exit_error("Can't copy Makefile");


// Compile mpdecimal
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'libmpdec').RN;
$bat .= 'nmake clean'.RN;
$bat .= 'nmake MACHINE=x64 DEBUG=0'.RN;
$batfile = TMP . 'build_mpdecimal.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($mpdlog, $ret);


// Verify if the build works
if(!is_file($path . 'libmpdec\libmpdec-4.0.0.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $mpdlog);
else draw_status($label, "complete", Green);


// Install mpdecimal
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'libmpdec\libmpdec-4.0.0.lib'] = 'lib\libmpdec_a.lib';
$files[$path . 'libmpdec\mpdecimal.h'] = 'include\mpdecimal.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);