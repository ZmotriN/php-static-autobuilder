<?php

ob_start();
phpinfo();
$info = ob_get_clean();
file_put_contents(DIR . 'phpinfo.html', $info);
wb_exec(DIR . 'phpinfo.html');