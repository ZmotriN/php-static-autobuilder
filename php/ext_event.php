<?php

$contents = file_get_contents($path . 'config.w32');
$contents = str_replace('EXTENSION("event", "php_event.c", true,', 'EXTENSION("event", "php_event.c", PHP_EVENT_SHARED,', $contents);
file_put_contents($path . 'config.w32', $contents);
