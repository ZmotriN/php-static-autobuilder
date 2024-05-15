<?php

$path = EXT_PATH . $ext->name . '\\';

if (!empty($ext->repo)) {
    if (!SPEED_DEV) {
        if (!is_dir($path)) {
            draw_line($ext->name, 'cloning', Yellow);
            if (git_clone($ext->repo, $path)) draw_status($ext->name, 'up-to-date', Green);
            else draw_status($ext->name, 'failed', Red, true, "Can't clone extension repo");
        } else {
            draw_line($ext->name, 'updating', Yellow);
            if (git_update($path)) draw_status($ext->name, 'up-to-date', Green);
            else draw_status($ext->name, 'failed', Red, true, "Can't update extension repo");
        }
    }
} elseif (!empty($ext->url) && !empty($ext->version)) {
    // TODO: OPTIMIZE
    if (!is_dir($path)) {
        $tmpfile = TMP . pathinfo($ext->url, PATHINFO_BASENAME);
        if (strtolower(pathinfo($tmpfile, PATHINFO_EXTENSION)) == 'tgz')
            $tmpfile = preg_replace('#\.tgz$#i', '.tar.gz', $tmpfile);

        if (!download_file($ext->url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
        if (!untar($tmpfile, EXT_PATH)) exit_error();

        $tmpdir = EXT_PATH . $ext->name . '-' . $ext->version;
        if (!is_dir($tmpdir)) exit_error("Can't find extension folder");
        if (!rename_wait($tmpdir, $path)) exit_error("Can't rename extension folder");
        file_put_contents($path . 'version-static.txt', $ext->version);

        if(!empty($ext->install_script)) include(MASTER . 'php\\' . $ext->install_script);

        draw_status($ext->name . '-' . $ext->version, 'installed', Green);
    } elseif (is_file($path . 'version-static.txt')) {
        $version = file_get_contents($path . 'version-static.txt');
        if (version_compare($ext->version, $version) > 0) {
            
            rm_dir($path);
            $tmpfile = TMP . pathinfo($ext->url, PATHINFO_BASENAME);
            if (strtolower(pathinfo($tmpfile, PATHINFO_EXTENSION)) == 'tgz')
                $tmpfile = preg_replace('#\.tgz$#i', '.tar.gz', $tmpfile);

            if (!download_file($ext->url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
            if (!untar($tmpfile, EXT_PATH)) exit_error();

            $tmpdir = EXT_PATH . $ext->name . '-' . $ext->version;
            if (!is_dir($tmpdir)) exit_error("Can't find extension folder");
            if (!rename_wait($tmpdir, $path)) exit_error("Can't rename extension folder");
            file_put_contents($path . 'version-static.txt', $ext->version);

            if(!empty($ext->install_script)) include(MASTER . 'php\\' . $ext->install_script);

            draw_status($ext->name . '-' . $ext->version, 'installed', Green);
        } else {
            draw_status($ext->name . '-' . $ext->version, 'installed', Green);
        }
    }
}
