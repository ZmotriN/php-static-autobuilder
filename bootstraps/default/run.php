<?php
if($argc <= 1 ) {
?>
PHP Static Autobuilder / Default stub file.

<?php echo pathinfo(SELF, PATHINFO_FILENAME)?> --phpinfo | <file.php>

Commands or arguments:
----------------------------------------------------------------------
--phpinfo    Open HTML phpinfo
<file.php>   A PHP or PHAR file to be executed

<?
    exit(0);
}

if($argv[1] == '--phpinfo') {
    ob_start();
    phpinfo();
    $info = ob_get_clean();
    file_put_contents(DIR . 'phpinfo.html', $info);
    shell_exec('start "" ' . escapeshellarg(DIR . 'phpinfo.html'));
} elseif(in_array(strtolower(pathinfo($argv[1], PATHINFO_EXTENSION)), ['php', 'phar'])) {
    if(!$file = realpath($argv[1])) {
        echo "Invalid input file.".RN;
        exit(1);
    }
    include($file);
    exit(0);
} else {
    echo "Invalid parameter.".RN;
    exit(1);
}