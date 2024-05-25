<?php

/**
 * ██████╗  █████╗ ████████╗ ██████╗██╗  ██╗    ███████╗██╗   ██╗
 * ██╔══██╗██╔══██╗╚══██╔══╝██╔════╝██║  ██║    ██╔════╝██║   ██║
 * ██████╔╝███████║   ██║   ██║     ███████║    █████╗  ██║   ██║
 * ██╔═══╝ ██╔══██║   ██║   ██║     ██╔══██║    ██╔══╝  ╚██╗ ██╔╝
 * ██║     ██║  ██║   ██║   ╚██████╗██║  ██║    ███████╗ ╚████╔╝ 
 * ╚═╝     ╚═╝  ╚═╝   ╚═╝    ╚═════╝╚═╝  ╚═╝    ╚══════╝  ╚═══╝  
 */


// Enable static build
$contents = file_get_contents($path . 'config.w32');
$contents = str_replace("EXTENSION('ev', php_ev_sources, true,", "EXTENSION('ev', php_ev_sources, PHP_EV_SHARED,", $contents);
file_put_contents($path . 'config.w32', $contents);