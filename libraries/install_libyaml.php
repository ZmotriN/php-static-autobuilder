<?php




$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$yamllog = LOG . 'libyaml.log';


if (is_dir($path) && is_file($path . 'build\lib\libyaml.lib') && is_file(DEPS_PATH . 'lib\libyaml.lib')) {
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
$bat .= 'cmake .'.RN;
$bat .= 'cmake --build . --config Release --clean-first'.RN;

$batfile = TMP . 'build_nghttp2.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile, true);
file_put_contents($yamllog, $ret);


if(!is_file($path . 'Release\yaml.lib')) {
    draw_status($label, "failed", Red, true);
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



if(!@copy($path . 'Release\yaml.lib', $libdir . 'libyaml.lib'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'include\yaml.h', $incdir . 'yaml.h'))
    draw_status($label, "failed", Red, true);




if(!install_deps($builddir)) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}









// die();