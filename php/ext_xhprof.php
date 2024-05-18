<?php

/**
 * ██████╗  █████╗ ████████╗ ██████╗██╗  ██╗    ██╗  ██╗██╗  ██╗██████╗ ██████╗  ██████╗ ███████╗
 * ██╔══██╗██╔══██╗╚══██╔══╝██╔════╝██║  ██║    ╚██╗██╔╝██║  ██║██╔══██╗██╔══██╗██╔═══██╗██╔════╝
 * ██████╔╝███████║   ██║   ██║     ███████║     ╚███╔╝ ███████║██████╔╝██████╔╝██║   ██║█████╗  
 * ██╔═══╝ ██╔══██║   ██║   ██║     ██╔══██║     ██╔██╗ ██╔══██║██╔═══╝ ██╔══██╗██║   ██║██╔══╝  
 * ██║     ██║  ██║   ██║   ╚██████╗██║  ██║    ██╔╝ ██╗██║  ██║██║     ██║  ██║╚██████╔╝██║     
 * ╚═╝     ╚═╝  ╚═╝   ╚═╝    ╚═════╝╚═╝  ╚═╝    ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝     ╚═╝  ╚═╝ ╚═════╝ ╚═╝     
 */


// Replace files to the root of extension
foreach(glob($path . 'extension\*') as $file)
    rename($file, $path . pathinfo($file, PATHINFO_BASENAME));

// Patch static cache
$contents = file_get_contents($path . 'xhprof.c');
$contents = str_replace('    ZEND_TSRMLS_CACHE_DEFINE();', '    //ZEND_TSRMLS_CACHE_DEFINE();', $contents);
file_put_contents($path . 'xhprof.c', $contents);

