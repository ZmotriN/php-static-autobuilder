<?php

$path = ARCH_PATH . $lib->name . '-' . $lib->version . '\\';
$lualog = LOG . 'lua.log';


// Verify if lua is installed
if (is_dir($path) && is_file($path . 'src\build\lib\liblua.lib') && is_file(DEPS_PATH . 'lib\liblua.lib')) {
    draw_status($lib->name . '-' . $lib->version, "installed", Green);
    return;
}


// Download and unzip library
$tmpfile = TMP.pathinfo($lib->download_url, PATHINFO_BASENAME);
if(!download_file($lib->download_url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
if(!untar($tmpfile, ARCH_PATH)) exit_error();
if(!is_dir($path)) exit_error("Can't find untar results");


// Compile lua
$label = "Compile " . $lib->name . '-' . $lib->version;
draw_line($label, "running", Yellow);


$builddir = $path . 'src\build\\';
if(!is_dir($builddir) && !@mkdir($builddir, 0777, true))
    draw_status($label, "failed", Red, true);

$bindir = $builddir . 'bin\\';
    if(!is_dir($bindir) && !@mkdir($bindir, 0777, true))
        draw_status($label, "failed", Red, true);

$libdir = $builddir . 'lib\\';
if(!is_dir($libdir) && !@mkdir($libdir, 0777, true))
    draw_status($label, "failed", Red, true);

$incdir = $builddir . 'include\lua\\';
if(!is_dir($incdir) && !@mkdir($incdir, 0777, true))
    draw_status($label, "failed", Red, true);

$bat = '@echo off'.RN;
$bat .= 'cd ' . escapeshellarg($path.'src').RN;
$bat .= 'cl /MD /O2 /c /DLUA_BUILD_AS_DLL *.c'.RN;
$bat .= 'ren lua.obj lua.o'.RN;
$bat .= 'ren luac.obj luac.o'.RN;
$bat .= 'link /DLL /IMPLIB:liblua.lib /OUT:build\bin\liblua.dll *.obj'.RN;
$bat .= 'link /OUT:build\bin\lua.exe lua.o liblua.lib'.RN;
$bat .= 'lib /OUT:build\lib\liblua.lib *.obj'.RN;
$bat .= 'link /OUT:build\bin\luac.exe luac.o build\lib\liblua.lib'.RN;

$batfile = TMP . 'build_lua.bat';
file_put_contents($batfile, $bat);
$ret = shell_exec_vs16($batfile);
file_put_contents($lualog, $ret);




// Verify if the build works
if(!is_file($libdir . 'liblua.lib')) {
    draw_status($label, "failed", Red, true, 'SEE: ' . $ptlog);
} else {
    draw_status($label, "complete", Green);
}

if(!@copy($path . 'src\lauxlib.h', $incdir . 'lauxlib.h'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'src\lua.h', $incdir . 'lua.h'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'src\lua.hpp', $incdir . 'lua.hpp'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'src\luaconf.h', $incdir . 'luaconf.h'))
    draw_status($label, "failed", Red, true);

if(!@copy($path . 'src\lualib.h', $incdir . 'lualib.h'))
    draw_status($label, "failed", Red, true);

if(!install_deps($builddir)) {
    draw_status($label, "failed", Red, true);
} else {
    draw_status($label, "complete", Green);
}

