<?php

$file = __DIR__ . '\default\run.php';

if(!is_file($file)) draw_status("Adding bootstrap", 'failed', Red, true);

if(!$contents = @file_get_contents($file)) draw_status("Adding bootstrap", 'failed', Red, true);

if(!res_set($release, 'PHP', 'RUN', $contents)) draw_status("Adding bootstrap", 'failed', Red, true);





