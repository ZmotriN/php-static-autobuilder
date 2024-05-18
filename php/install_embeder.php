<?php

/**
 * ██╗███╗   ██╗███████╗████████╗ █████╗ ██╗     ██╗         ███████╗███╗   ███╗██████╗ ███████╗██████╗ ███████╗██████╗ 
 * ██║████╗  ██║██╔════╝╚══██╔══╝██╔══██╗██║     ██║         ██╔════╝████╗ ████║██╔══██╗██╔════╝██╔══██╗██╔════╝██╔══██╗
 * ██║██╔██╗ ██║███████╗   ██║   ███████║██║     ██║         █████╗  ██╔████╔██║██████╔╝█████╗  ██║  ██║█████╗  ██████╔╝
 * ██║██║╚██╗██║╚════██║   ██║   ██╔══██║██║     ██║         ██╔══╝  ██║╚██╔╝██║██╔══██╗██╔══╝  ██║  ██║██╔══╝  ██╔══██╗
 * ██║██║ ╚████║███████║   ██║   ██║  ██║███████╗███████╗    ███████╗██║ ╚═╝ ██║██████╔╝███████╗██████╔╝███████╗██║  ██║
 * ╚═╝╚═╝  ╚═══╝╚══════╝   ╚═╝   ╚═╝  ╚═╝╚══════╝╚══════╝    ╚══════╝╚═╝     ╚═╝╚═════╝ ╚══════╝╚═════╝ ╚══════╝╚═╝  ╚═╝
 */


// Clone or update repo
if (SPEED_DEV) return;
if (!is_dir(EMBEDER_PATH)) {
    draw_line('Embeder', 'cloning', Yellow);
    if (git_clone($MATRIX->embeder->repo, EMBEDER_PATH)) draw_status('Embeder', 'up-to-date', Green);
    else draw_status('Embeder', 'failed', Red, true, "Can't clone Embeder repo");
} else {
    draw_line('Embeder', 'updating', Yellow);
    if (git_update(EMBEDER_PATH)) draw_status('Embeder', 'up-to-date', Green);
    else draw_status('Embeder', 'failed', Red, true, "Can't update Embeder repo");
}