<?php

/**
 * ██╗     ██╗██████╗ ███████╗██╗██████╗ 
 * ██║     ██║██╔══██╗╚══███╔╝██║██╔══██╗
 * ██║     ██║██████╔╝  ███╔╝ ██║██████╔╝
 * ██║     ██║██╔══██╗ ███╔╝  ██║██╔═══╝ 
 * ███████╗██║██████╔╝███████╗██║██║     
 * ╚══════╝╚═╝╚═════╝ ╚══════╝╚═╝╚═╝     
 */


$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$ziplog = LOG . 'libzip.log';


// Verify if libzip is installed
if (is_dir($path) && is_file($path . 'build\lib\libzip_a.lib') && is_file(DEPS_PATH . 'lib\libzip_a.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip libzip
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!unzip($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find unzip results");


// Copy libraries
if(!@copy(DEPS_PATH . 'lib\zlib_a.lib', DEPS_PATH . 'lib\zlib.lib')) exit_error("Can't copy zlib.lib");
if(!@copy(DEPS_PATH . 'lib\libbz2_a.lib', DEPS_PATH . 'lib\libbz2.lib')) exit_error("Can't copy libbz2.lib");
if(!@copy(DEPS_PATH . 'lib\liblzma_a.lib', DEPS_PATH . 'lib\liblzma.lib')) exit_error("Can't copy liblzma.lib");
if(!@copy(DEPS_PATH . 'lib\libzstd.lib', DEPS_PATH . 'lib\zstd.lib')) exit_error("Can't copy zstd.lib");


// Patch CMakeLists
$cmakelists = $path . 'lib\CMakeLists.txt';
$contents = file_get_contents($cmakelists);
$contents = str_replace('if(HAVE_LIBBZ2)', 'if(HAVE_LIBBZ2)' . RN . '  add_compile_definitions(LZMA_API_STATIC)', $contents);
file_put_contents($cmakelists, $contents);


// Configure libzip
$label = "Configure " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path).RN;
$bat .= 'mkdir build-win'.RN;
$bat .= 'cd build-win'.RN;
$bat .= 'set PATH=%PATH%;' . ARCH_PATH . 'deps'.RN;
$bat .= 'cmake -G "Visual Studio 16 2019" BUILD_SHARED_LIBS=OFF ..'.RN;
$batfile = TMP . 'build_libzip.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($ziplog, $ret);


// Verify if the solution works
if(!is_file($path . 'build-win\libzip.sln')) draw_status($label, "failed", Red, true, 'SEE: ' . $ziplog);
else draw_status($label, "complete", Green);


// Patch solution file to enable full static library
$prjfile = $path . 'build-win\lib\zip.vcxproj';
$contents = file_get_contents($prjfile);
$contents = str_replace('<ConfigurationType>DynamicLibrary</ConfigurationType>', '<ConfigurationType>StaticLibrary</ConfigurationType>', $contents);
$contents = str_replace('>.dll<', '>.lib<', $contents);
$addlibs = '<Lib><AdditionalDependencies>' . DEPS_PATH . 'lib\liblzma.lib;' . DEPS_PATH . 'lib\zstd.lib' . ';%(AdditionalDependencies)</AdditionalDependencies></Lib>';
$contents = preg_replace('#<ProjectReference>\s+<LinkLibraryDependencies>false</LinkLibraryDependencies>\s+</ProjectReference>#msi', '<ProjectReference><LinkLibraryDependencies>true</LinkLibraryDependencies></ProjectReference>'. $addlibs, $contents);
file_put_contents($prjfile, $contents);


// Compile libzip
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path . 'build-win').RN;
$bat .= 'DEVENV libzip.sln /rebuild "Release|x64" /project zip'.RN;
$batfile = TMP . 'build_libzip.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($ziplog, $ret, FILE_APPEND);


// Verify if the build works
if(!is_file($path . 'build-win\lib\Release\zip.lib')) draw_status($label, "failed", Red, true, 'SEE: ' . $ziplog);
else draw_status($label, "complete", Green);


// Install libzip
$label = "Install " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);
$builddir = $path . 'build\\';

$files[$path . 'build-win\lib\Release\zip.lib'] = 'lib\libzip_a.lib';
$files[$path . 'build-win\zipconf.h'] = 'include\zipconf.h';
$files[$path . 'lib\zip.h'] = 'include\zip.h';

if(!create_build($builddir, $files)) draw_status($label, "failed", Red, true);
if(!install_deps($builddir)) draw_status($label, "failed", Red, true);
else draw_status($label, "complete", Green);

delete_parent_deps($lib->name);


// Delete temp libraries
unlink(DEPS_PATH . 'lib\zlib.lib');
unlink(DEPS_PATH . 'lib\libbz2.lib');
unlink(DEPS_PATH . 'lib\liblzma.lib');
unlink(DEPS_PATH . 'lib\zstd.lib');
