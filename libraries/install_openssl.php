<?php

/**
 *  ██████╗ ██████╗ ███████╗███╗   ██╗███████╗███████╗██╗     
 * ██╔═══██╗██╔══██╗██╔════╝████╗  ██║██╔════╝██╔════╝██║     
 * ██║   ██║██████╔╝█████╗  ██╔██╗ ██║███████╗███████╗██║     
 * ██║   ██║██╔═══╝ ██╔══╝  ██║╚██╗██║╚════██║╚════██║██║     
 * ╚██████╔╝██║     ███████╗██║ ╚████║███████║███████║███████╗
 *  ╚═════╝ ╚═╝     ╚══════╝╚═╝  ╚═══╝╚══════╝╚══════╝╚══════╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$oslog = LOG . 'openssl.log';


// Verify if openssl is installed
if (is_dir($path) && is_file($path . 'build\lib\libssl.lib') && is_file(DEPS_PATH . 'lib\libssl.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and untar openssl
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!untar($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Library folder not found");


// Compile openssl
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'perl Configure VC-WIN64A'.RN;
$bat .= 'nmake'.RN;
$batfile = TMP . 'build_openssl.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($oslog, $ret);


// Verify if the build works
if(!is_file($path . 'libcrypto_static.lib') || !is_file($path . 'libssl_static.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $oslog);
else draw_status($label, "complete", Green);


// Install openssl
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

$builddir = $path . 'build\\';
$files[$path . 'libcrypto_static.lib'] = 'lib\libcrypto.lib';
$files[$path . 'libssl_static.lib'] = 'lib\libssl.lib';
foreach(glob($path . 'include\openssl\*') as $file)
    $files[$file] = 'include\openssl\\' . pathinfo($file, PATHINFO_BASENAME);

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);