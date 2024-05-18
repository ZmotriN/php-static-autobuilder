<?php

/**
 * ██╗     ██╗██████╗ ███████╗██╗   ██╗███████╗███╗   ██╗████████╗
 * ██║     ██║██╔══██╗██╔════╝██║   ██║██╔════╝████╗  ██║╚══██╔══╝
 * ██║     ██║██████╔╝█████╗  ██║   ██║█████╗  ██╔██╗ ██║   ██║   
 * ██║     ██║██╔══██╗██╔══╝  ╚██╗ ██╔╝██╔══╝  ██║╚██╗██║   ██║   
 * ███████╗██║██████╔╝███████╗ ╚████╔╝ ███████╗██║ ╚████║   ██║   
 * ╚══════╝╚═╝╚═════╝ ╚══════╝  ╚═══╝  ╚══════╝╚═╝  ╚═══╝   ╚═╝   
 */


$path =  ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$tmppath = ARCH_PATH . $lib->name . '-' . $lib->version;
$lelog = LOG . 'libevent.log';


// Verify if libevent is installed
if (is_dir($path) && is_file($path . 'build\lib\libevent.lib') && is_file(DEPS_PATH . 'lib\libevent.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libevent
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!untar($tmpfile, ARCH_PATH)) exit_error();
if(!$dirs = glob($tmppath.'*')) exit_error("Library folder not found");
if(!rename_wait($dirs[0], $tmppath)) exit_error("Can't rename library folder");


// Compile libevent
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
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($lelog, $ret);


// Verify if the build works
if(!is_file($path . 'build-win\lib\Release\event.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $lelog);
else draw_status($label, "complete", Green);


// Install libevent
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';
$files[$path .'build-win\lib\Release\event.lib'] = 'lib\libevent.lib';
$files[$path .'build-win\lib\Release\event_core.lib'] = 'lib\libevent_core.lib';
$files[$path .'build-win\lib\Release\event_extra.lib'] = 'lib\libevent_extras.lib';
$files[$path .'build-win\lib\Release\event_openssl.lib'] = 'lib\libevent_openssl.lib';
$inctmp = $path . 'include\\';
foreach(dig($inctmp . '*.h') as $file)
    $files[$file] = 'include\\' . str_replace($inctmp, '', $file);
$inctmp = $path . 'build-win\include\\';
foreach(dig($inctmp . '*.h') as $file) 
    $files[$file] = 'include\\' . str_replace($inctmp, '', $file);
if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);