<?php

/**
 * ██████╗  █████╗ ████████╗ ██████╗██╗  ██╗     ██████╗  █████╗ ██╗   ██╗████████╗██╗  ██╗
 * ██╔══██╗██╔══██╗╚══██╔══╝██╔════╝██║  ██║    ██╔═══██╗██╔══██╗██║   ██║╚══██╔══╝██║  ██║
 * ██████╔╝███████║   ██║   ██║     ███████║    ██║   ██║███████║██║   ██║   ██║   ███████║
 * ██╔═══╝ ██╔══██║   ██║   ██║     ██╔══██║    ██║   ██║██╔══██║██║   ██║   ██║   ██╔══██║
 * ██║     ██║  ██║   ██║   ╚██████╗██║  ██║    ╚██████╔╝██║  ██║╚██████╔╝   ██║   ██║  ██║
 * ╚═╝     ╚═╝  ╚═╝   ╚═╝    ╚═════╝╚═╝  ╚═╝     ╚═════╝ ╚═╝  ╚═╝ ╚═════╝    ╚═╝   ╚═╝  ╚═╝
 */


// Enable static build
$contents = file_get_contents($path . 'config.w32');
$contents = str_replace('EXTENSION("oauth", "oauth.c provider.c", true);', 'EXTENSION("oauth", "oauth.c provider.c", PHP_OAUTH_SHARED, "/DZEND_ENABLE_STATIC_TSRMLS_CACHE=1");', $contents);
file_put_contents($path . 'config.w32', $contents);

