<?php

$conf = $path . 'config.w32';
$contents = file_get_contents($conf);
$contents = str_replace("observer.c', '/DZEND_ENABLE_STATIC", "observer.c', PHP_OPENTELEMETRY_SHARED, '/DZEND_ENABLE_STATIC", $contents);
file_put_contents($conf, $contents);

