<?php


$path =  ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$tmppath = ARCH_PATH . $lib->name . '-' . $lib->version;

if (is_dir($path) && is_file($path . 'build\lib\libevent.lib') && is_file(DEPS_PATH . 'lib\libevent.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!untar($tmpfile, ARCH_PATH)) exit_error();
if(!$dirs = glob($tmppath.'*')) exit_error("Library folder not found");
if(!rename_wait($dirs[0], $tmppath)) exit_error("Can't rename library folder");

$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);

$cmakelists = $path . 'CMakeLists.txt';
if(!is_file($cmakelists)) draw_status($label, "failed", Red, true, "Can't find CMakeLists.txt");
$contents = file_get_contents($cmakelists);
$contents = str_replace('set(EVENT__LIBRARY_TYPE DEFAULT CACHE STRING', 'set(EVENT__LIBRARY_TYPE STATIC CACHE STRING', $contents);
file_put_contents($cmakelists, $contents);


$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'md build-win'.RN;
$bat .= 'cd build-win'.RN;
$bat .= 'set PATH=%PATH%;' . ARCH_PATH . 'deps'.RN;
$bat .= 'cmake -G "Visual Studio 16 2019" ..'.RN;
$bat .= 'DEVENV "libevent.sln" /rebuild "Release|x64"';

$batfile = TMP . 'build_libevent.bat';
$lelog = LOG . 'libevent.log';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($lelog, $ret);


if(!is_file($path . 'build-win\lib\Release\event.lib')) {
    draw_status($label, "failed", Red, true, 'SEE: ' . $lelog);
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

$incdir = $builddir . 'include\\';
if(!is_dir($incdir) && !@mkdir($incdir, 0777, true))
    draw_status($label, "failed", Red, true);


if(!@copy($path .'build-win\lib\Release\event.lib', $libdir . 'libevent.lib'))
    draw_status($label, "failed", Red, true);

if(!@copy($path .'build-win\lib\Release\event_core.lib', $libdir . 'libevent_core.lib'))
    draw_status($label, "failed", Red, true);

if(!@copy($path .'build-win\lib\Release\event_extra.lib', $libdir . 'libevent_extras.lib'))
    draw_status($label, "failed", Red, true);

if(!@copy($path .'build-win\lib\Release\event_openssl.lib', $libdir . 'libevent_openssl.lib'))
    draw_status($label, "failed", Red, true);


$inctmp = $path . 'include\\';
foreach(dig($inctmp . '*.h') as $file) {
    $dst = $incdir . str_replace($inctmp, '', $file);
    $dstdir = pathinfo($dst, PATHINFO_DIRNAME);
    if(!is_dir($dstdir) && !@mkdir($dstdir, 0777, true)) draw_status($label, "failed", Red, true);
    if(!@copy($file, $dst)) draw_status($label, "failed", Red, true);
}

$inctmp = $path . 'build-win\include\\';
foreach(dig($inctmp . '*.h') as $file) {
    $dst = $incdir . str_replace($inctmp, '', $file);
    $dstdir = pathinfo($dst, PATHINFO_DIRNAME);
    if(!is_dir($dstdir) && !@mkdir($dstdir, 0777, true)) draw_status($label, "failed", Red, true);
    if(!@copy($file, $dst)) draw_status($label, "failed", Red, true);
}



if(!install_deps($builddir)) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}

delete_parent_deps($lib->name);
