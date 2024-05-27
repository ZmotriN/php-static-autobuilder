<?php

/**
 * ██████╗ ████████╗██╗  ██╗██████╗ ███████╗ █████╗ ██████╗ 
 * ██╔══██╗╚══██╔══╝██║  ██║██╔══██╗██╔════╝██╔══██╗██╔══██╗
 * ██████╔╝   ██║   ███████║██████╔╝█████╗  ███████║██║  ██║
 * ██╔═══╝    ██║   ██╔══██║██╔══██╗██╔══╝  ██╔══██║██║  ██║
 * ██║        ██║   ██║  ██║██║  ██║███████╗██║  ██║██████╔╝
 * ╚═╝        ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝╚═════╝ 
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$ptlog = LOG . 'pthread.log';


// Verify if pthread is installed
if (is_dir($path) && is_file($path . 'build\lib\pthreadVC3.lib') && is_file(DEPS_PATH . 'lib\pthreadVC3.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip library
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile pthdreads
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$slndir = $path . 'windows\VS2019\\';
$sln = $slndir . 'pthread.2019.sln';
if(!is_file($sln)) draw_status($label, "failed", Red, true, "Solution file not found");
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'DEVENV ' . escapeshellarg($sln) . ' /rebuild "Release|x64"';
$batfile = TMP . 'build_pthread.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($ptlog, $ret);


// Verify if the build works
$compdir = $slndir . 'bin\Release-Unicode-64bit-x64\\';
if(!is_file($compdir . 'pthread_static_lib.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $ptlog);
else draw_status($label, "complete", Green);


// Install library
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';
copy($compdir . 'pthread_static_lib.lib', $compdir . 'pthreadVC2.lib');
$files[$compdir . 'pthread_static_lib.lib'] = 'lib\pthreadVC3.lib';
$files[$compdir . 'pthreadVC2.lib'] = 'lib\pthreadVC2.lib';
$files[$path . '_ptw32.h'] = 'include\_ptw32.h';
$files[$path . 'sched.h'] = 'include\sched.h';
$files[$path . 'semaphore.h'] = 'include\semaphore.h';
$files[$path . 'pthread.h'] = 'include\pthread.h';
if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
$contents = file_get_contents($builddir . 'include\sched.h');
$contents = str_replace('  typedef __int64', '  //typedef __int64', $contents);
file_put_contents($builddir . 'include\sched.h', $contents);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);