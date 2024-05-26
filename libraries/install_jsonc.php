<?php

/**
 *      ██╗███████╗ ██████╗ ███╗   ██╗       ██████╗
 *      ██║██╔════╝██╔═══██╗████╗  ██║      ██╔════╝
 *      ██║███████╗██║   ██║██╔██╗ ██║█████╗██║     
 * ██   ██║╚════██║██║   ██║██║╚██╗██║╚════╝██║     
 * ╚█████╔╝███████║╚██████╔╝██║ ╚████║      ╚██████╗
 *  ╚════╝ ╚══════╝ ╚═════╝ ╚═╝  ╚═══╝       ╚═════╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$jsonclog = LOG . 'jsonc.log';


// Verify if jsonc is installed
if (is_dir($path) && is_file($path . 'build\lib\json-c.lib') && is_file(DEPS_PATH . 'lib\json-c.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip jsonc
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile jsonc
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'md build-win'.RN;
$bat .= 'cd build-win'.RN;
$bat .= 'cmake -G "Visual Studio 16 2019" -DBUILD_SHARED_LIBS=OFF -DCMAKE_BUILD_TYPE=release ..'.RN;
$bat .= 'DEVENV "json-c.sln" /rebuild "Release|x64" /project "json-c"';
$batfile = TMP . 'build_jsonc.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile, true);
file_put_contents($jsonclog, $ret);


// Verify if the build works
if(!is_file($path . 'build-win\Release\json-c.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $lelog);
else draw_status($label, "complete", Green);


// Install jsonc
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'build-win\Release\json-c.lib'] = 'lib\json-c.lib';
$files[$path . 'build-win\json.h'] = 'include\json-c\json.h';
$files[$path . 'arraylist.h'] = 'include\json-c\json.h';
$files[$path . 'debug.h'] = 'include\json-c\debug.h';
$files[$path . 'json_c_version.h'] = 'include\json-c\json_c_version.h';
$files[$path . 'json_object.h'] = 'include\json-c\json_object.h';
$files[$path . 'json_object_iterator.h'] = 'include\json-c\json_object_iterator.h';
$files[$path . 'json_patch.h'] = 'include\json-c\json_patch.h';
$files[$path . 'json_pointer.h'] = 'include\json-c\json_pointer.h';
$files[$path . 'json_tokener.h'] = 'include\json-c\json_tokener.h';
$files[$path . 'json_util.h'] = 'include\json-c\json_util.h';
$files[$path . 'linkhash.h'] = 'include\json-c\linkhash.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);