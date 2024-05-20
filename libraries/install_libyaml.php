<?php

/**
 * ██╗     ██╗██████╗ ██╗   ██╗ █████╗ ███╗   ███╗██╗     
 * ██║     ██║██╔══██╗╚██╗ ██╔╝██╔══██╗████╗ ████║██║     
 * ██║     ██║██████╔╝ ╚████╔╝ ███████║██╔████╔██║██║     
 * ██║     ██║██╔══██╗  ╚██╔╝  ██╔══██║██║╚██╔╝██║██║     
 * ███████╗██║██████╔╝   ██║   ██║  ██║██║ ╚═╝ ██║███████╗
 * ╚══════╝╚═╝╚═════╝    ╚═╝   ╚═╝  ╚═╝╚═╝     ╚═╝╚══════╝
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$yamllog = LOG . 'libyaml.log';


// Verify if libyaml is installed
if (is_dir($path) && is_file($path . 'build\lib\libyaml_a.lib') && is_file(DEPS_PATH . 'lib\libyaml_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libyaml
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!$firstdir = zip_first_dir($tmpfile)) exit_error("Invalid zip archive");
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find archive extracted result");


// Compile libyaml
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'cmake .'.RN;
$bat .= 'cmake --build . --config Release --clean-first'.RN;
$batfile = TMP . 'build_libyaml.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($yamllog, $ret);


// Verify if the build works
if(!is_file($path . 'Release\yaml.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $yamllog);
else draw_status($label, "complete", Green);


// Install libyaml
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';
$files[$path . 'Release\yaml.lib'] = 'lib\libyaml_a.lib';
$files[$path . 'include\yaml.h'] = 'include\yaml.h';
if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);