<?php

/**
 * ██╗██████╗ ██████╗ ██████╗ ██████╗  ██████╗ ██╗  ██╗██╗   ██╗
 * ██║██╔══██╗╚════██╗██╔══██╗██╔══██╗██╔═══██╗╚██╗██╔╝╚██╗ ██╔╝
 * ██║██████╔╝ █████╔╝██████╔╝██████╔╝██║   ██║ ╚███╔╝  ╚████╔╝ 
 * ██║██╔═══╝ ██╔═══╝ ██╔═══╝ ██╔══██╗██║   ██║ ██╔██╗   ╚██╔╝  
 * ██║██║     ███████╗██║     ██║  ██║╚██████╔╝██╔╝ ██╗   ██║   
 * ╚═╝╚═╝     ╚══════╝╚═╝     ╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═╝   ╚═╝   
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$ip2log = LOG . 'ip2proxy.log';


// Verify if ip2proxy is installed
if (is_dir($path) && is_file($path . 'build\lib\IP2Proxy.lib') && is_file(DEPS_PATH . 'lib\IP2Proxy.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip ip2proxy
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile ip2proxy
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'nmake /A /f Makefile.win'.RN;
$batfile = TMP . 'build_ip2proxy.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($ip2log, $ret);


// Verify if the build works
if(!is_file($path . 'libIP2Proxy\IP2Proxy.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $ip2log);
else draw_status($label, "complete", Green);



// Install ip2proxy
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'libIP2Proxy\IP2Proxy.lib'] = 'lib\IP2Proxy.lib';
$files[$path . 'libIP2Proxy\IP2Proxy.h'] = 'include\IP2Proxy.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);