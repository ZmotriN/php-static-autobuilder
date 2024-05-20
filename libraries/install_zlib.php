<?php


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$project = $path . 'contrib\vstudio\vc14\\';
$zliblog = LOG . 'zlib.log';


// Verify if libsqlite3 is installed
if (is_dir($path) && is_file($project . 'x64\ZlibStatRelease\zlibstat.lib') && is_file(DEPS_PATH . 'lib\zlib_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}



// Download and unzip zlib
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find unzip results");


// Patch zlibvc.def
$contents = file_get_contents($project . 'zlibvc.def');
$contents = preg_replace('#VERSION\W+1\.3\.[0-9]+#i', 'VERSION		1.3', $contents);
file_put_contents($project . 'zlibvc.def', $contents);


// Compile zlib
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($project).RN;
$bat .= 'devenv zlibvc.sln /upgrade'.RN;
$bat .= 'devenv zlibvc.sln /rebuild "Release|x64"'.RN;
$batfile = TMP . 'build_zlib.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile, true);
file_put_contents($zliblog, $ret);


// Verify if the build works
if(!is_file($project . 'x64\ZlibStatRelease\zlibstat.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $zliblog);
else draw_status($label, "complete", Green);


// Install zlib
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$project . 'x64\ZlibStatRelease\zlibstat.lib'] = 'lib\zlib_a.lib';
$files[$path . 'zconf.h'] = 'include\zconf.h';
$files[$path . 'zlib.h'] = 'include\zlib.h';
$files[$path . 'zutil.h'] = 'include\zutil.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);








// die();