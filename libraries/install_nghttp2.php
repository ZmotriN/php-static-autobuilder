<?php

$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';

if (is_dir($path) && is_file($path . 'build\lib\nghttp2.lib') && is_file(DEPS_PATH . 'lib\nghttp2.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}

$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'cmake -DCMAKE_INSTALL_PREFIX="%PREFIX%" -DENABLE_STATIC_LIB=YES -DCMAKE_INSTALL_LIBDIR="%PREFIX%\lib" -G "Visual Studio 16 2019" .'.RN;
$bat .= 'cmake --build . --config Release'.RN;

$batfile = TMP . 'build_nghttp2.bat';
file_put_contents($batfile, $bat);
shell_exec_vs16($batfile);

if(!is_file($path . 'lib\Release\nghttp2.lib')) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}


$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

$builddir = $path . 'build\\';
if(!is_dir($builddir) && !@mkdir($builddir, 0777, true)) {
    draw_status($label, "failed", Red, true);
}

$libdir = $builddir . 'lib\\';
if(!is_dir($libdir) && !@mkdir($libdir, 0777, true))
    draw_status($label, "failed", Red, true);

$pkgdir = $libdir . 'pkgconfig\\';
if(!is_dir($pkgdir) && !@mkdir($pkgdir, 0777, true))
    draw_status($label, "failed", Red, true);

$incdir = $builddir . 'include\nghttp2\\';
if(!is_dir($incdir) && !@mkdir($incdir, 0777, true))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'lib\Release\nghttp2.lib', $libdir . 'nghttp2.lib'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'lib\includes\nghttp2\nghttp2.h', $incdir . 'nghttp2.h'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'lib\includes\nghttp2\nghttp2ver.h', $incdir . 'nghttp2ver.h'))
    draw_status($label, "failed", Red, true);

if(!install_deps($builddir)) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}


delete_parent_deps($lib->name);