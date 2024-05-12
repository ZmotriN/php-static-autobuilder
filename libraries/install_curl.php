<?php

$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';

$buildname = 'libcurl-vc16-x64-release-static-ssl-static-zlib-static-ssh2-static-ipv6-sspi-nghttp2-static';
$builddir = $path . 'builds\\' . $buildname . '\\';

if (is_dir($path) && is_file($builddir . 'lib\libcurl_a.lib') && is_file(DEPS_PATH . 'lib\libcurl_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Library folder not found");


$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path.'winbuild').RN;
$bat .= 'nmake /f Makefile.vc VC=16 MACHINE=x64 mode=static WITH_SSL=static WITH_ZLIB=static WITH_SSH2=static WITH_NGHTTP2=static ENABLE_SSPI=yes ENABLE_IPV6=yes ENABLE_IDN=yes'.RN;
$batfile = TMP . 'build_curl.bat';
file_put_contents($batfile, $bat);
shell_exec_vs16($batfile);


if(!is_dir($builddir)) draw_status($label, "failed", Red, true);

if(!is_file($builddir . 'lib\libcurl_a.lib')) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}


$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

if(!install_deps($builddir)) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}
