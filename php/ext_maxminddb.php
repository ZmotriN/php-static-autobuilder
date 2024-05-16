<?php

foreach(glob($path . 'ext\*') as $file) {
    rename($file, $path . pathinfo($file, PATHINFO_BASENAME));
}
