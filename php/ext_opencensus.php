<?php


// Movies extension files
foreach(glob($path . '*', GLOB_ONLYDIR) as $file)
    if(basename($file) != 'ext')
        rm_dir($file);
foreach(glob($path . 'ext\*') as $file)
    rename($file, $path . pathinfo($file, PATHINFO_BASENAME));



