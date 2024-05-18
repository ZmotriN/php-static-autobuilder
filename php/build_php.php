<?php

/**
 * ██████╗ ██╗   ██╗██╗██╗     ██████╗     ██████╗ ██╗  ██╗██████╗ 
 * ██╔══██╗██║   ██║██║██║     ██╔══██╗    ██╔══██╗██║  ██║██╔══██╗
 * ██████╔╝██║   ██║██║██║     ██║  ██║    ██████╔╝███████║██████╔╝
 * ██╔══██╗██║   ██║██║██║     ██║  ██║    ██╔═══╝ ██╔══██║██╔═══╝ 
 * ██████╔╝╚██████╔╝██║███████╗██████╔╝    ██║     ██║  ██║██║     
 * ╚═════╝  ╚═════╝ ╚═╝╚══════╝╚═════╝     ╚═╝     ╚═╝  ╚═╝╚═╝                                                                  
 */


define("BUILD_PATH", PHP_PATH . 'x64\Release_TS\\');
$phplog = LOG . 'php-' . $MATRIX->php->version . '.log';
$emblog = LOG . 'embeder.log';


// Delete last build
if(is_dir(BUILD_PATH))
    foreach(glob(BUILD_PATH.'php8*') as $file)
        unlink($file);


// Test configuration
if($CONFIG['build']['test']) {
    $bat = '@echo off' . RN;
    $bat .= 'cd ' . escapeshellarg(PHP_PATH) . RN;
    $bat .= 'call buildconf' . RN . RN;
    $bat .= 'call configure --disable-all ^' . RN;
    foreach ($MATRIX->extensions as $ext)
        if (isset($CONFIG['extensions'][$ext->name]) && $CONFIG['extensions'][$ext->name])
            $bat .= '    ' . $ext->switch . ' ^' . RN;
    $bat .= '    --enable-embed=static' . RN . RN;
    $batfile = TMP . 'build_php-' . $MATRIX->php->version . '.bat';
    file_put_contents($batfile, $bat);
    shell_exec_vs16_phpsdk($batfile, true);
    exit(0);
} 


// Create build bat file
$bat = '@echo off' . RN;
$bat .= 'cd ' . escapeshellarg(PHP_PATH) . RN;
if($CONFIG['build']['clean']) $bat .= 'nmake clean'.RN;
$bat .= 'call buildconf' . RN . RN;
$bat .= 'call configure --disable-all ^' . RN;
foreach ($MATRIX->extensions as $ext)
    if (isset($CONFIG['extensions'][$ext->name]) && $CONFIG['extensions'][$ext->name])
        $bat .= '    ' . $ext->switch . ' ^' . RN;
$bat .= '    --enable-embed=static' . RN . RN;
$bat .= 'nmake'.RN;
$batfile = TMP . 'build_php-' . $MATRIX->php->version . '.bat';
file_put_contents($batfile, $bat);


// Compile PHP
draw_line("Build PHP", 'running', Yellow);
$ret = shell_exec_vs16_phpsdk($batfile);
file_put_contents($phplog, $ret);
if(is_file(BUILD_PATH.'php8embed.lib') && is_file(BUILD_PATH.'php8ts.lib')) {
    draw_status("Build PHP", 'complete', Green);
} else {
    draw_status("Build PHP", 'failed', Red, true, "SEE: ". $phplog);
}


// Compile static libraries
$makefile = PHP_PATH . 'Makefile';
if(!is_file($makefile)) exit_error("Can't find Makefile");
$makefile_static = __DIR__ . '\Makefile-static';
if(!is_file($makefile_static)) exit_error("Can't find Makefile-static");
file_put_contents($makefile, file_get_contents($makefile_static), FILE_APPEND);

$bat = '@echo off' . RN;
$bat .= 'cd ' . escapeshellarg(PHP_PATH) . RN;
$bat .= 'nmake static > NUL'.RN;
$batfile = TMP . 'build_static_embed.bat';
file_put_contents($batfile, $bat);

draw_line("Build static embed libraries", 'running', Yellow);
shell_exec_vs16_phpsdk($batfile);
if(is_file(BUILD_PATH.'php8embed_static.lib') && is_file(BUILD_PATH.'php8ts_static.lib')) {
    draw_status("Build static embed libraries", 'complete', Green);
} else {
    draw_status("Build static embed libraries", 'failed', Red, true);
}


// Build embeder
$releasedir = PHP_PATH . 'embeder\x64\Release console\\';
$release = $releasedir . 'embeder.exe';

if(is_file($release))
    if(!@unlink($release))
        exit_error("Can't delete previous embeder build");

$bat = '@echo off' . RN;
$bat .= 'cd ' . escapeshellarg(PHP_PATH) . RN;
$bat .= 'DEVENV embeder\embeder.sln /rebuild "Release console|x64"'.RN;
$batfile = TMP . 'build_embeder.bat';
file_put_contents($batfile, $bat);

draw_line("Build Embeder", 'running', Yellow);
$ret = shell_exec_vs16_phpsdk($batfile);
file_put_contents($emblog, $ret);
if(is_file($release)) {
    draw_status("Build Embeder", 'complete', Green);
} else {
    draw_status("Build Embeder", 'failed', Red, true, "SEE: ". $emblog);
}


// Add manifest
draw_line("Adding manifest", 'running', Yellow);
$manifest = BUILD_PATH . 'php8embed.lib.manifest';
if(!is_file($manifest)) exit_error("Can't find php8embed.lib.manifest");
shell_exec(escapeshellarg(RCEDIT_PATH) . ' ' . escapeshellarg($release) . ' --application-manifest ' . escapeshellarg($manifest) . ' 2>&1');
draw_status("Adding manifest", 'complete', Green);


// Add bootstrap
$bootstrap =  MASTER . 'bootstraps\\' . $CONFIG['build']['bootstrap'] . '.php';
if(!is_file($bootstrap)) exit_error("Bootstrap '" . $CONFIG['build']['bootstrap'] . "' not found");
draw_line("Adding bootstrap", 'running', Yellow);
include($bootstrap);
draw_status("Adding bootstrap", 'complete', Green);


// Copy target
$target = BUILD . $CONFIG['build']['target'];
$targetdir = pathinfo($target, PATHINFO_DIRNAME);
if(!is_dir($targetdir) && !@mkdir($targetdir, 0777, true)) exit_error("Can't create target folder");
if(is_file($target) && !@unlink($target)) exit_error("Can't delete last build");
if(!copy($release, $target)) exit_error("Can't copy target");


// BRAVO
wcli_echo(RN."Build complete: " . $target.RN, Green|Bright);