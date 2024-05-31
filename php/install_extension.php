<?php

/**
 * ██╗███╗   ██╗███████╗████████╗ █████╗ ██╗     ██╗         ███████╗██╗  ██╗████████╗███████╗███╗   ██╗███████╗██╗ ██████╗ ███╗   ██╗
 * ██║████╗  ██║██╔════╝╚══██╔══╝██╔══██╗██║     ██║         ██╔════╝╚██╗██╔╝╚══██╔══╝██╔════╝████╗  ██║██╔════╝██║██╔═══██╗████╗  ██║
 * ██║██╔██╗ ██║███████╗   ██║   ███████║██║     ██║         █████╗   ╚███╔╝    ██║   █████╗  ██╔██╗ ██║███████╗██║██║   ██║██╔██╗ ██║
 * ██║██║╚██╗██║╚════██║   ██║   ██╔══██║██║     ██║         ██╔══╝   ██╔██╗    ██║   ██╔══╝  ██║╚██╗██║╚════██║██║██║   ██║██║╚██╗██║
 * ██║██║ ╚████║███████║   ██║   ██║  ██║███████╗███████╗    ███████╗██╔╝ ██╗   ██║   ███████╗██║ ╚████║███████║██║╚██████╔╝██║ ╚████║
 * ╚═╝╚═╝  ╚═══╝╚══════╝   ╚═╝   ╚═╝  ╚═╝╚══════╝╚══════╝    ╚══════╝╚═╝  ╚═╝   ╚═╝   ╚══════╝╚═╝  ╚═══╝╚══════╝╚═╝ ╚═════╝ ╚═╝  ╚═══╝
 */

// TODO: REFACTOR

$path = EXT_PATH . $ext->name . '\\';

if (!empty($ext->repo)) {
    
    if (!is_dir($path)) {
        draw_line($ext->name, 'cloning', Yellow);
        if (git_clone($ext->repo, $path)) draw_status($ext->name, 'up-to-date', Green);
        else draw_status($ext->name, 'failed', Red, true, "Can't clone extension repo");
    } else {
        if (!SPEED_DEV) {
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
        if (pathinfo($tmpfile, PATHINFO_EXTENSION) == 'zip') {
            if (!unzip($tmpfile, EXT_PATH)) exit_error();
        } elseif (!untar($tmpfile, EXT_PATH)) exit_error();

        if(!empty($ext->archive)) $tmpdir = EXT_PATH . $ext->archive;
        else $tmpdir = EXT_PATH . $ext->name . '-' . $ext->version;

        if (!is_dir($tmpdir)) exit_error("Can't find extension folder");
        if (!rename_wait($tmpdir, $path)) exit_error("Can't rename extension folder");
        file_put_contents($path . 'version-static.txt', $ext->version);

        if(!empty($ext->install_script)) include(MASTER . 'php\\' . $ext->install_script);

        draw_status($ext->name . '-' . $ext->version, 'installed', Green);
    } elseif (is_file($path . 'version-static.txt')) {
        $version = file_get_contents($path . 'version-static.txt');
        // if (version_compare($ext->version, $version) > 0) {
        if ($ext->version != $version) {
            
            rm_dir($path);
            $tmpfile = TMP . pathinfo($ext->url, PATHINFO_BASENAME);
            if (strtolower(pathinfo($tmpfile, PATHINFO_EXTENSION)) == 'tgz')
                $tmpfile = preg_replace('#\.tgz$#i', '.tar.gz', $tmpfile);

            if (!download_file($ext->url, $tmpfile, pathinfo($tmpfile, PATHINFO_BASENAME))) exit_error();
            if (pathinfo($tmpfile, PATHINFO_EXTENSION) == 'zip') {
                if (!unzip($tmpfile, EXT_PATH)) exit_error();
            } elseif (!untar($tmpfile, EXT_PATH)) exit_error();

            if(!empty($ext->archive)) $tmpdir = EXT_PATH . $ext->archive;
            else $tmpdir = EXT_PATH . $ext->name . '-' . $ext->version;
            
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
