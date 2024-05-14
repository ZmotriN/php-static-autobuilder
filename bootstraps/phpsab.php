<?php

if(is_file(DIR . 'phpsab.php')) $file = DIR . 'phpsab.php';
elseif(is_file(MASTER . 'phpsab.php')) $file = MASTER . 'phpsab.php';
else draw_status("Adding bootstrap", 'failed', Red, true);

if(!$contents = @file_get_contents($file)) draw_status("Adding bootstrap", 'failed', Red, true);

if(!res_set($release, 'PHP', 'RUN', $contents)) draw_status("Adding bootstrap", 'failed', Red, true);
