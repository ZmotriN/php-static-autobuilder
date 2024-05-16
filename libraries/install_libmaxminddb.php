<?php

$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$lmmlog = LOG . 'libmaxminddb.log';


// Verify if maxminddb is installed
if (is_dir($path) && is_file($path . 'build-win\Release\maxminddb.lib') && is_file(DEPS_PATH . 'lib\libmaxminddb.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip library
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!untar($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find untar results");


// Compile libmaxminddb
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);


$sln = $path . 'build-win\maxminddb.sln';
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'mkdir build-win'.RN;
$bat .= 'cd build-win'.RN;
$bat .= 'cmake -DMSVC_STATIC_RUNTIME=ON -DBUILD_SHARED_LIBS=OFF ..'.RN;
$bat .= 'cmake --build .'.RN;
$bat .= 'DEVENV ' . escapeshellarg($sln) . ' /rebuild "Release|x64"'.RN;

$batfile = TMP . 'build_libmaxminddb.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($lmmlog, $ret);


// Verify if the build works
if(!is_file($path . 'build-win\Release\maxminddb.lib')) {
    draw_status($label, "failed", Red, true, 'SEE: ' . $ptlog);
} else {
    draw_status($label, "complete", Green);
}

// Install library
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);


$builddir = $path . 'build\\';
if(!is_dir($builddir) && !@mkdir($builddir, 0777, true))
    draw_status($label, "failed", Red, true);

$libdir = $builddir . 'lib\\';
if(!is_dir($libdir) && !@mkdir($libdir, 0777, true))
    draw_status($label, "failed", Red, true);

$incdir = $builddir . 'include\\';
if(!is_dir($incdir) && !@mkdir($incdir, 0777, true))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'build-win\Release\maxminddb.lib', $libdir . 'libmaxminddb.lib'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'build-win\generated\maxminddb_config.h', $incdir . 'maxminddb_config.h'))
    draw_status($label, "failed", Red, true);

foreach(glob($path . 'include\*') as $file)
    if(!@copy($file, $incdir . pathinfo($file, PATHINFO_BASENAME)))
        draw_status($label, "failed", Red, true);


if(!install_deps($builddir)) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}

