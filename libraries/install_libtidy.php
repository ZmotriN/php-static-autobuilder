<?php

/**
 * ██╗     ██╗██████╗ ████████╗██╗██████╗ ██╗   ██╗
 * ██║     ██║██╔══██╗╚══██╔══╝██║██╔══██╗╚██╗ ██╔╝
 * ██║     ██║██████╔╝   ██║   ██║██║  ██║ ╚████╔╝ 
 * ██║     ██║██╔══██╗   ██║   ██║██║  ██║  ╚██╔╝  
 * ███████╗██║██████╔╝   ██║   ██║██████╔╝   ██║   
 * ╚══════╝╚═╝╚═════╝    ╚═╝   ╚═╝╚═════╝    ╚═╝   
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$tidylog = LOG . 'libtidy.log';


// Verify if libjpeg-turbo is installed
if (is_dir($path) && is_file($path . 'build\cmake\Release\tidy_static.lib') && is_file(DEPS_PATH . 'lib\tidy_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libtidy
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!rename_wait(ARCH_PATH . $firstdir, $path)) exit_error("Can't rename library path");


// Compile libtidy
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'build\cmake').RN;
$bat .= 'cmake ..\.. -DCMAKE_BUILD_TYPE=Release'.RN;
$bat .= 'cmake --build . --config Release'.RN;
$batfile = TMP . 'build_libtidy.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($tidylog, $ret);


// Verify if the build works
if(!is_file($path . 'build\cmake\Release\tidy_static.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $tidylog);
else draw_status($label, "complete", Green);


// Install libtidy
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build-win\\';

$files[$path . 'build\cmake\Release\tidy_static.lib'] = 'lib\tidy_a.lib';
$files[$path . 'include\tidy.h'] = 'include\tidy.h';
$files[$path . 'include\tidybuffio.h'] = 'include\tidybuffio.h';
$files[$path . 'include\tidyenum.h'] = 'include\tidyenum.h';
$files[$path . 'include\tidyplatform.h'] = 'include\tidyplatform.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);

