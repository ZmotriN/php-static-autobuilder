<?php

$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';

if (is_dir($path) && is_file($path . 'build\lib\libssl.lib') && is_file(DEPS_PATH . 'lib\libssl.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!untar($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Library folder not found");


$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'perl Configure VC-WIN64A'.RN;
$bat .= 'nmake'.RN;
$batfile = TMP . 'build_openssl.bat';
file_put_contents($batfile, $bat);
shell_exec_vs16($batfile);

if(!is_file($path . 'libcrypto_static.lib') || !is_file($path . 'libssl_static.lib')) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}


$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

$builddir = $path . 'build\\';
if(!is_dir($builddir) && !@mkdir($builddir, 0777, true))
    draw_status($label, "failed", Red, true);

$libdir = $builddir . 'lib\\';
if(!is_dir($libdir) && !@mkdir($libdir, 0777, true))
    draw_status($label, "failed", Red, true);

$incdir = $builddir . 'include\openssl\\';
if(!is_dir($incdir) && !@mkdir($incdir, 0777, true))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'libcrypto_static.lib', $libdir . 'libcrypto.lib'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'libssl_static.lib', $libdir . 'libssl.lib'))
    draw_status($label, "failed", Red, true);

foreach(glob($path . 'include\openssl\*') as $file)
    if(!@copy($file, $incdir . pathinfo($file, PATHINFO_BASENAME)))
        draw_status($label, "failed", Red, true);


if(!install_deps($builddir)) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}
