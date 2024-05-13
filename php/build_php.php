<?php

$bat = '@echo off' . RN;
$bat .= 'cd ' . escapeshellarg(PHP_PATH) . RN;
$bat .= 'call buildconf' . RN . RN;
$bat .= 'call configure --disable-all ^' . RN;

foreach ($MATRIX->extensions as $ext)
    if (isset($CONFIG['extensions'][$ext->name]) && $CONFIG['extensions'][$ext->name])
        $bat .= '    ' . $ext->switch . ' ^' . RN;

$bat .= '    ' . '--enable-embed=static' . RN . RN;
$bat .= 'nmake'.RN;

$batfile = TMP . 'build_php-' . $MATRIX->php->version . '.bat';
file_put_contents($batfile, $bat);
shell_exec_vs16_phpsdk($batfile, true);